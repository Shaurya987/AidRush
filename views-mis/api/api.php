<?php
/* ─────────────────────────────────────────────────────────────
   VIEWS India MIS — REST API (single router)
   Endpoints (all under api.php):
     ?action=login              POST  {username|email, password}
     ?action=logout             POST  (server-side session invalidation)
     ?action=me                 GET   (validates current token+tab)
     ?action=heartbeat          POST  (extends session)
     ?action=reset_password     POST  {user_id|username, new_password}      (admin only)
     ?action=user_permissions   GET   ?user_id=N
     ?action=user_permissions   POST  {user_id, permissions:[{section,can_view,can_edit,can_delete}]}
     ?action=options            GET   (lightweight id+name lookups)
     ?action=dashboard          GET   [&programme_id&project_id&donor_id&geography_id]
     ?action=audit              GET   [&q]
     ?action=report             GET   ?period=M|Q|Y&donor_id=&programme_id=&project_id=&from=&to=
     ?action=charts             GET
     ?resource=<name>           GET    list  [&filters...&q=&limit=&offset=]
     ?resource=<name>&id=<n>    GET    one / PUT update / DELETE
     ?resource=<name>           POST   create

   AUTH MODEL
     • Server-issued session_token (32-byte hex) returned on login.
     • Client-generated tab_id (UUID) sent on every request.
     • Both stored on the user row.  Every request MUST send both via
       X-Session-Token / X-Tab-Id headers.
     • 30-min idle expiry; every request extends the deadline.
     • Tab close → sessionStorage gone → next request 401 → re-login.
   ───────────────────────────────────────────────────────────── */
require __DIR__.'/config.php';

/* resource → real table (whitelist) */
$RES = [
  'programmes'=>'programmes','projects'=>'projects','donors'=>'donors',
  'mappings'=>'donor_mappings','geographies'=>'geographies','villages'=>'villages',
  'beneficiaries'=>'beneficiaries','crops'=>'crops','activities'=>'activities',
  'shgs'=>'shgs','loans'=>'loans','indicators'=>'indicators',
  'progress'=>'indicator_progress','users'=>'users','charts'=>'dashboard_charts',
  'objectives'=>'programme_objectives',
  'project_objectives'=>'project_objectives',
  'shg_members'=>'shg_members',
  'hq_targets'=>'hq_targets',
];

/* resource → [id_column, prefix, zero-pad] (auto-generated on POST) */
$ID_GEN = [
  'programmes'=>['programme_id','PRG',3], 'projects'=>['project_id','PRJ',3],
  'donors'=>['donor_id','DON',3], 'mappings'=>['mapping_id','MAP',3],
  'geographies'=>['geography_id','GEO',4], 'villages'=>['demographic_id','DEM',3],
  'beneficiaries'=>['beneficiary_id','BEN',4], 'crops'=>['production_record_id','PROD',4],
  'activities'=>['activity_id','ACT',3], 'shgs'=>['shg_id','SHG',3],
  'loans'=>['loan_record_id','LOAN',3], 'indicators'=>['indicator_id','IND',3],
  'progress'=>['progress_id','PROG',3], 'objectives'=>['objective_id','OBJ',3],
  'project_objectives'=>['objective_id','POBJ',3],
  'shg_members'=>['member_id','MEM',4],
  'hq_targets'=>['target_id','HQT',4],
];

/* Foreign key → [table, id_col, name_col]  — used by enrich_names() */
$FK_NAMES = [
  'programme_id'  => ['programmes','programme_id','programme_name'],
  'project_id'    => ['projects','project_id','project_name'],
  'donor_id'      => ['donors','donor_id','donor_name'],
  'indicator_id'  => ['indicators','indicator_id','indicator_name'],
  'shg_id'        => ['shgs','shg_id','shg_name'],
  'beneficiary_id'=> ['beneficiaries','beneficiary_id','beneficiary_farmer_name'],
];

/* compute the next sequential business ID for a resource, e.g. BEN-2702 */
function next_business_id($resource){
  global $ID_GEN, $RES;
  if(!isset($ID_GEN[$resource])) return null;
  list($col,$prefix,$pad) = $ID_GEN[$resource];
  $table = $RES[$resource];
  $st = db()->prepare("SELECT `$col` FROM `$table` WHERE `$col` LIKE ?");
  $st->execute([$prefix.'-%']);
  $max = 0;
  foreach($st->fetchAll(PDO::FETCH_COLUMN) as $v){
    if(preg_match('/(\d+)\s*$/', (string)$v, $m)){ $n=(int)$m[1]; if($n>$max)$max=$n; }
  }
  return $prefix.'-'.str_pad($max+1, $pad, '0', STR_PAD_LEFT);
}

$method   = $_SERVER['REQUEST_METHOD'];
$action   = $_GET['action'] ?? '';
$resource = $_GET['resource'] ?? '';
$id       = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/* ════════ AUTH ════════ */
const SESSION_IDLE_MINUTES = 30;
const MAX_FAILED_LOGINS    = 8;
const LOCKOUT_MINUTES      = 15;

function current_user(){ return $_SESSION['user'] ?? null; }
function role(){ $u=current_user(); return $u? $u['role'] : ''; }
function is_admin(){
  $u=current_user(); if(!$u) return false;
  $r=strtolower($u['role'] ?? '');
  return strpos($r,'admin')!==false || !empty($u['is_root']);
}

/* validate the incoming session token + tab id against the users table.
   Refreshes session_expires_at on every successful call. */
function load_session_user(){
  $token = header_val('X-Session-Token');
  $tab   = header_val('X-Tab-Id');
  if(!$token || !$tab) return null;
  try{
    $st = db()->prepare(
      "SELECT * FROM users
         WHERE active_session_token=? AND active_tab_id=?
           AND session_expires_at IS NOT NULL AND session_expires_at > NOW()
         LIMIT 1");
    $st->execute([$token,$tab]);
    $u = $st->fetch();
    if(!$u) return null;
    // Sliding idle-expiry, THROTTLED: only write when ~60s+ have elapsed since the
    // last refresh. The session still stays alive during activity, but we avoid an
    // UPDATE on the users table for every single request — that write-per-read was a
    // big part of why large list pages (beneficiaries/outputs/activities) felt slow.
    $full = SESSION_IDLE_MINUTES*60;
    $remaining = strtotime($u['session_expires_at']) - time();
    if(($full - $remaining) >= 60){
      try{
        $exp = date('Y-m-d H:i:s', time() + $full);
        $up = db()->prepare("UPDATE users SET session_expires_at=?, last_seen=NOW() WHERE id=?");
        $up->execute([$exp, $u['id']]);
        $u['session_expires_at'] = $exp;
      }catch(Exception $e){}
    }
    return $u;
  }catch(Exception $e){ return null; }
}

/* call this at the top of every protected endpoint */
function require_session(){
  $u = load_session_user();
  if(!$u){ out(['error'=>'Session expired — please sign in again','code'=>'SESSION_EXPIRED'],401); }
  $_SESSION['user'] = [
    'id'=>$u['id'],'username'=>$u['username'],'name'=>$u['user_name'],
    'role'=>$u['role'],'is_root'=>(int)$u['is_root'],
    'email'=>$u['email'] ?? '',
    'access_level'=>$u['access_level'] ?? '',
    'can_enter'=>$u['can_enter_data'] ?? 'No',
    'can_verify'=>$u['can_verify_data'] ?? 'No',
    'can_view'=>$u['can_view_dashboard'] ?? 'Yes',
  ];
  return $_SESSION['user'];
}
function require_admin(){
  $u = require_session();
  if(!is_admin()) out(['error'=>'Admin access required for this action'],403);
  return $u;
}

/* ────── per-section permissions matrix ──────
   admin/root  → unrestricted
   otherwise   → consult user_permissions table.  If the user has no row
                 for the section, fall back to the legacy role rules so
                 nothing breaks for existing accounts. */
function load_user_permissions($user_id){
  static $cache=[];
  if(isset($cache[$user_id])) return $cache[$user_id];
  $map = [];
  try{
    $st = db()->prepare("SELECT section,can_view,can_edit,can_delete FROM user_permissions WHERE user_id=?");
    $st->execute([$user_id]);
    foreach($st->fetchAll() as $r){
      $map[$r['section']] = [
        'v'=>(int)$r['can_view'],'e'=>(int)$r['can_edit'],'d'=>(int)$r['can_delete'],
      ];
    }
  }catch(Exception $e){}
  return $cache[$user_id] = $map;
}
function legacy_role_can($verb, $resource){
  $r = strtolower(role());
  if (strpos($r,'admin')!==false) return true;
  if (strpos($r,'donor')!==false || strpos($r,'view')!==false) return false;
  if ($resource==='users')        return false;
  return $verb!=='delete' || true;
}
function can_view($section){
  $u=current_user(); if(!$u) return false;
  if(is_admin()) return true;
  $map=load_user_permissions($u['id']);
  if(isset($map[$section])) return $map[$section]['v']===1;
  return true; // default allow read for legacy roles
}
function can_write($resource){
  $u=current_user(); if(!$u) return false;
  if(is_admin()) return true;
  if($resource==='users') return false;
  $map=load_user_permissions($u['id']);
  if(isset($map[$resource])) return $map[$resource]['e']===1;
  return legacy_role_can('edit',$resource);
}
function can_delete($resource){
  $u=current_user(); if(!$u) return false;
  if(is_admin()) return true;
  if($resource==='users') return false;
  $map=load_user_permissions($u['id']);
  if(isset($map[$resource])) return $map[$resource]['d']===1;
  return legacy_role_can('delete',$resource);
}

function table_columns($table){
  static $cache=[];
  if(isset($cache[$table])) return $cache[$table];
  $rows = db()->query("SHOW COLUMNS FROM `$table`")->fetchAll();
  $cols = array_column($rows,'Field');
  $cache[$table]=$cols; return $cols;
}

/* ────── AUDIT — IST timestamp + IP + UA + tab + before/after ────── */
function audit($verb,$entity,$entity_id,$message,$before=null,$after=null){
  $u=current_user();
  $tab=header_val('X-Tab-Id');
  // Never store secrets in the audit trail (password, hashes, session tokens) — no matter the caller.
  $scrub=function($d){ if(!is_array($d)) return $d; foreach(['password','new_password','confirm_password','password_hash','token','active_session_token','active_tab_id'] as $sk){ if(array_key_exists($sk,$d)) unset($d[$sk]); } return $d; };
  $before=$scrub($before); $after=$scrub($after);
  try{
    $st=db()->prepare(
      "INSERT INTO audit_log
         (username,role,action,entity,entity_id,message,
          ip,user_agent,tab_id,ts_ist,before_data,after_data,verb,ts)
       VALUES (?,?,?,?,?,?, ?,?,?,?, ?,?, ?, NOW())");
    $st->execute([
      $u['username'] ?? 'system',
      $u['role'] ?? '',
      $verb, $entity, (string)$entity_id, $message,
      client_ip(), client_ua(), $tab, ist_now(),
      $before? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
      $after?  json_encode($after,  JSON_UNESCAPED_UNICODE) : null,
      $verb,
    ]);
  }catch(Exception $e){
    // Fallback for upgrade-not-run yet — keep the original minimal schema working
    try{
      $st2=db()->prepare("INSERT INTO audit_log (username,role,action,entity,entity_id,message) VALUES (?,?,?,?,?,?)");
      $st2->execute([$u['username']??'system',$u['role']??'',$verb,$entity,(string)$entity_id,$message]);
    }catch(Exception $e2){}
  }
}

/* attach <fk>__name to each row so the UI can show names instead of cryptic IDs */
function enrich_names($rows, $skipCol=null){
  global $FK_NAMES;
  if(!$rows) return $rows;
  $cols = array_keys($rows[0]);
  foreach($FK_NAMES as $fk=>$info){
    if($fk===$skipCol || !in_array($fk,$cols)) continue;
    $ids=[]; foreach($rows as $r){ if(!empty($r[$fk])) $ids[$r[$fk]]=1; }
    $ids=array_keys($ids); if(!$ids) continue;
    list($tbl,$idc,$namec)=$info;
    $ph=implode(',',array_fill(0,count($ids),'?'));
    try{
      $st=db()->prepare("SELECT `$idc`, `$namec` FROM `$tbl` WHERE `$idc` IN ($ph)");
      $st->execute($ids);
      $map=[]; foreach($st->fetchAll() as $r2){ $map[$r2[$idc]]=$r2[$namec]; }
      foreach($rows as &$r){ if(!empty($r[$fk])) $r[$fk.'__name']=$map[$r[$fk]] ?? $r[$fk]; } unset($r);
    }catch(Exception $e){}
  }
  return $rows;
}

/* ════════════════ AUTH ACTIONS ════════════════ */

if ($action==='login') {
  $b = body();
  $identifier = trim(strtolower($b['username'] ?? ''));
  $password   = $b['password'] ?? '';
  if(!$identifier || !$password) out(['error'=>'Username/email and password are required'],400);

  // Accept either username OR email
  $st = db()->prepare(
    "SELECT * FROM users
       WHERE LOWER(username)=? OR LOWER(email)=? LIMIT 1");
  $st->execute([$identifier,$identifier]);
  $u = $st->fetch();

  if(!$u){
    audit('login_fail','auth',$identifier,"Failed login — unknown user [$identifier]");
    out(['error'=>'Invalid username or password'],401);
  }

  // Lockout check
  if(!empty($u['locked_until'])){
    try{
      $until = new DateTime($u['locked_until']);
      if($until > new DateTime()){
        audit('login_locked','auth',$u['username'],"Login blocked — account locked until ".$u['locked_until']);
        out(['error'=>'Too many failed attempts — account locked. Try again later or ask the admin.'],423);
      }
    }catch(Exception $e){}
  }

  if(!password_verify($password, $u['password_hash'])){
    // increment counter, lock if threshold hit
    $cnt = (int)($u['failed_login_count'] ?? 0) + 1;
    $lock = ($cnt >= MAX_FAILED_LOGINS)
      ? date('Y-m-d H:i:s', time() + LOCKOUT_MINUTES*60)
      : null;
    try{
      $up=db()->prepare("UPDATE users SET failed_login_count=?, locked_until=? WHERE id=?");
      $up->execute([$cnt,$lock,$u['id']]);
    }catch(Exception $e){}
    audit('login_fail','auth',$u['username'],"Failed login attempt #$cnt for {$u['username']}");
    out(['error'=>'Invalid username or password'],401);
  }

  // SUCCESS — issue a fresh token + tab id (server side, never trust client)
  $token  = gen_token(32);
  $tabId  = gen_token(16);
  $expAt  = date('Y-m-d H:i:s', time() + SESSION_IDLE_MINUTES*60);
  try{
    $up=db()->prepare(
      "UPDATE users SET
         active_session_token=?, active_tab_id=?, session_expires_at=?,
         last_login=NOW(), last_login_ip=?, last_seen=NOW(),
         failed_login_count=0, locked_until=NULL
       WHERE id=?");
    $up->execute([$token,$tabId,$expAt, client_ip(), $u['id']]);
  }catch(Exception $e){
    out(['error'=>'Login failed — please run sql/upgrade2.sql','detail'=>$e->getMessage()],500);
  }

  $_SESSION['user'] = [
    'id'=>$u['id'],'username'=>$u['username'],'name'=>$u['user_name'],
    'role'=>$u['role'],'is_root'=>(int)$u['is_root'],'email'=>$u['email'],
    'access_level'=>$u['access_level'],
    'can_enter'=>$u['can_enter_data'],'can_verify'=>$u['can_verify_data'],
    'can_view'=>$u['can_view_dashboard'],
  ];
  audit('login','auth',$u['username'],
    "Sign-in success — {$u['username']} ({$u['role']}) from ".client_ip());

  out([
    'ok'=>true,
    'token'=>$token,
    'tab_id'=>$tabId,
    'expires_in'=>SESSION_IDLE_MINUTES*60,
    'user'=>$_SESSION['user'],
    'permissions'=>load_user_permissions($u['id']),
  ]);
}

if ($action==='logout') {
  // Logout can come from a fetch OR a sendBeacon (tab-close) — read either way.
  $token = header_val('X-Session-Token');
  if(!$token){ $b=body(); $token = $b['token'] ?? ''; }
  $reason = ($_GET['reason'] ?? 'manual');
  try{
    $st=db()->prepare("SELECT * FROM users WHERE active_session_token=? LIMIT 1");
    $st->execute([$token]); $u=$st->fetch();
    if($u){
      $_SESSION['user'] = [
        'id'=>$u['id'],'username'=>$u['username'],'name'=>$u['user_name'],
        'role'=>$u['role'],'is_root'=>(int)$u['is_root'],
      ];
      audit('logout','auth',$u['username'],
        "Sign-out ($reason) — {$u['username']} from ".client_ip());
      $up=db()->prepare("UPDATE users SET active_session_token=NULL, active_tab_id=NULL, session_expires_at=NULL WHERE id=?");
      $up->execute([$u['id']]);
    }
  }catch(Exception $e){}
  $_SESSION=[]; @session_destroy();
  out(['ok'=>true]);
}

if ($action==='me') {
  $u = load_session_user();
  if(!$u){ out(['user'=>null]); }
  out(['user'=>[
    'id'=>$u['id'],'username'=>$u['username'],'name'=>$u['user_name'],
    'role'=>$u['role'],'is_root'=>(int)$u['is_root'],'email'=>$u['email'],
    'access_level'=>$u['access_level'],
    'can_enter'=>$u['can_enter_data'],'can_verify'=>$u['can_verify_data'],
    'can_view'=>$u['can_view_dashboard'],
  ],'permissions'=>load_user_permissions($u['id'])]);
}

if ($action==='heartbeat') {
  require_session();
  out(['ok'=>true,'extended'=>SESSION_IDLE_MINUTES*60]);
}

/* ════════════════ ADMIN: PASSWORD RESET ════════════════ */
if ($action==='reset_password') {
  require_admin();
  $b=body();
  $uid = (int)($b['user_id'] ?? 0);
  $newp = $b['new_password'] ?? '';
  if(!$uid || strlen($newp)<6) out(['error'=>'user_id and new_password (≥6 chars) are required'],400);
  $st=db()->prepare("SELECT id,username,is_root FROM users WHERE id=?"); $st->execute([$uid]);
  $target=$st->fetch();
  if(!$target) out(['error'=>'User not found'],404);
  $hash = password_hash($newp, PASSWORD_BCRYPT);
  $up = db()->prepare(
    "UPDATE users SET password_hash=?, password_changed_at=NOW(),
                      active_session_token=NULL, active_tab_id=NULL, session_expires_at=NULL,
                      failed_login_count=0, locked_until=NULL
       WHERE id=?");
  $up->execute([$hash,$uid]);
  audit('password_reset','users',$target['username'],
    "Admin reset password for {$target['username']} — all sessions invalidated");
  out(['ok'=>true]);
}

/* ════════════════ ADMIN: PERMISSION MATRIX ════════════════ */
if ($action==='user_permissions') {
  if($method==='GET'){
    require_admin();
    $uid = (int)($_GET['user_id'] ?? 0);
    if(!$uid) out(['error'=>'user_id is required'],400);
    out(['user_id'=>$uid,'permissions'=>load_user_permissions($uid)]);
  }
  if($method==='POST'){
    require_admin();
    $b=body();
    $uid=(int)($b['user_id'] ?? 0);
    $perms=$b['permissions'] ?? [];
    if(!$uid) out(['error'=>'user_id is required'],400);
    db()->beginTransaction();
    try{
      $del=db()->prepare("DELETE FROM user_permissions WHERE user_id=?"); $del->execute([$uid]);
      $ins=db()->prepare(
        "INSERT INTO user_permissions (user_id,section,can_view,can_edit,can_delete) VALUES (?,?,?,?,?)");
      foreach($perms as $p){
        $ins->execute([
          $uid, $p['section'],
          !empty($p['can_view'])?1:0,
          !empty($p['can_edit'])?1:0,
          !empty($p['can_delete'])?1:0,
        ]);
      }
      db()->commit();
      $tu=db()->prepare("SELECT username FROM users WHERE id=?"); $tu->execute([$uid]); $tn=$tu->fetch();
      audit('permissions','users',$uid,"Updated permission matrix for ".($tn['username'] ?? "#$uid"),null,$perms);
      out(['ok'=>true]);
    }catch(Exception $e){
      db()->rollBack(); out(['error'=>'Could not save permissions','detail'=>$e->getMessage()],500);
    }
  }
}

/* ════════════════ BULK CREATE (Excel import) ════════════════ */
if ($action==='bulk_create') {
  require_session();
  $b = body();
  $bres = $b['resource'] ?? '';
  $brows = $b['rows'] ?? [];
  if(!$bres || !isset($RES[$bres])) out(['error'=>'Unknown resource'],404);
  // Special-case 'users' — admin-only and password is required
  if($bres==='users'){ require_admin(); }
  else if(!can_write($bres)) out(['error'=>'You do not have permission to import to this resource'],403);
  $btable = $RES[$bres];
  $bcols  = table_columns($btable);
  $idCol  = isset($ID_GEN[$bres]) ? $ID_GEN[$bres][0] : null;
  $created=0; $errors=[]; $createdIds=[];
  db()->beginTransaction();
  try{
    foreach($brows as $idx=>$row){
      if(!is_array($row)){ $errors[]=['row'=>$idx+1,'error'=>'Invalid row payload']; continue; }
      if($bres==='users'){
        if(empty($row['username']) || empty($row['password']) || strlen($row['password'])<6){
          $errors[]=['row'=>$idx+1,'error'=>'username and password (≥6 chars) required']; continue;
        }
        $row['password_hash']=password_hash($row['password'],PASSWORD_BCRYPT);
        unset($row['password']);
        $row['password_changed_at']=ist_now();
      }
      $genId=null;
      if($idCol && in_array($idCol,$bcols)){
        $genId = next_business_id($bres); $row[$idCol] = $genId;
      }
      $set=[]; $ph=[]; $vals=[];
      foreach($row as $k=>$v){
        if(in_array($k,$bcols) && !in_array($k,['id','created_at','updated_at','active_session_token','active_tab_id','session_expires_at','last_login','last_seen','failed_login_count','locked_until'])){
          $set[]="`$k`"; $ph[]='?'; $vals[]=($v===''?null:$v);
        }
      }
      if(!$set){ $errors[]=['row'=>$idx+1,'error'=>'No valid fields']; continue; }
      try{
        $sql="INSERT INTO `$btable` (".implode(',',$set).") VALUES (".implode(',',$ph).")";
        $st=db()->prepare($sql); $st->execute($vals);
        $created++;
        $createdIds[] = $genId ?: db()->lastInsertId();
      }catch(Exception $e){
        $errors[]=['row'=>$idx+1,'error'=>$e->getMessage()];
      }
    }
    db()->commit();
    audit('bulk_import',$bres,'#'.$created,
      "Bulk-imported $created $bres (".count($errors)." error".(count($errors)===1?'':'s').")",
      null,
      ['created'=>$created,'errors'=>count($errors)]
    );
    out(['ok'=>true,'created'=>$created,'errors'=>$errors,'ids'=>$createdIds]);
  }catch(Exception $e){
    db()->rollBack();
    out(['error'=>'Bulk import failed','detail'=>$e->getMessage()],500);
  }
}

/* ════════════════ LOOKUP / OPTIONS ════════════════ */
if ($action==='options') {
  require_session();
  $pdo=db();
  $q=function($sql) use($pdo){ try{return $pdo->query($sql)->fetchAll();}catch(Exception $e){return [];} };
  out([
    'programmes'=>$q("SELECT programme_id id, programme_name name FROM programmes WHERE programme_id IS NOT NULL ORDER BY programme_name"),
    'projects'=>$q("SELECT project_id id, project_name name, programme_id FROM projects WHERE project_id IS NOT NULL ORDER BY project_name"),
    'donors'=>$q("SELECT donor_id id, donor_name name, reporting_frequency, donor_type, total_approved_budget_inr FROM donors WHERE donor_id IS NOT NULL ORDER BY donor_name"),
    /* donor → list of project IDs that donor funds (donor_mappings) — used to drive multi-project donor reports */
    'donor_projects'=>$q("SELECT m.donor_id, m.project_id, p.project_name, p.programme_id FROM donor_mappings m LEFT JOIN projects p ON p.project_id=m.project_id WHERE m.donor_id IS NOT NULL AND m.project_id IS NOT NULL GROUP BY m.donor_id, m.project_id"),
    'indicators'=>$q("SELECT indicator_id id, indicator_name name, programme_id, project_id FROM indicators WHERE indicator_id IS NOT NULL ORDER BY indicator_name"),
    'shgs'=>$q("SELECT shg_id id, shg_name name, programme_id, project_id, donor_id FROM shgs WHERE shg_id IS NOT NULL ORDER BY shg_name"),
    'geographies'=>$q("SELECT geography_id id, CONCAT_WS(' · ', NULLIF(village_or_ward,''), NULLIF(gram_panchayat,''), NULLIF(block,''), NULLIF(district,'')) name, district, block, gram_panchayat, village_or_ward village FROM geographies WHERE geography_id IS NOT NULL AND geography_id<>'' ORDER BY district, block, gram_panchayat, village_or_ward"),
    /* staff — for assigning HQ work units to field officers (username is the stable key) */
    'staff'=>$q("SELECT username id, COALESCE(NULLIF(user_name,''),username) name, role FROM users WHERE username IS NOT NULL AND username<>'' ORDER BY name"),
  ]);
}

/* ═══════ BENEFICIARY LOOKUP — loaded lazily & cached (only the Production form needs it; ═══════
   keeping ~2,700 rows OUT of the common ?action=options keeps login/dashboard/saves fast). */
if ($action==='blist') {
  require_session(); $pdo=db();
  try{ $rows=$pdo->query("SELECT beneficiary_id id, CONCAT_WS(' · ', beneficiary_farmer_name, NULLIF(father_or_spouse_name,''), NULLIF(village,''), beneficiary_id) name, geography_id FROM beneficiaries WHERE beneficiary_id IS NOT NULL AND beneficiary_id<>'' ORDER BY beneficiary_farmer_name, father_or_spouse_name")->fetchAll(); }catch(Exception $e){ $rows=[]; }
  out(['rows'=>$rows]);
}

/* ════════════════ COUNTS — live record totals for the Data Entry cards ════════════════ */
if ($action==='counts') {
  require_session();
  $pdo=db();
  $res_out=[];
  foreach($RES as $res=>$tbl){
    if(in_array($res,['users','charts'])) continue;
    try{ $res_out[$res]=(int)$pdo->query("SELECT COUNT(*) c FROM `$tbl`")->fetch()['c']; }catch(Exception $e){ $res_out[$res]=null; }
  }
  out(['counts'=>$res_out]);
}

/* ════════════════ AUDIT TRAIL ════════════════ */
if ($action==='audit') {
  require_session();
  $q='%'.($_GET['q']??'').'%';
  $limit = min((int)($_GET['limit'] ?? 1000), 5000);
  try{
    $st=db()->prepare(
      "SELECT username,role,action,verb,entity,entity_id,message,ip,user_agent,tab_id,
              COALESCE(ts_ist, DATE_ADD(ts,INTERVAL 0 MINUTE)) AS ts_ist, ts,
              before_data, after_data
         FROM audit_log
         WHERE message LIKE ? OR username LIKE ? OR entity LIKE ?
         ORDER BY id DESC LIMIT $limit");
    $st->execute([$q,$q,$q]);
  }catch(Exception $e){
    // legacy schema fallback
    $st=db()->prepare(
      "SELECT username,role,action,entity,entity_id,message,ts AS ts_ist,ts
         FROM audit_log
         WHERE message LIKE ? OR username LIKE ? OR entity LIKE ?
         ORDER BY id DESC LIMIT $limit");
    $st->execute([$q,$q,$q]);
  }
  out(['rows'=>$st->fetchAll()]);
}

/* ════════════════ DASHBOARD ════════════════ */
if ($action==='dashboard') {
  require_session();
  $pdo=db();
  $f=[]; $p=[];
  // Report/dashboard dimensions — each is applied only to tables that actually
  // have the column (see $whereFor), so passing e.g. district= filters beneficiaries
  // & geography counts but harmlessly skips tables without that column.
  foreach(['programme_id','project_id','donor_id','geography_id','district','block','shg_id'] as $k){
    if(!empty($_GET[$k])){ $f[]="$k=?"; $p[$k]=$_GET[$k]; }
  }
  $whereFor=function($table) use($f,$p){
    if(!$f) return ['',[]];
    $cols=table_columns($table);
    $cl=[]; $vals=[];
    foreach($p as $k=>$v){ if(in_array($k,$cols)){ $cl[]="`$k`=?"; $vals[]=$v; } }
    return $cl? [' WHERE '.implode(' AND ',$cl), $vals] : ['',[]];
  };
  $count=function($table) use($pdo,$whereFor){
    [$w,$v]=$whereFor($table); $st=$pdo->prepare("SELECT COUNT(*) c FROM `$table`$w"); $st->execute($v);
    return (int)$st->fetch()['c'];
  };
  $sum=function($table,$col) use($pdo,$whereFor){
    [$w,$v]=$whereFor($table); $st=$pdo->prepare("SELECT COALESCE(SUM(`$col`),0) s FROM `$table`$w"); $st->execute($v);
    return (float)$st->fetch()['s'];
  };
  $groupCount=function($table,$col,$limit=12) use($pdo,$whereFor){
    [$w,$v]=$whereFor($table);
    $st=$pdo->prepare("SELECT COALESCE(NULLIF(TRIM(`$col`),''),'—') k, COUNT(*) c FROM `$table`$w GROUP BY k ORDER BY c DESC LIMIT $limit");
    $st->execute($v); return $st->fetchAll();
  };

  $kpi=[
    'programmes'=>$count('programmes'),'projects'=>$count('projects'),'donors'=>$count('donors'),
    'beneficiaries'=>$count('beneficiaries'),'shgs'=>$count('shgs'),'activities'=>$count('activities'),
    'villages'=>$count('villages'),'loans'=>$count('loans'),
    'budget'=>$sum('donors','total_approved_budget_inr'),
    'loan_total'=>$sum('loans','loan_amount_inr'),
    'ach'=>$sum('activities','cumulative_achievement'),
    'tgt'=>$sum('activities','total_target'),
    'crop_income'=>$sum('crops','income_inr'),
  ];
  // Distinct Districts & Blocks from the Geography master (filter-aware)
  try{ [$wG,$vG]=$whereFor('geographies');
    $dq=$pdo->prepare("SELECT COUNT(DISTINCT NULLIF(TRIM(district),'')) c FROM geographies$wG"); $dq->execute($vG); $kpi['districts']=(int)$dq->fetch()['c'];
    $bq=$pdo->prepare("SELECT COUNT(DISTINCT NULLIF(TRIM(block),'')) c FROM geographies$wG"); $bq->execute($vG); $kpi['blocks']=(int)$bq->fetch()['c'];
  }catch(Exception $e){ $kpi['districts']=0; $kpi['blocks']=0; }
  // Active Donors = donors that currently fund at least one project (have a mapping) — filter-aware
  try{ [$wM2,$vM2]=$whereFor('donor_mappings');
    $sqlAd = $wM2 ? "SELECT COUNT(DISTINCT donor_id) c FROM donor_mappings$wM2 AND donor_id IS NOT NULL AND TRIM(donor_id)<>''"
                  : "SELECT COUNT(DISTINCT donor_id) c FROM donor_mappings WHERE donor_id IS NOT NULL AND TRIM(donor_id)<>''";
    $am=$pdo->prepare($sqlAd); $am->execute($vM2); $kpi['active_donors']=(int)$am->fetch()['c'];
  }catch(Exception $e){ $kpi['active_donors']=$kpi['donors']; }
  $donorFund=$pdo->query("SELECT donor_name k, COALESCE(total_approved_budget_inr,0) s FROM donors ORDER BY s DESC")->fetchAll();
  $progRows=$pdo->query("SELECT programme_id, programme_name FROM programmes ORDER BY programme_name")->fetchAll();
  $prgName=[]; foreach($progRows as $pr){ if(!isset($prgName[$pr['programme_id']])) $prgName[$pr['programme_id']]=$pr['programme_name']; }
  $objByPrg=[];
  try{ foreach($pdo->query("SELECT programme_id, objective, status FROM programme_objectives ORDER BY id")->fetchAll() as $o){ $objByPrg[$o['programme_id']][]=['objective'=>$o['objective'],'status'=>$o['status']]; } }catch(Exception $e){}
  $programmesList=[];
  foreach($prgName as $pid=>$pname){ $programmesList[]=['programme_id'=>$pid,'programme_name'=>$pname,'objectives'=>$objByPrg[$pid]??[]]; }

  // Projects + their objectives (parallel to programmes panel)
  $projectsList=[];
  try{
    $prjRows=$pdo->query("SELECT project_id, project_name, programme_id, project_code FROM projects ORDER BY project_name")->fetchAll();
    $objByPrj=[];
    foreach($pdo->query("SELECT project_id, objective, status, target_beneficiaries FROM project_objectives ORDER BY id")->fetchAll() as $o){
      $objByPrj[$o['project_id']][] = ['objective'=>$o['objective'],'status'=>$o['status'],'target_beneficiaries'=>$o['target_beneficiaries']];
    }
    foreach($prjRows as $pr){
      $projectsList[] = [
        'project_id'=>$pr['project_id'], 'project_name'=>$pr['project_name'],
        'programme_id'=>$pr['programme_id'], 'project_code'=>$pr['project_code'],
        'objectives'=> $objByPrj[$pr['project_id']] ?? [],
      ];
    }
  }catch(Exception $e){}

  // ── Honour the dashboard filter on the Programmes/Projects + objectives panels ──
  $fProg = trim($_GET['programme_id'] ?? '');
  $fProj = trim($_GET['project_id']   ?? '');
  $fDonor= trim($_GET['donor_id']     ?? '');
  if($fProg!=='' || $fProj!=='' || $fDonor!==''){
    $projProg=[]; foreach($projectsList as $pr){ $projProg[$pr['project_id']]=$pr['programme_id']; }
    $donorProj=null; $donorProg=null;
    if($fDonor!==''){
      $donorProj=[]; $donorProg=[];
      try{ $st=$pdo->prepare("SELECT DISTINCT project_id, programme_id FROM donor_mappings WHERE donor_id=?"); $st->execute([$fDonor]);
        foreach($st->fetchAll() as $r){
          if(!empty($r['project_id'])){ $donorProj[$r['project_id']]=true; if(isset($projProg[$r['project_id']])) $donorProg[$projProg[$r['project_id']]]=true; }
          if(!empty($r['programme_id'])) $donorProg[$r['programme_id']]=true;
        }
      }catch(Exception $e){}
    }
    $projProgId = ($fProj!=='' && isset($projProg[$fProj])) ? $projProg[$fProj] : '';
    $projectsList = array_values(array_filter($projectsList, function($pr) use($fProg,$fProj,$donorProj){
      if($fProj!=='' && $pr['project_id']!==$fProj) return false;
      if($fProg!=='' && $pr['programme_id']!==$fProg) return false;
      if($donorProj!==null && !isset($donorProj[$pr['project_id']])) return false;
      return true;
    }));
    $programmesList = array_values(array_filter($programmesList, function($p) use($fProg,$projProgId,$donorProg){
      if($fProg!=='' && $p['programme_id']!==$fProg) return false;
      if($projProgId!=='' && $p['programme_id']!==$projProgId) return false;
      if($donorProg!==null && !isset($donorProg[$p['programme_id']])) return false;
      return true;
    }));
  }

  // SHGs + their members (for the dashboard panel)
  $shgsList=[];
  try{
    // honour the same filters: only those SHGs the active filter would include
    [$ws,$vs]=$whereFor('shgs');
    $stShg=$pdo->prepare("SELECT shg_id, shg_name, village, block, no_of_members, monthly_savings_by_members_inr FROM `shgs`$ws ORDER BY shg_name");
    $stShg->execute($vs);
    $shgRows=$stShg->fetchAll();
    $memBy=[];
    try{ foreach($pdo->query("SELECT shg_id, member_name, role, gender, age, contact_number, savings_contribution_inr, status FROM shg_members ORDER BY id")->fetchAll() as $m){
      $memBy[$m['shg_id']][] = $m;
    } }catch(Exception $e){}
    foreach($shgRows as $s){
      $shgsList[] = array_merge($s, ['members' => $memBy[$s['shg_id']] ?? []]);
    }
  }catch(Exception $e){}

  $byPrgRaw=$groupCount('beneficiaries','programme_id',20);
  $byProgramme=[]; foreach($byPrgRaw as $r){ $byProgramme[]=['k'=>($prgName[$r['k']]??$r['k']),'c'=>$r['c']]; }
  // Beneficiaries by Project (resolve names)
  $prjName=[]; foreach(($projectsList??[]) as $pp){ if(!isset($prjName[$pp['project_id']])) $prjName[$pp['project_id']]=$pp['project_name']; }
  $byPrjRaw=$groupCount('beneficiaries','project_id',20);
  $byProject=[]; foreach($byPrjRaw as $r){ $byProject[]=['k'=>($prjName[$r['k']]??$r['k']),'c'=>$r['c']]; }
  // Donor → funded projects panel (live from donor mappings)
  $donorFunded=[];
  try{
    $dm=$pdo->query("SELECT m.donor_id, d.donor_name, m.project_id, p.project_name FROM donor_mappings m LEFT JOIN donors d ON d.donor_id=m.donor_id LEFT JOIN projects p ON p.project_id=m.project_id WHERE m.donor_id IS NOT NULL ORDER BY d.donor_name, p.project_name")->fetchAll();
    $byDonor=[];
    foreach($dm as $row){ $dn=$row['donor_name']?:$row['donor_id']; if($dn===null||$dn==='') continue; if(!isset($byDonor[$dn])) $byDonor[$dn]=[]; $pn=$row['project_name']?:$row['project_id']; if($pn && !in_array($pn,$byDonor[$dn])) $byDonor[$dn][]=$pn; }
    foreach($byDonor as $dn=>$prjs){ $donorFunded[]=['donor'=>$dn,'projects'=>$prjs]; }
  }catch(Exception $e){}

  // How many people have an active (non-expired) session right now.
  $online=0;
  try{ $online=(int)$pdo->query("SELECT COUNT(*) c FROM users WHERE active_session_token IS NOT NULL AND session_expires_at IS NOT NULL AND session_expires_at > NOW()")->fetch()['c']; }catch(Exception $e){}

  // ── Income impact: Baseline (at registration) vs Current (cumulative, from Production/Output) ──
  // Current income per activity is already SUM(crops.income_inr) = $kpi['crop_income'].
  // Baseline comes from the beneficiary's registration breakdown when present, else the
  // legacy registration annual income. All of this respects the dashboard filters.
  $benCols = table_columns('beneficiaries');
  $hasBL   = in_array('bl_paddy_income', $benCols);
  [$wB,$vB] = $whereFor('beneficiaries');
  [$wC,$vC] = $whereFor('crops');
  $acts = ['paddy','millet','vegetable','mushroom','goat','poultry','micro'];
  $blByAct = array_fill_keys($acts, 0.0);
  if($hasBL){
    $blExpr = "(COALESCE(bl_paddy_income,0)+COALESCE(bl_millet_income,0)+COALESCE(bl_vegetable_income,0)+COALESCE(bl_mushroom_income,0)+COALESCE(bl_goat_income,0)+COALESCE(bl_poultry_income,0)+COALESCE(bl_micro_enterprise_income,0))";
    $stB = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN $blExpr>0 THEN $blExpr ELSE COALESCE(current_income_per_annum_inr,0) END),0) s FROM beneficiaries$wB");
    $stB->execute($vB); $kpi['baseline_income'] = (float)$stB->fetch()['s'];
    $stBA = $pdo->prepare("SELECT COALESCE(SUM(bl_paddy_income),0) paddy, COALESCE(SUM(bl_millet_income),0) millet, COALESCE(SUM(bl_vegetable_income),0) vegetable, COALESCE(SUM(bl_mushroom_income),0) mushroom, COALESCE(SUM(bl_goat_income),0) goat, COALESCE(SUM(bl_poultry_income),0) poultry, COALESCE(SUM(bl_micro_enterprise_income),0) micro FROM beneficiaries$wB");
    $stBA->execute($vB); $r = $stBA->fetch();
    foreach($acts as $a){ $blByAct[$a] = (float)$r[$a]; }
  } else {
    $stB = $pdo->prepare("SELECT COALESCE(SUM(COALESCE(current_income_per_annum_inr,0)),0) s FROM beneficiaries$wB");
    $stB->execute($vB); $kpi['baseline_income'] = (float)$stB->fetch()['s'];
  }
  $stCA = $pdo->prepare("SELECT
      COALESCE(SUM(CASE WHEN LOWER(TRIM(crop))='paddy' THEN income_inr ELSE 0 END),0) paddy,
      COALESCE(SUM(CASE WHEN LOWER(TRIM(crop))='millet' THEN income_inr ELSE 0 END),0) millet,
      COALESCE(SUM(CASE WHEN LOWER(TRIM(crop))='vegetable' THEN income_inr ELSE 0 END),0) vegetable,
      COALESCE(SUM(CASE WHEN LOWER(TRIM(alternative_livelihood)) IN ('mushroom','mushroom cultivation') OR LOWER(TRIM(crop))='mushroom' THEN income_inr ELSE 0 END),0) mushroom,
      COALESCE(SUM(CASE WHEN LOWER(TRIM(alternative_livelihood)) IN ('goatery','goat','goat rearing') THEN income_inr ELSE 0 END),0) goat,
      COALESCE(SUM(CASE WHEN LOWER(TRIM(alternative_livelihood)) IN ('poultry','backyard poultry') THEN income_inr ELSE 0 END),0) poultry,
      COALESCE(SUM(CASE WHEN LOWER(TRIM(alternative_livelihood)) IN ('micro enterprise','petty shop','tailoring','dairy','bee keeping') THEN income_inr ELSE 0 END),0) micro
    FROM crops$wC");
  $stCA->execute($vC); $cur = $stCA->fetch();
  $curByAct = array_fill_keys($acts, 0.0);
  foreach($acts as $a){ $curByAct[$a] = (float)$cur[$a]; }
  $income_compare = ['baseline'=>$blByAct, 'current'=>$curByAct];

  // ── Income over time — current income per project year (trend), filter-aware ──
  $income_by_year=[];
  try{
    $stY=$pdo->prepare("SELECT COALESCE(NULLIF(TRIM(project_year),''),'Unspecified') y, COALESCE(SUM(income_inr),0) s FROM crops$wC GROUP BY y ORDER BY y");
    $stY->execute($vC);
    foreach($stY->fetchAll() as $r){ $income_by_year[]=['year'=>$r['y'],'income'=>(float)$r['s']]; }
  }catch(Exception $e){}

  // ── Data readiness (handover health) — filter-aware ──
  $readiness=['ben_total'=>0,'ben_with_baseline'=>0,'ben_with_production'=>0,'prod_total'=>0,'prod_missing_income'=>0];
  try{
    $readiness['ben_total']=(int)$count('beneficiaries');
    $blCond = $hasBL ? "($blExpr>0 OR COALESCE(current_income_per_annum_inr,0)>0)" : "COALESCE(current_income_per_annum_inr,0)>0";
    $wBb = $wB ? ($wB.' AND '.$blCond) : (' WHERE '.$blCond);
    $stRB=$pdo->prepare("SELECT COUNT(*) c FROM beneficiaries$wBb"); $stRB->execute($vB); $readiness['ben_with_baseline']=(int)$stRB->fetch()['c'];
    $wCp = $wC ? ($wC.' AND ') : ' WHERE ';
    $stRP=$pdo->prepare("SELECT COUNT(DISTINCT beneficiary_id) c FROM crops$wCp beneficiary_id IS NOT NULL AND TRIM(beneficiary_id)<>''"); $stRP->execute($vC); $readiness['ben_with_production']=(int)$stRP->fetch()['c'];
    $readiness['prod_total']=(int)$count('crops');
    $stRM=$pdo->prepare("SELECT COUNT(*) c FROM crops$wCp (income_inr IS NULL OR income_inr=0)"); $stRM->execute($vC); $readiness['prod_missing_income']=(int)$stRM->fetch()['c'];
  }catch(Exception $e){}

  // Farmers by crop — distinct beneficiaries growing each crop (filter-aware)
  $byCrop=[];
  try{ $stCr=$pdo->prepare("SELECT COALESCE(NULLIF(TRIM(crop),''),'—') k, COUNT(DISTINCT beneficiary_id) c FROM crops$wC GROUP BY k HAVING k<>'—' ORDER BY c DESC LIMIT 15"); $stCr->execute($vC); $byCrop=$stCr->fetchAll(); }catch(Exception $e){}

  // Beneficiaries by District & Block — combined geography breakdown (filter-aware)
  $byDistrictBlock=[];
  try{ $bcols=table_columns('beneficiaries');
    if(in_array('block',$bcols)){
      $hasD=in_array('district',$bcols);
      $keyExpr = $hasD ? "CONCAT_WS(' · ', NULLIF(TRIM(district),''), NULLIF(TRIM(block),''))" : "NULLIF(TRIM(block),'')";
      $grp     = $hasD ? "district, block" : "block";
      [$wDB,$vDB]=$whereFor('beneficiaries');
      $stDB=$pdo->prepare("SELECT $keyExpr k, COUNT(*) c FROM beneficiaries$wDB GROUP BY $grp HAVING k IS NOT NULL AND k<>'' ORDER BY c DESC LIMIT 15");
      $stDB->execute($vDB); $byDistrictBlock=$stDB->fetchAll();
    }
  }catch(Exception $e){}

  out([
    'kpi'=>$kpi,
    'online'=>$online,
    'income_compare'=>$income_compare,
    'income_by_year'=>$income_by_year,
    'readiness'=>$readiness,
    'byProject'=>$byProject,
    'byCrop'=>$byCrop,
    'byDistrictBlock'=>$byDistrictBlock,
    'donorFunded'=>$donorFunded,
    'caste'=>$groupCount('beneficiaries','caste'),
    'gender'=>$groupCount('beneficiaries','gender'),
    'religion'=>$groupCount('beneficiaries','religion'),
    'income'=>$groupCount('beneficiaries','primary_income_source'),
    'block'=>$groupCount('beneficiaries','block'),
    'donorFund'=>$donorFund,
    'byProgramme'=>$byProgramme,
    'programmesList'=>$programmesList,
    'projectsList'=>$projectsList,
    'shgsList'=>$shgsList,
    'topActivities'=>(function() use($pdo,$whereFor){
       [$w,$v]=$whereFor('activities');
       $st=$pdo->prepare("SELECT project_activity nm, COALESCE(total_target,0) tgt, COALESCE(cumulative_achievement,0) ach
                          FROM activities$w ORDER BY ach DESC LIMIT 5");
       $st->execute($v); return $st->fetchAll(); })(),
  ]);
}

/* ════════════════ REPORT — Period × Donor × Programme ════════════════ */
if ($action==='report') {
  require_session();
  $pdo=db();

  $period = strtoupper($_GET['period'] ?? 'Y');  // M / Q / Y / C
  $donor  = trim($_GET['donor_id']     ?? '');
  $prog   = trim($_GET['programme_id'] ?? '');
  $proj   = trim($_GET['project_id']   ?? '');
  $district = trim($_GET['district'] ?? '');
  $block    = trim($_GET['block'] ?? '');
  $geo      = trim($_GET['geography_id'] ?? '');
  $shg      = trim($_GET['shg_id'] ?? '');
  $from   = trim($_GET['from'] ?? '');
  $to     = trim($_GET['to']   ?? '');

  // Build WHERE that applies only to tables that have the column — supports every report dimension
  $applyFilter = function($table) use($pdo,$donor,$prog,$proj,$district,$block,$geo,$shg){
    $cols = table_columns($table);
    $w=[]; $vals=[];
    if($donor && in_array('donor_id',$cols))       { $w[]="`$table`.`donor_id`=?";     $vals[]=$donor; }
    if($prog  && in_array('programme_id',$cols))   { $w[]="`$table`.`programme_id`=?"; $vals[]=$prog; }
    if($proj  && in_array('project_id',$cols))     { $w[]="`$table`.`project_id`=?";   $vals[]=$proj; }
    if($district && in_array('district',$cols))    { $w[]="`$table`.`district`=?";     $vals[]=$district; }
    if($block && in_array('block',$cols))          { $w[]="`$table`.`block`=?";        $vals[]=$block; }
    if($geo && in_array('geography_id',$cols))     { $w[]="`$table`.`geography_id`=?"; $vals[]=$geo; }
    if($shg && in_array('shg_id',$cols))           { $w[]="`$table`.`shg_id`=?";       $vals[]=$shg; }
    return [$w? ' WHERE '.implode(' AND ',$w) : '', $vals];
  };

  // 1) Beneficiaries breakdown
  [$wB,$pB]=$applyFilter('beneficiaries');
  $st=$pdo->prepare("SELECT COUNT(*) c,
                            COALESCE(SUM(current_income_per_annum_inr),0) inc,
                            COALESCE(SUM(total_land_acre),0) land
                       FROM `beneficiaries`$wB");
  $st->execute($pB); $bn=$st->fetch();
  $st=$pdo->prepare("SELECT COALESCE(NULLIF(TRIM(caste),''),'—') k, COUNT(*) c FROM `beneficiaries`$wB GROUP BY k ORDER BY c DESC");
  $st->execute($pB); $byCaste=$st->fetchAll();
  $st=$pdo->prepare("SELECT COALESCE(NULLIF(TRIM(gender),''),'—') k, COUNT(*) c FROM `beneficiaries`$wB GROUP BY k ORDER BY c DESC");
  $st->execute($pB); $byGender=$st->fetchAll();
  $st=$pdo->prepare("SELECT COALESCE(NULLIF(TRIM(block),''),'—') k, COUNT(*) c FROM `beneficiaries`$wB GROUP BY k ORDER BY c DESC");
  $st->execute($pB); $byBlock=$st->fetchAll();

  // 2) Activities — quarterly performance (already columnised in the table)
  [$wA,$pA]=$applyFilter('activities');
  $st=$pdo->prepare("SELECT project_activity nm, unit_of_measurement unit,
                            COALESCE(total_target,0) total_target,
                            COALESCE(y1_q1,0) y1_q1, COALESCE(y1_q2,0) y1_q2,
                            COALESCE(y1_q3,0) y1_q3, COALESCE(y1_q4,0) y1_q4,
                            COALESCE(y1_total_achievement,0) y1_ach,
                            COALESCE(target_year_2,0) y2_target, COALESCE(y2_total_achievement,0) y2_ach,
                            COALESCE(target_year_3,0) y3_target, COALESCE(y3_total_achievement,0) y3_ach,
                            COALESCE(cumulative_achievement,0) cum_ach
                       FROM `activities`$wA ORDER BY nm");
  $st->execute($pA); $acts=$st->fetchAll();

  // 3) Loans — group by FY (works as proxy for annual donor report)
  [$wL,$pL]=$applyFilter('loans');
  $st=$pdo->prepare("SELECT financial_year fy, repayment_status status,
                            COUNT(*) c, COALESCE(SUM(loan_amount_inr),0) amt
                       FROM `loans`$wL GROUP BY fy,status ORDER BY fy,status");
  $st->execute($pL); $loanRows=$st->fetchAll();

  // 4) Indicator progress — period-wise rows (Quarter | Period)
  [$wP,$pP]=$applyFilter('indicator_progress');
  $st=$pdo->prepare("SELECT indicator_id, reporting_period, reporting_year, quarter,
                            COALESCE(target_for_period,0) target_for_period,
                            COALESCE(achievement_for_period,0) achievement_for_period,
                            COALESCE(cumulative_achievement,0) cumulative_achievement,
                            COALESCE(variance,0) variance
                       FROM `indicator_progress`$wP ORDER BY reporting_year, quarter, reporting_period");
  $st->execute($pP); $indProg=$st->fetchAll();

  // 5) SHGs + savings
  [$wS,$pS]=$applyFilter('shgs');
  $st=$pdo->prepare("SELECT COUNT(*) c,
                            COALESCE(SUM(no_of_members),0) members,
                            COALESCE(SUM(monthly_savings_by_members_inr),0) monthly
                       FROM `shgs`$wS");
  $st->execute($pS); $shgT=$st->fetch();

  // 6) Crops / outputs by intervention
  [$wC,$pC]=$applyFilter('crops');
  $st=$pdo->prepare("SELECT COALESCE(NULLIF(TRIM(crop),''),'—') k,
                            COUNT(*) c,
                            COALESCE(SUM(production_kg),0) qty,
                            COALESCE(SUM(income_inr),0) inc,
                            COALESCE(SUM(net_profit_inr),0) profit
                       FROM `crops`$wC GROUP BY k ORDER BY inc DESC");
  $st->execute($pC); $cropG=$st->fetchAll();

  // 7) Donor budget vs disbursed (mappings.approved_amount_inr)
  $donorRows=[];
  if(!$donor){
    $st=$pdo->query("SELECT d.donor_id, d.donor_name, d.donor_type,
                            COALESCE(d.total_approved_budget_inr,0) budget,
                            COALESCE((SELECT SUM(approved_amount_inr) FROM donor_mappings m WHERE m.donor_id=d.donor_id),0) AS mapped
                       FROM donors d ORDER BY budget DESC");
    $donorRows = $st->fetchAll();
  } else {
    $st=$pdo->prepare("SELECT d.donor_id, d.donor_name, d.donor_type,
                              COALESCE(d.total_approved_budget_inr,0) budget,
                              COALESCE((SELECT SUM(approved_amount_inr) FROM donor_mappings m WHERE m.donor_id=d.donor_id),0) AS mapped
                         FROM donors d WHERE d.donor_id=?");
    $st->execute([$donor]); $donorRows=$st->fetchAll();
  }

  out([
    'period'=>$period,
    'filters'=>['donor_id'=>$donor,'programme_id'=>$prog,'project_id'=>$proj,'from'=>$from,'to'=>$to],
    'generated_at'=>ist_now().' IST',
    'beneficiaries'=>['total'=>(int)$bn['c'],'income_total'=>(float)$bn['inc'],'land_total'=>(float)$bn['land']],
    'by_caste'=>$byCaste,'by_gender'=>$byGender,'by_block'=>$byBlock,
    'activities'=>$acts,
    'loans'=>$loanRows,
    'indicator_progress'=>$indProg,
    'shg_summary'=>['count'=>(int)$shgT['c'],'members'=>(int)$shgT['members'],'monthly_savings'=>(float)$shgT['monthly']],
    'crops'=>$cropG,
    'donors'=>$donorRows,
  ]);
}

/* ════════════════ CUSTOM DASHBOARD CHARTS ════════════════ */
if ($action==='charts') {
  require_session();
  $pdo=db();
  $charts=$pdo->query("SELECT * FROM dashboard_charts ORDER BY sort_order, id")->fetchAll();
  $result=[];
  foreach($charts as $c){
    $src = $c['source'] ?: 'indicator';
    if ($src!=='indicator' && isset($RES[$src])) {
      $tbl=$RES[$src];
      $cols=table_columns($tbl);
      $gb = in_array($c['group_by'],$cols) ? $c['group_by'] : null;
      $measure = $c['measure'] ?: 'count';
      if(!$gb){ continue; }
      $useSum = ($measure!=='count' && in_array($measure,$cols));
      $valExpr = $useSum ? "COALESCE(SUM(`$measure`),0)" : "COUNT(*)";
      $sql="SELECT COALESCE(NULLIF(TRIM(`$gb`),''),'—') k, $valExpr v FROM `$tbl` GROUP BY k ORDER BY v DESC LIMIT 25";
      $rows=$pdo->query($sql)->fetchAll();
      $labels=[]; $values=[]; $tableRows=[];
      $nameMap=null;
      if(isset($FK_NAMES[$gb])){
        list($ftbl,$fidc,$fnamec)=$FK_NAMES[$gb];
        $ids=array_values(array_filter(array_map(fn($r)=>$r['k'],$rows), fn($x)=>$x!=='—'));
        if($ids){ $ph=implode(',',array_fill(0,count($ids),'?')); $s2=$pdo->prepare("SELECT `$fidc`,`$fnamec` FROM `$ftbl` WHERE `$fidc` IN ($ph)"); $s2->execute($ids); $nameMap=[]; foreach($s2->fetchAll() as $r2){$nameMap[$r2[$fidc]]=$r2[$fnamec];} }
      }
      foreach($rows as $r){
        $lbl = $nameMap && isset($nameMap[$r['k']]) ? $nameMap[$r['k']] : $r['k'];
        $labels[]=$lbl; $values[]=(float)$r['v'];
        $tableRows[]=['Category'=>$lbl, ($useSum?'Total':'Count')=>$r['v']];
      }
      $result[]=[
        'id'=>(int)$c['id'],'title'=>$c['title'],'chart_type'=>$c['chart_type'],'kind'=>'pivot',
        'labels'=>$labels,'achievement'=>$values,'target'=>[],'cumulative'=>[],'table'=>$tableRows,
        'subtitle'=>($useSum?('Sum of '.$measure):'Count').' by '.$gb,
      ];
      continue;
    }
    $indId=$c['indicator_id'];
    $st=$pdo->prepare("SELECT * FROM indicators WHERE indicator_id=? LIMIT 1"); $st->execute([$indId]); $ind=$st->fetch();
    $st=$pdo->prepare("SELECT * FROM indicator_progress WHERE indicator_id=? ORDER BY id"); $st->execute([$indId]); $prog=$st->fetchAll();
    $labels=[]; $target=[]; $achievement=[]; $cumulative=[]; $tableRows=[];
    if($prog){
      foreach($prog as $p){
        $labels[]=$p['reporting_period']?:($p['quarter']?:'');
        $target[]=(float)$p['target_for_period'];
        $achievement[]=(float)$p['achievement_for_period'];
        $cumulative[]=(float)$p['cumulative_achievement'];
        $tableRows[]=['Period'=>$p['reporting_period'],'Target'=>$p['target_for_period'],'Achievement'=>$p['achievement_for_period'],'Cumulative'=>$p['cumulative_achievement'],'Variance'=>$p['variance']];
      }
    } else if($ind){
      $labels=['Baseline','Target']; $achievement=[(float)$ind['baseline_value'],(float)$ind['project_target']]; $target=[null,null];
      $tableRows[]=['Baseline'=>$ind['baseline_value'],'Target'=>$ind['project_target'],'Unit'=>$ind['unit']];
    }
    $result[]=[
      'id'=>(int)$c['id'],'title'=>$c['title'],'chart_type'=>$c['chart_type'],'kind'=>'indicator',
      'indicator_id'=>$indId,'subtitle'=>$ind?($ind['indicator_name'].($ind['unit']?(' · '.$ind['unit']):'')):$indId,
      'labels'=>$labels,'target'=>$target,'achievement'=>$achievement,'cumulative'=>$cumulative,'table'=>$tableRows,
    ];
  }
  out(['charts'=>$result]);
}

/* ════════════════ CRUD ════════════════ */
if (!$resource || !isset($RES[$resource])) out(['error'=>'Unknown resource'],404);
$table=$RES[$resource];
require_session();

// Section-level read gate
if (!can_view($resource)) out(['error'=>'You do not have access to '.$resource],403);

if ($method==='GET') {
 try {
  $cols=table_columns($table);
  $where=[]; $params=[];
  foreach($_GET as $k=>$v){
    if(in_array($k,['resource','id','q','limit','offset','action'])) continue;
    if(in_array($k,$cols) && $v!==''){ $where[]="`$k`=?"; $params[]=$v; }
  }
  if(!empty($_GET['q'])){
    $q='%'.$_GET['q'].'%';
    $textCols=array_slice($cols,0,12);
    $ors=[]; foreach($textCols as $c){ $ors[]="`$c` LIKE ?"; $params[]=$q; }
    if($ors) $where[]='('.implode(' OR ',$ors).')';
  }
  $w = $where? ' WHERE '.implode(' AND ',$where) : '';
  if ($id) {
    $st=db()->prepare("SELECT * FROM `$table` WHERE id=?"); $st->execute([$id]);
    $row = $st->fetch();
    if($row && $resource==='users') unset($row['password_hash']);
    out(['row'=>$row]);
  }
  $limit=min((int)($_GET['limit']??5000),10000); $offset=(int)($_GET['offset']??0);
  $cnt=db()->prepare("SELECT COUNT(*) c FROM `$table`$w"); $cnt->execute($params); $total=(int)$cnt->fetch()['c'];
  $st=db()->prepare("SELECT * FROM `$table`$w ORDER BY id DESC LIMIT $limit OFFSET $offset");
  $st->execute($params);
  $rows=$st->fetchAll();
  $ownId = isset($ID_GEN[$resource]) ? $ID_GEN[$resource][0] : null;
  $rows=enrich_names($rows, $ownId);
  if($resource==='users'){
    foreach($rows as &$r){ unset($r['password_hash'],$r['active_session_token'],$r['active_tab_id']); } unset($r);
  }
  if($resource==='donors' && $rows){
    $bc=[]; $pc=[];
    try{ foreach(db()->query("SELECT donor_id, COUNT(*) c FROM beneficiaries GROUP BY donor_id")->fetchAll() as $r2){ $bc[$r2['donor_id']]=(int)$r2['c']; } }catch(Exception $e){}
    try{ foreach(db()->query("SELECT donor_id, COUNT(DISTINCT project_id) c FROM donor_mappings GROUP BY donor_id")->fetchAll() as $r2){ $pc[$r2['donor_id']]=(int)$r2['c']; } }catch(Exception $e){}
    foreach($rows as &$r){ $r['_beneficiaries']=$bc[$r['donor_id']]??0; $r['_projects']=$pc[$r['donor_id']]??0; } unset($r);
  }
  // Programmes: attach an objective count so each row shows "🎯 N" live
  if($resource==='programmes' && $rows){
    $oc=[];
    try{ foreach(db()->query("SELECT programme_id, COUNT(*) c FROM programme_objectives GROUP BY programme_id")->fetchAll() as $r2){ $oc[$r2['programme_id']]=(int)$r2['c']; } }catch(Exception $e){}
    foreach($rows as &$r){ $r['_objectives']=$oc[$r['programme_id']]??0; } unset($r);
  }
  // Projects: attach an objective count so each row shows "🎯 N" live
  if($resource==='projects' && $rows){
    $oc=[];
    try{ foreach(db()->query("SELECT project_id, COUNT(*) c FROM project_objectives GROUP BY project_id")->fetchAll() as $r2){ $oc[$r2['project_id']]=(int)$r2['c']; } }catch(Exception $e){}
    foreach($rows as &$r){ $r['_objectives']=$oc[$r['project_id']]??0; } unset($r);
  }
  // SHGs: attach a member count so each row shows "👥 N" live
  if($resource==='shgs' && $rows){
    $mc=[];
    try{ foreach(db()->query("SELECT shg_id, COUNT(*) c FROM shg_members GROUP BY shg_id")->fetchAll() as $r2){ $mc[$r2['shg_id']]=(int)$r2['c']; } }catch(Exception $e){}
    foreach($rows as &$r){ $r['_members']=$mc[$r['shg_id']]??0; } unset($r);
  }
  out(['rows'=>$rows,'total'=>$total,'limit'=>$limit,'offset'=>$offset]);
 } catch (Throwable $e) {
  // Never let a query error surface to the browser as a bare 500 / "failed to fetch".
  out(['error'=>'Could not load '.$resource.' right now. '.$e->getMessage()], 500);
 }
}

if ($method==='POST') {
  // users: admin-only
  if($resource==='users'){
    require_admin();
    $b=body(); $cols=table_columns($table);
    if(empty($b['username']) || empty($b['password'])) out(['error'=>'Username and password are required'],400);
    if(strlen($b['password'])<6) out(['error'=>'Password must be at least 6 characters'],400);
    $b['password_hash']=password_hash($b['password'],PASSWORD_BCRYPT);
    unset($b['password']);
    $b['password_changed_at']=ist_now();
    $set=[]; $ph=[]; $vals=[];
    foreach($b as $k=>$v){
      if(in_array($k,$cols) && !in_array($k,['id','created_at','active_session_token','active_tab_id','session_expires_at','last_login','last_seen','failed_login_count','locked_until','is_root'])){
        $set[]="`$k`"; $ph[]='?'; $vals[]=($v===''?null:$v);
      }
    }
    if(!$set) out(['error'=>'No valid fields'],400);
    $sql="INSERT INTO `users` (".implode(',',$set).") VALUES (".implode(',',$ph).")";
    $st=db()->prepare($sql); $st->execute($vals);
    $newid=db()->lastInsertId();
    audit('create','users',$b['username'] ?? $newid,"Admin created user [".($b['username']??'')."] role=".($b['role']??''),null,array_diff_key($b,['password_hash'=>1]));
    out(['ok'=>true,'id'=>$newid],201);
  }
  if(!can_write($resource)) out(['error'=>'You do not have permission to add records'],403);
  $b=body(); $cols=table_columns($table);
  $genId=null;
  if(isset($ID_GEN[$resource])){
    $idCol=$ID_GEN[$resource][0];
    if(in_array($idCol,$cols)){ $genId=next_business_id($resource); $b[$idCol]=$genId; }
  }
  $set=[]; $ph=[]; $vals=[];
  foreach($b as $k=>$v){
    if(in_array($k,$cols) && !in_array($k,['id','created_at','updated_at'])){
      $set[]="`$k`"; $ph[]='?'; $vals[]=($v===''?null:$v);
    }
  }
  if(!$set) out(['error'=>'No valid fields'],400);
  $sql="INSERT INTO `$table` (".implode(',',$set).") VALUES (".implode(',',$ph).")";
  $st=db()->prepare($sql); $st->execute($vals);
  $newid=db()->lastInsertId();
  audit('create',$resource,$genId?:$newid,"Added $resource ".($genId?:('#'.$newid)),null,$b);
  out(['ok'=>true,'id'=>$newid,'business_id'=>$genId],201);
}

if ($method==='PUT') {
  if(!$id) out(['error'=>'id required'],400);
  if($resource==='users'){
    require_admin();
    $cols=table_columns($table);
    $st=db()->prepare("SELECT * FROM users WHERE id=?"); $st->execute([$id]); $existing=$st->fetch();
    if(!$existing) out(['error'=>'User not found'],404);
    $b=body();
    // Root admin can't be demoted from admin or have is_root removed
    if(!empty($existing['is_root'])){
      if(isset($b['role']) && stripos($b['role'],'admin')===false) out(['error'=>'Root admin role cannot be changed'],403);
      if(isset($b['username']) && strtolower($b['username'])!==strtolower($existing['username'])) out(['error'=>'Root admin username cannot be changed'],403);
      unset($b['is_root']);
    }
    // Password change: optional — only via reset_password endpoint OR if "new_password" provided here
    if(!empty($b['password']) || !empty($b['new_password'])){
      $newp = $b['new_password'] ?? $b['password'];
      if(strlen($newp)<6) out(['error'=>'New password must be at least 6 chars'],400);
      $b['password_hash']=password_hash($newp,PASSWORD_BCRYPT);
      $b['password_changed_at']=ist_now();
    }
    unset($b['password'],$b['new_password']);
    $set=[]; $vals=[];
    foreach($b as $k=>$v){
      if(in_array($k,$cols) && !in_array($k,['id','created_at','active_session_token','active_tab_id','session_expires_at','last_login','last_seen','failed_login_count','locked_until'])){
        $set[]="`$k`=?"; $vals[]=($v===''?null:$v);
      }
    }
    if(!$set) out(['error'=>'No valid fields'],400);
    $vals[]=$id;
    $st=db()->prepare("UPDATE `users` SET ".implode(',',$set)." WHERE id=?");
    $st->execute($vals);
    unset($existing['password_hash']);
    audit('update','users',$existing['username'],"Admin edited user ".$existing['username'],$existing,$b);
    out(['ok'=>true]);
  }
  if(!can_write($resource)) out(['error'=>'You do not have permission to edit records'],403);
  $b=body(); $cols=table_columns($table);
  $st=db()->prepare("SELECT * FROM `$table` WHERE id=?"); $st->execute([$id]); $before=$st->fetch();
  $set=[]; $vals=[];
  foreach($b as $k=>$v){
    if(in_array($k,$cols) && !in_array($k,['id','created_at','updated_at'])){
      $set[]="`$k`=?"; $vals[]=($v===''?null:$v);
    }
  }
  if(!$set) out(['error'=>'No valid fields'],400);
  $vals[]=$id;
  $st=db()->prepare("UPDATE `$table` SET ".implode(',',$set)." WHERE id=?");
  $st->execute($vals);
  audit('update',$resource,$id,"Edited $resource #$id",$before,$b);
  out(['ok'=>true]);
}

if ($method==='DELETE') {
  if(!$id) out(['error'=>'id required'],400);
  if($resource==='users'){
    require_admin();
    $st=db()->prepare("SELECT id,username,is_root FROM users WHERE id=?"); $st->execute([$id]); $target=$st->fetch();
    if(!$target) out(['error'=>'User not found'],404);
    if(!empty($target['is_root'])) out(['error'=>'The root admin cannot be deleted'],403);
    $me = current_user();
    if($me && (int)$me['id']===(int)$id) out(['error'=>'You cannot delete yourself'],403);
    db()->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
    db()->prepare("DELETE FROM user_permissions WHERE user_id=?")->execute([$id]);
    audit('delete','users',$target['username'],"Admin deleted user ".$target['username'],$target,null);
    out(['ok'=>true]);
  }
  if(!can_delete($resource)) out(['error'=>'You do not have permission to delete records'],403);
  $st=db()->prepare("SELECT * FROM `$table` WHERE id=?"); $st->execute([$id]); $before=$st->fetch();
  $st=db()->prepare("DELETE FROM `$table` WHERE id=?"); $st->execute([$id]);
  audit('delete',$resource,$id,"Deleted $resource #$id",$before,null);
  out(['ok'=>true]);
}

out(['error'=>'Unsupported request'],400);
