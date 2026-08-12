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

/* ════════════════ SECURITY HARDENING (no functional change) ════════════════
   config.php reflects ANY Origin for local development convenience. In
   production that would let a malicious website call this API with the
   visitor's cookies. Enforce here, AFTER config: only the site's own host
   (and localhost, for development) may keep the CORS grant. */
$__origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if($__origin){
  $__ohost = strtolower(parse_url($__origin, PHP_URL_HOST) ?: '');
  $__host  = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? '')[0]);
  if($__ohost !== $__host && !in_array($__ohost, ['localhost','127.0.0.1'], true)){
    header_remove('Access-Control-Allow-Origin');
    header_remove('Access-Control-Allow-Credentials');
  }
}
/* Security headers — served from PHP so no .htaccess is needed on the host */
header('X-Content-Type-Options: nosniff');          // JSON must never be sniffed as HTML
header('X-Frame-Options: DENY');                    // the API has no business inside an iframe
header('Referrer-Policy: no-referrer');             // never leak API URLs to other sites
ini_set('display_errors','0');                      // PHP warnings go to the log, never to visitors

/* User-facing error text must never leak SQL / schema internals. The full
   message goes to the server error log; the user gets a safe, useful hint. */
function safe_err($e, $fallback='Something went wrong — please try again.'){
  $m = $e instanceof Exception ? $e->getMessage() : (string)$e;
  error_log('[VIEWS-MIS] '.$m);
  if(stripos($m,"doesn't exist")!==false || stripos($m,'Unknown column')!==false)
    return 'This section needs a technical update before it can be used. No data has been lost. Please contact developer.';
  if(stripos($m,'Duplicate entry')!==false) return 'Duplicate value — a record with this key already exists.';
  if(stripos($m,'cannot be null')!==false || stripos($m,'Incorrect')!==false) return 'A value was missing or in the wrong format.';
  return $fallback;
}

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
  'workspaces'=>'workspaces',
  'gramsabha'=>'gram_sabha',
  'convergence'=>'convergence',
];

/* ════════ ONE livelihood vocabulary, the same eleven the browser uses ════════
   The beneficiary baseline table and Production & Output share these categories,
   so a before/after comparison always compares like with like. Every spelling the
   database has ever held is listed against its category, which is why records
   entered under an older wording still land in the right column of every report
   instead of quietly disappearing.  Keep in step with LIVELIHOOD_ALIAS in index.html. */
$LIVELIHOODS = [
  'paddy'            => ['label'=>'Paddy',               'crop'=>true,  'alias'=>['paddy','rice','dhan']],
  'millet'           => ['label'=>'Millet',              'crop'=>true,  'alias'=>['millet','millets','ragi','finger millet']],
  'vegetable'        => ['label'=>'Vegetable',           'crop'=>true,  'alias'=>['vegetable','vegetables','vegetable crop']],
  'tuber'            => ['label'=>'Tuber Crop',          'crop'=>true,  'alias'=>['tuber crop','tuber','tuber crops','tubers']],
  'pulses'           => ['label'=>'Pulses',              'crop'=>true,  'alias'=>['pulses','pulse','dal']],
  'oilseed'          => ['label'=>'Oilseeds',            'crop'=>true,  'alias'=>['oilseeds','oilseed','oil seed','oil seeds']],
  'mushroom'         => ['label'=>'Mushroom Cultivation','crop'=>true,  'alias'=>['mushroom cultivation','mushroom']],
  'goat'             => ['label'=>'Goat Rearing',        'crop'=>false, 'alias'=>['goat rearing','goat','goatery','goatary']],
  'poultry'          => ['label'=>'Backyard Poultry',    'crop'=>false, 'alias'=>['backyard poultry','poultry']],
  'micro_enterprise' => ['label'=>'Micro Enterprise',    'crop'=>false, 'alias'=>['micro enterprise','petty shop','tailoring','dairy','bee keeping','beekeeping','enterprise']],
  'other'            => ['label'=>'Other Income',        'crop'=>false, 'alias'=>['other income','other','others']],
];
/* SQL that resolves a crops row to EXACTLY ONE category key — the same rule the
   browser follows in livelihoodKey(): a specific match on the crop column wins,
   then a specific match on the older alternative_livelihood column, and anything
   left over is Other Income.
   Resolving to a single key matters: a legacy row that carries a value in BOTH
   columns must be counted once, not once per column, or every income total would
   be inflated. */
function liv_key_sql(){
  global $LIVELIHOODS;
  static $sql = null;
  if($sql !== null) return $sql;
  $cropCase=[]; $altCase=[];
  foreach($LIVELIHOODS as $k=>$d){
    if($k==='other') continue;   // the catch-all is the ELSE, never a WHEN
    $in = implode(',', array_map(fn($a)=>"'".str_replace("'","''",$a)."'", $d['alias']));
    $cropCase[] = "WHEN LOWER(TRIM(COALESCE(crop,''))) IN ($in) THEN '$k'";
    $altCase[]  = "WHEN LOWER(TRIM(COALESCE(alternative_livelihood,''))) IN ($in) THEN '$k'";
  }
  $sql = "CASE ".implode(' ',$cropCase).' '.implode(' ',$altCase)." ELSE 'other' END";
  return $sql;
}
/* TRUE when a crops row belongs to the given category — one row, one category. */
function liv_sql($key){
  global $LIVELIHOODS;
  if(!isset($LIVELIHOODS[$key])) return '0';
  return "(".liv_key_sql()." = '".$key."')";
}
/* The category's display label, for GROUP BY in the reports. */
function liv_label_sql(){
  global $LIVELIHOODS;
  $parts=[];
  foreach($LIVELIHOODS as $k=>$d){ $parts[]="WHEN '$k' THEN '".str_replace("'","''",$d['label'])."'"; }
  return "CASE (".liv_key_sql().") ".implode(' ',$parts)." ELSE 'Other Income' END";
}
/* The non-farm categories: no kilograms, no price per kilogram, so their income is
   typed straight in rather than calculated. */
function liv_nonfarm_keys(){ global $LIVELIHOODS; return array_keys(array_filter($LIVELIHOODS, fn($v)=>!$v['crop'])); }

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
  'workspaces'=>['workspace_id','WS',4],
  'gramsabha'=>['gramsabha_id','GS',4],
  'convergence'=>['convergence_id','CNV',4],
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
/* Users & Access is grantable via the permission matrix ('users' row):
   v = see the user list · e = create/edit users & their permissions · d = delete users.
   Root-protected accounts remain modifiable ONLY by root callers. */
function can_manage_users($verb='e'){
  if(is_admin()) return true;
  $u=current_user(); if(!$u) return false;
  $map=load_user_permissions($u['id']);
  if(!isset($map['users'])) return false;
  if($verb==='v') return $map['users']['v']===1;
  if($verb==='d') return $map['users']['d']===1;
  return $map['users']['e']===1;
}
/* Sensitive pages (audit, misstatus): admin, or an explicit View tick in the matrix.
   The nav hides them client-side; this enforces the same rule on the API itself. */
function can_view_section($section){
  if(is_admin()) return true;
  $u=current_user(); if(!$u) return false;
  $map=load_user_permissions($u['id']);
  return isset($map[$section]) && $map[$section]['v']===1;
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
/* 🎯 TARGETS are a separate, explicit grant — the numbers HQ plans against.
   Field officers update ACHIEVEMENTS; they can never set or change a target
   unless the admin ticks the 'targets' row (Edit) in Users & Access.
   Deny by default: no matrix row = no target rights (admins/root always can). */
function can_edit_targets(){
  $u=current_user(); if(!$u) return false;
  if(is_admin()) return true;
  $map=load_user_permissions($u['id']);
  return isset($map['targets']) && $map['targets']['e']===1;
}
/* The target-carrying columns of each resource — stripped from edits made
   without the grant, so the HQ-set numbers can never be overwritten. */
function target_columns($resource){
  if($resource==='activities') return ['total_target','target_year_1','target_year_2','target_year_3','target_year_4','target_year_5','target_year_6'];
  if($resource==='indicators') return ['project_target','baseline_value'];
  if($resource==='projects')   return ['target_beneficiaries'];
  return [];
}
/* 📌 PROJECT-RESTRICTED USERS — Users & Access can pin a data-entry user to
   specific project(s) (users.assigned_projects, comma-separated business IDs).
   Returns NULL for "all projects" (admins, root, or no restriction set);
   otherwise the array of allowed project IDs. Read fresh from the DB so an
   admin's change applies on the user's very next request — no re-login needed. */
function assigned_projects(){
  static $cached=false,$val=null;
  if($cached) return $val;
  $cached=true;
  $u=current_user(); if(!$u || is_admin()) return $val=null;
  try{
    if(!in_array('assigned_projects',table_columns('users'))) return $val=null;
    $st=db()->prepare("SELECT assigned_projects FROM users WHERE id=?"); $st->execute([$u['id']]);
    $raw=trim((string)(($st->fetch()['assigned_projects'])??''));
    if($raw==='') return $val=null;
    $list=array_values(array_filter(array_map('trim',explode(',',$raw))));
    return $val=($list?:null);
  }catch(Exception $e){ return $val=null; }
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
    out(['error'=>'Sign in needs a technical update before it can work. Please contact developer.','detail'=>safe_err($e,'Server error during login.')],500);
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
  if(!can_manage_users('e')) out(['error'=>'You do not have permission to manage users'],403);
  $b=body();
  $uid = (int)($b['user_id'] ?? 0);
  $newp = $b['new_password'] ?? '';
  if(!$uid || strlen($newp)<6) out(['error'=>'user_id and new_password (≥6 chars) are required'],400);
  $st=db()->prepare("SELECT id,username,is_root FROM users WHERE id=?"); $st->execute([$uid]);
  $target=$st->fetch();
  if(!$target) out(['error'=>'User not found'],404);
  $meU=current_user();
  if(!empty($target['is_root']) && empty($meU['is_root'])) out(['error'=>'Only a root admin can reset a root-protected account'],403);
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
    if(!can_manage_users('v')) out(['error'=>'You do not have permission to view user permissions'],403);
    $uid = (int)($_GET['user_id'] ?? 0);
    if(!$uid) out(['error'=>'user_id is required'],400);
    out(['user_id'=>$uid,'permissions'=>load_user_permissions($uid)]);
  }
  if($method==='POST'){
    if(!can_manage_users('e')) out(['error'=>'You do not have permission to change user permissions'],403);
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
      db()->rollBack(); out(['error'=>'Could not save permissions','detail'=>safe_err($e)],500);
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
  // 🎯 Target lock — importing rows that carry targets needs the Targets grant too
  if(in_array($bres,['activities','indicators','hq_targets']) && !can_edit_targets())
    out(['error'=>'Targets are locked — only target-setters (HQ) can import '.$bres.'. Ask your admin to tick 🎯 Targets in Users & Access.'],403);
  $btable = $RES[$bres];
  $bcols  = table_columns($btable);
  $idCol  = isset($ID_GEN[$bres]) ? $ID_GEN[$bres][0] : null;
  $created=0; $errors=[]; $createdIds=[];
  // Same per-resource named lock as single saves — two simultaneous bulk imports
  // (or an import racing a manual save) can never generate colliding business IDs.
  $bulkLock='vmis_id_'.$bres;
  try{ db()->prepare("SELECT GET_LOCK(?,10)")->execute([$bulkLock]); }catch(Exception $e){ $bulkLock=null; }
  db()->beginTransaction();
  try{
    $apB=assigned_projects();
    foreach($brows as $idx=>$row){
      if(!is_array($row)){ $errors[]=['row'=>$idx+1,'error'=>'Invalid row payload']; continue; }
      // 📌 Project-restricted user: imported rows must belong to their project(s)
      if($apB && !empty($row['project_id']) && !in_array((string)$row['project_id'],$apB,true)){
        $errors[]=['row'=>$idx+1,'error'=>'Project '.$row['project_id'].' is not assigned to you']; continue;
      }
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
      // stamp the importer's username on bulk-imported rows too
      $mu=current_user();
      if($mu && in_array('created_by',$bcols)) $row['created_by']=$mu['username'];
      // imported beneficiaries with a blank registration date get today (IST)
      if($bres==='beneficiaries' && empty($row['registration_date'])) $row['registration_date']=date('Y-m-d');
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
        $errors[]=['row'=>$idx+1,'error'=>safe_err($e,'Row rejected by the database.')];
      }
    }
    db()->commit();
    audit('bulk_import',$bres,'#'.$created,
      "Bulk-imported $created $bres (".count($errors)." error".(count($errors)===1?'':'s').")",
      null,
      ['created'=>$created,'errors'=>count($errors)]
    );
    if($bulkLock){ try{ db()->prepare("SELECT RELEASE_LOCK(?)")->execute([$bulkLock]); }catch(Exception $e){} }
    out(['ok'=>true,'created'=>$created,'errors'=>$errors,'ids'=>$createdIds]);
  }catch(Exception $e){
    db()->rollBack();
    if($bulkLock){ try{ db()->prepare("SELECT RELEASE_LOCK(?)")->execute([$bulkLock]); }catch(Exception $e2){} }
    out(['error'=>'Bulk import failed','detail'=>safe_err($e)],500);
  }
}

/* ════════════════ LOOKUP / OPTIONS ════════════════ */
if ($action==='options') {
  require_session();
  $pdo=db();
  $q=function($sql) use($pdo){ try{return $pdo->query($sql)->fetchAll();}catch(Exception $e){return [];} };
  $L=[
    'programmes'=>$q("SELECT programme_id id, programme_name name FROM programmes WHERE programme_id IS NOT NULL ORDER BY programme_name"),
    'projects'=>$q("SELECT project_id id, project_name name, programme_id FROM projects WHERE project_id IS NOT NULL ORDER BY project_name"),
    'donors'=>$q("SELECT donor_id id, donor_name name, reporting_frequency, donor_type, total_approved_budget_inr FROM donors WHERE donor_id IS NOT NULL ORDER BY donor_name"),
    /* donor → list of project IDs that donor funds (donor_mappings) — used to drive multi-project donor reports */
    'donor_projects'=>$q("SELECT m.donor_id, m.project_id, p.project_name, p.programme_id FROM donor_mappings m LEFT JOIN projects p ON p.project_id=m.project_id WHERE m.donor_id IS NOT NULL AND m.project_id IS NOT NULL GROUP BY m.donor_id, m.project_id"),
    'indicators'=>$q("SELECT indicator_id id, indicator_name name, programme_id, project_id FROM indicators WHERE indicator_id IS NOT NULL ORDER BY indicator_name"),
    'shgs'=>$q("SELECT shg_id id, shg_name name, programme_id, project_id, donor_id FROM shgs WHERE shg_id IS NOT NULL ORDER BY shg_name"),
    'geographies'=>$q("SELECT geography_id id, CONCAT_WS(' · ', NULLIF(village_or_ward,''), NULLIF(gram_panchayat,''), NULLIF(block,''), NULLIF(district,'')) name, state, district, block, gram_panchayat, village_or_ward village".(in_array('project_id',table_columns('geographies'))?", project_id":"")." FROM geographies WHERE geography_id IS NOT NULL AND geography_id<>'' ORDER BY district, block, gram_panchayat, village_or_ward"),
    /* staff — for assigning HQ work units to field officers (username is the stable key) */
    'staff'=>$q("SELECT username id, COALESCE(NULLIF(user_name,''),username) name, role FROM users WHERE username IS NOT NULL AND username<>'' ORDER BY name"),
  ];
  // 📌 Project-restricted user → every project-linked picker narrows to their projects
  // (places/indicators without a project stay visible — they are the shared pool)
  $ap=assigned_projects();
  if($ap){
    $inAp=function($pid) use($ap){ return in_array((string)$pid,$ap,true); };
    $L['projects']=array_values(array_filter($L['projects'],function($r) use($inAp){ return $inAp($r['id']); }));
    foreach(['geographies','indicators','shgs'] as $lk){
      $L[$lk]=array_values(array_filter($L[$lk],function($r) use($inAp){ return empty($r['project_id']) || $inAp($r['project_id']); }));
    }
    $L['donor_projects']=array_values(array_filter($L['donor_projects'],function($r) use($inAp){ return $inAp($r['project_id']); }));
  }
  out($L);
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

/* ════════════════════════════════════════════════════════════════
   THE 11 LOGGED INDICATORS — fixed by the client, computed AUTOMATICALLY
   from the data already entered (nothing is typed by hand). Formulas are
   exactly the ones in the client's indicator sheet. Filter-aware
   (thematic / project / donor / district) and project-restriction-aware.
   Any indicator whose source data is not yet entered returns null and the
   UI says so — a number is never invented.
   ════════════════════════════════════════════════════════════════ */
if ($action==='logged_indicators') {
  require_session();
  $pdo=db();
  $p=[];
  foreach(['programme_id','project_id','donor_id','district','block'] as $k){ if(!empty($_GET[$k])) $p[$k]=$_GET[$k]; }
  $ap=assigned_projects();
  $W=function($table) use($p,$ap){
    try{
      $cols=table_columns($table); $cl=[]; $vals=[];
      foreach($p as $k=>$v){ if(in_array($k,$cols)){ $cl[]="`$k`=?"; $vals[]=$v; } }
      if($ap && in_array('project_id',$cols)){
        $ph=implode(',',array_fill(0,count($ap),'?'));
        $cl[]="(project_id IN ($ph) OR project_id IS NULL OR TRIM(project_id)='')";
        foreach($ap as $apv) $vals[]=$apv;
      }
      return $cl? [' WHERE '.implode(' AND ',$cl), $vals] : ['',[]];
    }catch(Exception $e){ return ['',[]]; }
  };
  $one=function($sql,$v) use($pdo){ try{ $st=$pdo->prepare($sql); $st->execute($v); $r=$st->fetch(); return $r!==false ? (float)array_values($r)[0] : null; }catch(Exception $e){ return null; } };
  $AND=function($w){ return $w? ($w.' AND ') : ' WHERE '; };
  [$wB,$vB]=$W('beneficiaries'); [$wC,$vC]=$W('crops'); [$wG,$vG]=$W('gram_sabha');
  [$wV,$vV]=$W('convergence');   [$wS,$vS]=$W('shgs');  [$wL,$vL]=$W('loans');
  try{ $benCols=table_columns('beneficiaries'); }catch(Exception $e){ $benCols=[]; }
  try{ $cropCols=table_columns('crops'); }catch(Exception $e){ $cropCols=[]; }
  $pct=function($num,$den){ return ($num===null||$den===null||$den==0)? null : round($num/$den*1000)/10; };
  $NPx="COALESCE(net_profit_inr,income_inr)";
  $IND=[];

  /* 1 · % increase in annual household income
     (Avg current NET income/yr − Avg baseline NET income) / Avg baseline × 100 — matched pairs */
  { $v=null;$b=null;$c=null;$n=0;
    try{
      $blMap=['bl_paddy_income','bl_millet_income','bl_vegetable_income','bl_tuber_income','bl_pulses_income','bl_oilseed_income','bl_mushroom_income','bl_goat_income','bl_poultry_income','bl_micro_enterprise_income','bl_other_income'];
      $have=array_values(array_intersect($blMap,$benCols));
      if($have){
        $parts=[]; foreach($have as $ic){ $ec=str_replace('_income','_expenditure',$ic);
          $parts[]=in_array($ec,$benCols)?"(COALESCE($ic,0)-COALESCE($ec,0))":"COALESCE($ic,0)"; }
        $blExpr='('.implode('+',$parts).')';
        $stP=$pdo->prepare("SELECT beneficiary_id bid, COALESCE(SUM($NPx),0) cur, COUNT(DISTINCT NULLIF(TRIM(project_year),'')) ny FROM crops".$AND($wC)."beneficiary_id IS NOT NULL AND TRIM(beneficiary_id)<>'' GROUP BY beneficiary_id");
        $stP->execute($vC); $curBy=[];
        foreach($stP->fetchAll() as $r){ $curBy[$r['bid']]=((float)$r['cur'])/max(1,(int)$r['ny']); }
        if($curBy){
          $ids=array_keys($curBy); $ph=implode(',',array_fill(0,count($ids),'?'));
          $blPer="CASE WHEN $blExpr<>0 THEN $blExpr ELSE COALESCE(current_income_per_annum_inr,0) END";
          $wBp=$wB?($wB." AND beneficiary_id IN ($ph)"):(" WHERE beneficiary_id IN ($ph)");
          $stB=$pdo->prepare("SELECT beneficiary_id bid, $blPer bl FROM beneficiaries$wBp");
          $stB->execute(array_merge($vB,$ids));
          $sB=0;$sC=0;
          foreach($stB->fetchAll() as $r){ $n++; $sB+=(float)$r['bl']; $sC+=$curBy[$r['bid']]??0; }
          if($n){ $b=$sB/$n; $c=$sC/$n; if(abs($b)>0.004) $v=round(($c-$b)/abs($b)*1000)/10; }
        }
      }
    }catch(Exception $e){}
    $IND[]=['n'=>1,'name'=>'Percentage increase in annual household income','unit'=>'%','value'=>$v,
      'before'=>$b===null?null:round($b),'after'=>$c===null?null:round($c),'pairs'=>$n,
      'detail'=>$n?('Avg baseline NET ₹'.number_format(round($b)).' → avg current NET ₹'.number_format(round($c)).'/yr · '.$n.' beneficiaries with production logged'):'Needs beneficiaries with baseline income AND production records',
      'formula'=>'(Avg current household NET income/yr − Avg baseline NET income) ÷ Avg baseline × 100'];
  }

  /* 2 · % households with food security throughout the year (Yes on production records) */
  { $y=null;$t=null;
    if(in_array('food_security',$cropCols)){
      $y=$one("SELECT COUNT(DISTINCT beneficiary_id) FROM crops".$AND($wC)."LOWER(TRIM(food_security))='yes'",$vC);
      $t=$one("SELECT COUNT(DISTINCT beneficiary_id) FROM crops".$AND($wC)."food_security IS NOT NULL AND TRIM(food_security)<>''",$vC);
    }
    $IND[]=['n'=>2,'name'=>'Percentage of households with improved food security','unit'=>'%','value'=>$pct($y,$t),
      'before'=>null,'after'=>$y,'pairs'=>$t===null?0:(int)$t,
      'detail'=>($t)?(((int)$y).' of '.((int)$t).' households answered YES to "food security throughout the year?"'):'Answer "Food security throughout the year?" on Production records to light this up',
      'formula'=>'Households answering YES ÷ households answering, from Production & Output records'];
  }

  /* 3 · % increase in agricultural productivity — paddy + millet KG, current vs baseline */
  { $cur=$one("SELECT COALESCE(SUM(production_kg),0) FROM crops".$AND($wC)."LOWER(TRIM(crop)) IN ('paddy','millet')",$vC);
    $base=null;
    $kgCols=array_values(array_intersect(['bl_paddy_kg','bl_millet_kg'],$benCols));
    if($kgCols){
      $base=$one("SELECT COALESCE(SUM(".implode('+',array_map(function($c){return "COALESCE($c,0)";},$kgCols))."),0) FROM beneficiaries$wB",$vB);
    }
    $v=($base!==null && $base>0 && $cur!==null)? round(($cur-$base)/$base*1000)/10 : null;
    $IND[]=['n'=>3,'name'=>'Percentage increase in agricultural productivity','unit'=>'%','value'=>$v,
      'before'=>$base,'after'=>$cur,'pairs'=>0,
      'detail'=>($base!==null&&$cur!==null)?('Baseline paddy+millet: '.number_format(round($base)).' kg → current: '.number_format(round($cur)).' kg'):'Needs baseline Production (kg) for Paddy & Millet on the Beneficiary form, and current Production records',
      'formula'=>'(Current paddy+millet production kg − Baseline paddy+millet kg) ÷ Baseline × 100'];
  }

  /* 4 · % increase in area under improved agricultural practices */
  { $cur=$one("SELECT COALESCE(SUM(area_acre),0) FROM crops$wC",$vC);
    $areaCols=array_values(array_intersect(['bl_paddy_area','bl_millet_area','bl_vegetable_area','bl_tuber_area','bl_pulses_area','bl_oilseed_area'],$benCols));
    $base=$areaCols? $one("SELECT COALESCE(SUM(".implode('+',array_map(function($c){return "COALESCE($c,0)";},$areaCols))."),0) FROM beneficiaries$wB",$vB) : null;
    $v=($base!==null && $base>0 && $cur!==null)? round(($cur-$base)/$base*1000)/10 : null;
    $IND[]=['n'=>4,'name'=>'Percentage increase in area under improved agricultural practices','unit'=>'%','value'=>$v,
      'before'=>$base,'after'=>$cur,'pairs'=>0,
      'detail'=>($base!==null&&$cur!==null)?('Baseline cultivated area: '.number_format(round($base,1),1).' acre → current: '.number_format(round($cur,1),1).' acre'):'Needs baseline crop areas and current Production records with Area',
      'formula'=>'(Total current cultivation area − Total baseline area) ÷ Baseline area × 100'];
  }

  /* 5 · % of farmers practicing organic / natural farming — growth vs baseline */
  { $b=in_array('organic_farming',$benCols)? $one("SELECT COUNT(*) FROM beneficiaries".$AND($wB)."LOWER(TRIM(organic_farming))='yes'",$vB) : null;
    $c=in_array('organic_farming',$cropCols)? $one("SELECT COUNT(DISTINCT beneficiary_id) FROM crops".$AND($wC)."LOWER(TRIM(organic_farming))='yes'",$vC) : null;
    $v=($b!==null && $b>0 && $c!==null)? round(($c-$b)/$b*1000)/10 : null;
    $IND[]=['n'=>5,'name'=>'Percentage of farmers practicing organic or natural farming','unit'=>'%','value'=>$v,
      'before'=>$b,'after'=>$c,'pairs'=>0,
      'detail'=>($b!==null||$c!==null)?('Organic at baseline: '.(int)$b.' households → organic now (production records): '.(int)$c):'Answer Organic Farming (Yes/No) on the Beneficiary and Production forms',
      'formula'=>'(Households organic now − households organic at baseline) ÷ baseline households × 100'];
  }

  /* 6 · % households with diversified livelihood sources — ≥ 3 crops cultivated */
  { $y=$one("SELECT COUNT(*) FROM (SELECT beneficiary_id FROM crops".$AND($wC)."TRIM(COALESCE(crop,''))<>'' AND beneficiary_id IS NOT NULL AND TRIM(beneficiary_id)<>'' GROUP BY beneficiary_id HAVING COUNT(DISTINCT LOWER(TRIM(crop)))>=3) t",$vC);
    $t=$one("SELECT COUNT(DISTINCT beneficiary_id) FROM crops".$AND($wC)."beneficiary_id IS NOT NULL AND TRIM(beneficiary_id)<>''",$vC);
    $IND[]=['n'=>6,'name'=>'Percentage of households with diversified livelihood sources','unit'=>'%','value'=>$pct($y,$t),
      'before'=>null,'after'=>$y,'pairs'=>$t===null?0:(int)$t,
      'detail'=>($t)?(((int)$y).' of '.((int)$t).' producing households cultivate 3 or more different crops'):'Computed from Production records (households with ≥3 distinct crops)',
      'formula'=>'Households cultivating ≥ 3 crops ÷ households with production records × 100'];
  }

  /* 7 · % households adopting alternative livelihoods
        A household counts when it has a NON-FARM livelihood anywhere: a production
        record in Goat Rearing, Backyard Poultry, Micro Enterprise or Other Income,
        or baseline income under one of those, or the older free-typed column. All
        three are read, so the figure does not depend on which one was filled in. */
  { $nonFarm = liv_nonfarm_keys();
    $cropCond = '('.liv_key_sql()." IN ('".implode("','", $nonFarm)."'))";
    $blCols=[]; foreach(['bl_goat_income','bl_poultry_income','bl_micro_enterprise_income','bl_other_income'] as $c){ if(in_array($c,$benCols)) $blCols[]="COALESCE($c,0)>0"; }
    $altCond="(alternative_livelihood IS NOT NULL AND LOWER(TRIM(alternative_livelihood)) NOT IN ('','none'))";
    $benCond = $blCols ? '('.$altCond.' OR '.implode(' OR ',$blCols).')' : $altCond;
    $y=$one("SELECT COUNT(*) FROM (SELECT beneficiary_id FROM beneficiaries".$AND($wB).$benCond." UNION SELECT beneficiary_id FROM crops".$AND($wC).$cropCond." AND beneficiary_id IS NOT NULL AND TRIM(beneficiary_id)<>'') t",array_merge($vB,$vC));
    $t=$one("SELECT COUNT(*) FROM beneficiaries$wB",$vB);
    $IND[]=['n'=>7,'name'=>'Percentage of households adopting alternative livelihoods','unit'=>'%','value'=>$pct($y,$t),
      'before'=>null,'after'=>$y,'pairs'=>$t===null?0:(int)$t,
      'detail'=>($t)?(((int)$y).' of '.((int)$t).' households have a non-farm livelihood: goat rearing, backyard poultry, micro enterprise or other income'):'Counted from the non-farm rows of the beneficiary baseline table and from Production & Output records in those categories',
      'formula'=>'Households with a non-farm livelihood (baseline or production) ÷ total households × 100'];
  }

  /* 8 · % SHG members accessing formal financial services — members of SHGs with loans */
  { $y=$one("SELECT COALESCE(SUM(no_of_members),0) FROM shgs".$AND($wS)."shg_id IN (SELECT DISTINCT shg_id FROM loans".$AND($wL)."shg_id IS NOT NULL AND TRIM(shg_id)<>'')",array_merge($vS,$vL));
    $t=$one("SELECT COALESCE(SUM(no_of_members),0) FROM shgs$wS",$vS);
    $IND[]=['n'=>8,'name'=>'Percentage of SHG members accessing formal financial services','unit'=>'%','value'=>$pct($y,$t),
      'before'=>null,'after'=>$y,'pairs'=>$t===null?0:(int)$t,
      'detail'=>($t)?(number_format((int)$y).' of '.number_format((int)$t).' SHG members are in groups that have received a loan'):'Computed from SHGs and SHG Loans',
      'formula'=>'Members of SHGs that received loans ÷ all SHG members × 100'];
  }

  /* 9 · % women participating in local governance — from Gram Sabha records */
  { $f=$one("SELECT COALESCE(SUM(female_participants),0) FROM gram_sabha$wG",$vG);
    $t=$one("SELECT COALESCE(SUM(total_participants),0) FROM gram_sabha$wG",$vG);
    $m=$one("SELECT COUNT(*) FROM gram_sabha$wG",$vG);
    $IND[]=['n'=>9,'name'=>'Percentage of women participating in local governance','unit'=>'%','value'=>$pct($f,$t),
      'before'=>null,'after'=>$f,'pairs'=>$t===null?0:(int)$t,
      'detail'=>($t!==null&&$t>0)?(number_format((int)$f).' women among '.number_format((int)$t).' Gram Sabha participants · '.((int)$m).' meetings recorded'):'Enter Gram Sabha / VDC records in the Local Governance section',
      'formula'=>'Female Gram Sabha participants ÷ total participants × 100'];
  }

  /* 10 · % households accessing government schemes — from Convergence */
  { $y=$one("SELECT COALESCE(SUM(hh_benefited),0) FROM convergence$wV",$vV);
    $t=$one("SELECT COUNT(*) FROM beneficiaries$wB",$vB);
    $IND[]=['n'=>10,'name'=>'Percentage of households accessing government schemes and social protection','unit'=>'%','value'=>$pct($y,$t),
      'before'=>null,'after'=>$y,'pairs'=>$t===null?0:(int)$t,
      'detail'=>($t)?(number_format((int)$y).' household benefits recorded in Convergence · '.number_format((int)$t).' beneficiaries in scope'):'Enter Convergence records (Local Governance section) to light this up',
      'formula'=>'Households benefited (Convergence) ÷ total households × 100'];
  }

  /* 11 · Amount leveraged through government schemes (₹) — from Convergence */
  { $amt=$one("SELECT COALESCE(SUM(amount_mobilised),0) FROM convergence$wV",$vV);
    $cnt=$one("SELECT COUNT(*) FROM convergence$wV",$vV);
    $IND[]=['n'=>11,'name'=>'Amount leveraged through government schemes and programmes','unit'=>'₹','value'=>$amt,
      'before'=>null,'after'=>$amt,'pairs'=>$cnt===null?0:(int)$cnt,
      'detail'=>($cnt!==null&&$cnt>0)?('Across '.((int)$cnt).' convergence record'.($cnt==1?'':'s')):'Total of "Amount Mobilised" from the Convergence section',
      'formula'=>'Σ Amount Mobilised, Convergence section'];
  }

  out(['indicators'=>$IND,'generated_at'=>ist_now().' IST']);
}

/* ════════════════ AUDIT TRAIL ════════════════ */
/* ════════════════ MIS STATUS — live officer presence ════════════════ */
if ($action==='staff_status') {
  require_session();
  if(!can_view_section('misstatus')) out(['error'=>'You do not have permission to view officer status'],403);
  try{
    $rows=db()->query("SELECT username, COALESCE(NULLIF(user_name,''),username) name, role, last_seen, last_login,
        (active_session_token IS NOT NULL AND session_expires_at IS NOT NULL AND session_expires_at>NOW()) online
      FROM users WHERE username IS NOT NULL AND username<>'' ORDER BY name")->fetchAll();
    out(['rows'=>$rows]);
  }catch(Exception $e){ out(['rows'=>[]]); }
}

if ($action==='audit') {
  require_session();
  if(!can_view_section('audit')) out(['error'=>'You do not have permission to view the audit trail'],403);
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
  // crop / season / project_year join the list so the Income Impact banner and every
  // KPI follow those filters too (each is applied ONLY to tables that have the column,
  // so e.g. season narrows production while leaving the beneficiary counts alone)
  foreach(['programme_id','project_id','donor_id','geography_id','district','block','shg_id','crop','season','project_year'] as $k){
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
    // NET is what a household actually keeps, so every income comparison uses it.
    // Rows entered before Net Profit existed fall back to their gross income.
    'crop_income'=>(function() use($pdo,$whereFor){
      [$w,$v]=$whereFor('crops');
      $st=$pdo->prepare("SELECT COALESCE(SUM(COALESCE(net_profit_inr,income_inr)),0) s FROM crops$w"); $st->execute($v);
      return (float)$st->fetch()['s'];
    })(),
    'crop_income_gross'=>$sum('crops','income_inr'),
  ];
  // Distinct Districts & Blocks from the Geography master (filter-aware).
  // Built explicitly (NOT via whereFor): a chosen Project WINS over the auto-selected
  // Thematic Area — a place's own programme column may be empty/stale, and requiring
  // BOTH made project-assigned geographies vanish from the counts.
  // A Donor filter reaches geographies through the chain: donor → funded projects → places.
  try{
    $gCols=table_columns('geographies'); $gw=[]; $vG=[];
    if(!empty($p['project_id']) && in_array('project_id',$gCols)){ $gw[]="`project_id`=?"; $vG[]=$p['project_id']; }
    elseif(!empty($p['programme_id']) && in_array('programme_id',$gCols)){ $gw[]="`programme_id`=?"; $vG[]=$p['programme_id']; }
    if(!empty($p['donor_id']) && in_array('project_id',$gCols)){
      $gw[]="`project_id` IN (SELECT project_id FROM donor_mappings WHERE donor_id=?)"; $vG[]=$p['donor_id'];
    }
    $wG = $gw ? (' WHERE '.implode(' AND ',$gw)) : '';
    $dq=$pdo->prepare("SELECT COUNT(DISTINCT NULLIF(TRIM(district),'')) c FROM geographies$wG"); $dq->execute($vG); $kpi['districts']=(int)$dq->fetch()['c'];
    $bq=$pdo->prepare("SELECT COUNT(DISTINCT NULLIF(TRIM(block),'')) c FROM geographies$wG"); $bq->execute($vG); $kpi['blocks']=(int)$bq->fetch()['c'];
    if($gw){
      // A chain filter is active → Villages follows the same geography set as
      // Districts & Blocks (unfiltered keeps the Village Demographics count).
      $vq=$pdo->prepare("SELECT COUNT(DISTINCT NULLIF(TRIM(village_or_ward),'')) c FROM geographies$wG"); $vq->execute($vG);
      $kpi['villages']=(int)$vq->fetch()['c'];
    }
  }catch(Exception $e){ $kpi['districts']=0; $kpi['blocks']=0; }
  // ── Thematic Areas & Projects KPIs must follow the Donor / Project filters through
  //    the mapping chain — their own tables have no donor_id column, so $whereFor
  //    skipped the donor and the cards kept showing ALL projects/themes.
  try{
    if(!empty($p['project_id'])){
      $st=$pdo->prepare("SELECT COUNT(*) c, COUNT(DISTINCT NULLIF(TRIM(programme_id),'')) pg FROM projects WHERE project_id=?");
      $st->execute([$p['project_id']]); $r=$st->fetch();
      $kpi['projects']=(int)$r['c']; $kpi['programmes']=(int)$r['pg'];
    } elseif(!empty($p['donor_id'])){
      $joinProg=!empty($p['programme_id']);
      $vals=$joinProg? [$p['donor_id'],$p['programme_id']] : [$p['donor_id']];
      $sqlPj="SELECT COUNT(DISTINCT m.project_id) c FROM donor_mappings m"
            .($joinProg?" JOIN projects pr ON pr.project_id=m.project_id":"")
            ." WHERE m.donor_id=? AND m.project_id IS NOT NULL AND m.project_id<>''"
            .($joinProg?" AND pr.programme_id=?":"");
      $st=$pdo->prepare($sqlPj); $st->execute($vals); $kpi['projects']=(int)$st->fetch()['c'];
      $sqlPg="SELECT COUNT(DISTINCT NULLIF(TRIM(pr.programme_id),'')) c FROM donor_mappings m JOIN projects pr ON pr.project_id=m.project_id WHERE m.donor_id=?"
            .($joinProg?" AND pr.programme_id=?":"");
      $st=$pdo->prepare($sqlPg); $st->execute($vals); $kpi['programmes']=(int)$st->fetch()['c'];
    }
  }catch(Exception $e){}
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
  // Follows the dashboard filters — picking a donor / project / thematic area narrows
  // this panel to exactly that funding relationship (it used to always show everything).
  $donorFunded=[];
  try{
    $dmW=["m.donor_id IS NOT NULL"]; $dmP=[];
    if(!empty($p['donor_id']))   { $dmW[]="m.donor_id=?";     $dmP[]=$p['donor_id']; }
    if(!empty($p['project_id'])) { $dmW[]="m.project_id=?";   $dmP[]=$p['project_id']; }
    if(!empty($p['programme_id'])){ $dmW[]="p.programme_id=?"; $dmP[]=$p['programme_id']; }
    $stDM=$pdo->prepare("SELECT m.donor_id, d.donor_name, m.project_id, p.project_name FROM donor_mappings m LEFT JOIN donors d ON d.donor_id=m.donor_id LEFT JOIN projects p ON p.project_id=m.project_id WHERE ".implode(' AND ',$dmW)." ORDER BY d.donor_name, p.project_name");
    $stDM->execute($dmP); $dm=$stDM->fetchAll();
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
  // Every baseline source THIS database has (older DBs simply have fewer — nothing breaks)
  $blActCols = [ 'paddy'=>'bl_paddy_income','millet'=>'bl_millet_income','vegetable'=>'bl_vegetable_income',
                 'tuber'=>'bl_tuber_income','pulses'=>'bl_pulses_income','oilseed'=>'bl_oilseed_income',
                 'mushroom'=>'bl_mushroom_income','goat'=>'bl_goat_income','poultry'=>'bl_poultry_income',
                 'micro'=>'bl_micro_enterprise_income','other'=>'bl_other_income' ];
  $blActCols = array_filter($blActCols, fn($c)=>in_array($c,$benCols));
  // Baseline NET = income − expenditure per source (expenditure columns arrive with
  // upgrade13; where a database or a row has none, net simply equals gross income).
  $blExpCols = [];
  foreach($blActCols as $a=>$c){ $ec=str_replace('_income','_expenditure',$c); if(in_array($ec,$benCols)) $blExpCols[$a]=$ec; }
  $blNetOf = function($a) use($blActCols,$blExpCols){
    $inc="COALESCE({$blActCols[$a]},0)";
    return isset($blExpCols[$a]) ? "($inc-COALESCE({$blExpCols[$a]},0))" : $inc;
  };
  $hasBL   = in_array('bl_paddy_income', $benCols);
  [$wB,$vB] = $whereFor('beneficiaries');
  [$wC,$vC] = $whereFor('crops');
  $acts = array_keys($blActCols); if(!$acts) $acts=['paddy','millet','vegetable','mushroom','goat','poultry','micro'];
  $blByAct = array_fill_keys($acts, 0.0);
  if($hasBL){
    $blExpr = '('.implode('+',array_map($blNetOf,$acts)).')';
    $stB = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN $blExpr>0 THEN $blExpr ELSE COALESCE(current_income_per_annum_inr,0) END),0) s FROM beneficiaries$wB");
    $stB->execute($vB); $kpi['baseline_income'] = (float)$stB->fetch()['s'];
    $sel=[]; foreach($acts as $a){ $sel[]="COALESCE(SUM(".$blNetOf($a)."),0) `$a`"; }
    $stBA = $pdo->prepare("SELECT ".implode(', ',$sel)." FROM beneficiaries$wB");
    $stBA->execute($vB); $r = $stBA->fetch();
    foreach($acts as $a){ $blByAct[$a] = (float)($r[$a]??0); }
  } else {
    $stB = $pdo->prepare("SELECT COALESCE(SUM(COALESCE(current_income_per_annum_inr,0)),0) s FROM beneficiaries$wB");
    $stB->execute($vB); $kpi['baseline_income'] = (float)$stB->fetch()['s'];
  }
  // Current side also uses NET (falling back to gross for rows entered before Net Profit)
  // and buckets by the SAME eleven categories as the baseline, through liv_sql(), so
  // the before and after columns of every chart line up by construction.
  $NP="COALESCE(net_profit_inr,income_inr)";
  $curKeyFor = ['paddy'=>'paddy','millet'=>'millet','vegetable'=>'vegetable','tuber'=>'tuber',
                'pulses'=>'pulses','oilseed'=>'oilseed','mushroom'=>'mushroom','goat'=>'goat',
                'poultry'=>'poultry','micro'=>'micro_enterprise'];
  $selC = ["COALESCE(SUM($NP),0) total"];
  foreach($curKeyFor as $alias=>$livKey){ $selC[] = "COALESCE(SUM(CASE WHEN ".liv_sql($livKey)." THEN $NP ELSE 0 END),0) `$alias`"; }
  $stCA = $pdo->prepare("SELECT ".implode(",\n      ",$selC)." FROM crops$wC");
  $stCA->execute($vC); $cur = $stCA->fetch();
  $curByAct = array_fill_keys($acts, 0.0);
  foreach($acts as $a){ if($a!=='other') $curByAct[$a] = (float)($cur[$a]??0); }
  // "Other" current income = everything not captured by a named bucket above
  if(array_key_exists('other',$curByAct)){
    $known=0.0; foreach($curByAct as $a=>$v){ if($a!=='other') $known+=$v; }
    $curByAct['other']=max(0.0,(float)($cur['total']??0)-$known);
  }
  $income_compare = ['baseline'=>$blByAct, 'current'=>$curByAct];

  // ── Matched-pairs income — compare ONLY beneficiaries whose current (production)
  //    income is actually logged. Comparing everyone's baseline against a handful of
  //    production records made the headline read "−99.99%" while entry was ramping up.
  $income_pairs=['n'=>0,'baseline'=>0.0,'current'=>0.0];
  try{
    $wCp0 = $wC ? ($wC.' AND ') : ' WHERE ';
    $stPB=$pdo->prepare("SELECT beneficiary_id bid, COALESCE(SUM(COALESCE(net_profit_inr,income_inr)),0) cur FROM crops".$wCp0." beneficiary_id IS NOT NULL AND TRIM(beneficiary_id)<>'' GROUP BY beneficiary_id");
    $stPB->execute($vC);
    $curBy=[]; foreach($stPB->fetchAll() as $r){ $curBy[$r['bid']]=(float)$r['cur']; }
    if($curBy){
      $ids=array_keys($curBy); $ph=implode(',',array_fill(0,count($ids),'?'));
      $blPer = $hasBL ? "CASE WHEN $blExpr>0 THEN $blExpr ELSE COALESCE(current_income_per_annum_inr,0) END" : "COALESCE(current_income_per_annum_inr,0)";
      $wBp = $wB ? ($wB." AND beneficiary_id IN ($ph)") : (" WHERE beneficiary_id IN ($ph)");
      $stBP=$pdo->prepare("SELECT beneficiary_id bid, $blPer bl FROM beneficiaries$wBp");
      $stBP->execute(array_merge($vB,$ids));
      foreach($stBP->fetchAll() as $r){
        $income_pairs['n']++;
        $income_pairs['baseline'] += (float)$r['bl'];
        $income_pairs['current']  += $curBy[$r['bid']] ?? 0;
      }
    }
  }catch(Exception $e){}

  // How many distinct project years the filtered production covers. The baseline is an
  // ANNUAL income figure, so every "now" comparison divides by this — otherwise Year-3
  // would read as Y1+Y2+Y3 stacked against a single year's baseline (false growth).
  $kpi['income_years']=1;
  try{
    $wCy = $wC ? ($wC.' AND ') : ' WHERE ';
    $stNY=$pdo->prepare("SELECT COUNT(DISTINCT NULLIF(TRIM(project_year),'')) n FROM crops".$wCy."income_inr IS NOT NULL");
    $stNY->execute($vC); $kpi['income_years']=max(1,(int)$stNY->fetch()['n']);
  }catch(Exception $e){}

  // ── Income over time — current income per project year (trend), filter-aware ──
  $income_by_year=[];
  try{
    $stY=$pdo->prepare("SELECT COALESCE(NULLIF(TRIM(project_year),''),'Unspecified') y, COALESCE(SUM(COALESCE(net_profit_inr,income_inr)),0) s FROM crops$wC GROUP BY y ORDER BY y");
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
    'income_pairs'=>$income_pairs,
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

  // 1) Beneficiaries breakdown — additionally scoped by the Duration date window
  //    (registration_date), when a from/to is supplied. Stored as YYYY-MM-DD text,
  //    so LEFT(...,10) makes the string comparison safe.
  [$wB,$pB]=$applyFilter('beneficiaries');
  if(($from||$to) && in_array('registration_date',table_columns('beneficiaries'))){
    $dc=[]; if($from){$dc[]="LEFT(`beneficiaries`.`registration_date`,10)>=?"; $pB[]=$from;} if($to){$dc[]="LEFT(`beneficiaries`.`registration_date`,10)<=?"; $pB[]=$to;}
    if($dc){ $wB .= ($wB?' AND ':' WHERE ').implode(' AND ',$dc); }
  }
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

  // 6) Outputs grouped by the ELEVEN shared livelihood categories, so the report
  //    never lists Goatery and Goat Rearing as two different things.
  [$wC,$pC]=$applyFilter('crops');
  $kExpr = liv_label_sql();
  $st=$pdo->prepare("SELECT $kExpr k,
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

  /* ── ORGANIC FARMING — before (at registration) vs now (from production records) ──
     "How many practised it before, and how many practise it now" is a headline the
     donor asks for, so it is counted here, filter-aware, in one place.            */
  $organic=['before_yes'=>0,'before_no'=>0,'before_total'=>0,'now_yes'=>0,'now_total'=>0];
  try{
    if(in_array('organic_farming',table_columns('beneficiaries'))){
      $stO=$pdo->prepare("SELECT COALESCE(NULLIF(TRIM(organic_farming),''),'—') k, COUNT(*) c FROM `beneficiaries`$wB GROUP BY k");
      $stO->execute($pB);
      foreach($stO->fetchAll() as $r){
        $organic['before_total']+=(int)$r['c'];
        if(strcasecmp($r['k'],'Yes')===0) $organic['before_yes']=(int)$r['c'];
        elseif(strcasecmp($r['k'],'No')===0) $organic['before_no']=(int)$r['c'];
      }
    }
    if(in_array('organic_farming',table_columns('crops'))){
      $wCo = $wC ? ($wC.' AND ') : ' WHERE ';
      $stN=$pdo->prepare("SELECT COUNT(DISTINCT beneficiary_id) c FROM `crops`".$wCo."LOWER(TRIM(organic_farming))='yes'");
      $stN->execute($pC); $organic['now_yes']=(int)$stN->fetch()['c'];
      $stT=$pdo->prepare("SELECT COUNT(DISTINCT beneficiary_id) c FROM `crops`".$wCo."beneficiary_id IS NOT NULL AND TRIM(beneficiary_id)<>''");
      $stT->execute($pC); $organic['now_total']=(int)$stT->fetch()['c'];
    }
  }catch(Exception $e){}

  /* ── LOCAL GOVERNANCE for the report workbook ──
     Gram Sabha participation and Convergence (government schemes leveraged),
     both filter aware, with the row detail and a summary. */
  $gsRows=[]; $gsSum=['meetings'=>0,'total'=>0,'male'=>0,'female'=>0,'vdp_yes'=>0];
  try{
    [$wG,$pG]=$applyFilter('gram_sabha');
    $st=$pdo->prepare("SELECT district,block,gram_panchayat,village,vdc_name,meeting_date,
        COALESCE(total_participants,0) total_participants, COALESCE(male_participants,0) male_participants,
        COALESCE(female_participants,0) female_participants, vdp_submitted, remarks
      FROM `gram_sabha`$wG ORDER BY district, block, village");
    $st->execute($pG); $gsRows=$st->fetchAll();
    foreach($gsRows as $g){
      $gsSum['meetings']++;
      $gsSum['total']+=(int)$g['total_participants'];
      $gsSum['male']+=(int)$g['male_participants'];
      $gsSum['female']+=(int)$g['female_participants'];
      if(strcasecmp((string)$g['vdp_submitted'],'Yes')===0) $gsSum['vdp_yes']++;
    }
  }catch(Exception $e){}

  $cvRows=[]; $cvSum=['records'=>0,'hh'=>0,'amount'=>0.0]; $cvByDept=[];
  try{
    [$wV,$pV]=$applyFilter('convergence');
    $st=$pdo->prepare("SELECT district,block,gram_panchayat,village,department,scheme_name,work_type,
        COALESCE(hh_benefited,0) hh_benefited, COALESCE(amount_mobilised,0) amount_mobilised, remarks
      FROM `convergence`$wV ORDER BY department, scheme_name");
    $st->execute($pV); $cvRows=$st->fetchAll();
    foreach($cvRows as $c){
      $cvSum['records']++;
      $cvSum['hh']+=(int)$c['hh_benefited'];
      $cvSum['amount']+=(float)$c['amount_mobilised'];
      $d=trim((string)$c['department']) ?: 'Not stated';
      if(!isset($cvByDept[$d])) $cvByDept[$d]=['department'=>$d,'schemes'=>[],'hh'=>0,'amount'=>0.0,'records'=>0];
      $cvByDept[$d]['records']++;
      $cvByDept[$d]['hh']+=(int)$c['hh_benefited'];
      $cvByDept[$d]['amount']+=(float)$c['amount_mobilised'];
      $sn=trim((string)$c['scheme_name']); if($sn && !in_array($sn,$cvByDept[$d]['schemes'])) $cvByDept[$d]['schemes'][]=$sn;
    }
    foreach($cvByDept as $k=>$v){ $cvByDept[$k]['scheme_count']=count($v['schemes']); $cvByDept[$k]['schemes']=implode(', ',$v['schemes']); }
    $cvByDept=array_values($cvByDept);
    usort($cvByDept, function($a,$b){ return $b['amount'] <=> $a['amount']; });
  }catch(Exception $e){}

  out([
    'period'=>$period,
    'gram_sabha'=>$gsRows,'gram_sabha_summary'=>$gsSum,
    'convergence'=>$cvRows,'convergence_summary'=>$cvSum,'convergence_by_dept'=>$cvByDept,
    'filters'=>['donor_id'=>$donor,'programme_id'=>$prog,'project_id'=>$proj,'from'=>$from,'to'=>$to],
    'generated_at'=>ist_now().' IST',
    'organic'=>$organic,
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
      // Grouping Production & Output by livelihood groups by CATEGORY, so an older
      // "Goatery" row sits with "Goat Rearing" instead of forming a bar of its own.
      $kExprCh = ($src==='crops' && $gb==='crop')
        ? liv_label_sql()
        : "COALESCE(NULLIF(TRIM(`$gb`),''),'—')";
      $sql="SELECT $kExprCh k, $valExpr v FROM `$tbl` GROUP BY k ORDER BY v DESC LIMIT 25";
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
  if($resource==='users' && !can_manage_users('v')) out(['error'=>'You do not have permission to view users'],403);
  // Officers may read their OWN workspaces; the all-officers view needs the MIS Status grant
  if($resource==='workspaces'){
    $wu=current_user();
    $own = $wu && isset($_GET['username']) && strcasecmp((string)$_GET['username'], (string)$wu['username'])===0;
    if(!$own && !can_view_section('misstatus')) out(['error'=>'You do not have permission to view all workspaces'],403);
  }
  $cols=table_columns($table);
  $ownIdCol = isset($ID_GEN[$resource]) ? $ID_GEN[$resource][0] : 'id';
  $where=[]; $params=[];
  // search accumulators: strict (own columns) and broad (own columns + linked names)
  $sWhere=[]; $sParams=[]; $mWhere=[]; $mParams=[]; $bWhere=[]; $bParams=[]; $firstTerm=null; $usedTerms=0;
  foreach($_GET as $k=>$v){
    if(in_array($k,['resource','id','q','limit','offset','action'])) continue;
    if(in_array($k,['_v'])) continue;   // cache-buster raised on every write, never a filter
    /* The Livelihood filter on Production & Output matches by CATEGORY, not by the exact
       letters stored. Choosing "Goat Rearing" therefore also finds rows an officer once
       saved as "Goatery", and choosing "Other Income" finds the retired categories that
       are now counted there. Without this, filtering would silently hide older records. */
    if($k==='crop' && $resource==='crops' && $v!==''){
      $key=null;
      foreach($LIVELIHOODS as $lk=>$d){ if(strcasecmp($d['label'],(string)$v)===0){ $key=$lk; break; } }
      if($key===null){ foreach($LIVELIHOODS as $lk=>$d){ if(in_array(strtolower(trim((string)$v)),$d['alias'],true)){ $key=$lk; break; } } }
      if($key!==null){ $where[]='('.liv_key_sql()." = '".$key."')"; continue; }
    }
    if(in_array($k,$cols) && $v!==''){ $where[]="`$k`=?"; $params[]=$v; }
  }
  // 📌 Project-restricted user → only rows of their project(s); rows without any
  // project (shared master data, unassigned places) stay visible.
  $ap=assigned_projects();
  if($ap && in_array('project_id',$cols) && $resource!=='users'){
    $ph=implode(',',array_fill(0,count($ap),'?'));
    $where[]="(project_id IN ($ph) OR project_id IS NULL OR TRIM(project_id)='')";
    foreach($ap as $apv) $params[]=$apv;
  }
  $baseParams = $params;   // filters and any project restriction, shared by both passes
  if(!empty($_GET['q'])){
    /* ── SMART SEARCH ──────────────────────────────────────────────
       Two bugs made search useless before:
       1) only the FIRST 12 columns were searched — a beneficiary's name is
          column 13, so searching a person by name NEVER matched anything.
       2) tables store IDs, not names: the Production list shows "Kuresh" but
          the row only holds BEN-0042, so searching a person on that page (or
          an SHG on the Loans page) could never match.
       Now: EVERY column is searched, AND every linked record is searched by
       its real name (beneficiary / SHG / project / donor / thematic /
       indicator), so what you see on screen is what you can search for.
       Multiple words = all must match somewhere (AND of ORs).            */
    /* The query is split into terms. Three power features are supported and are
       all optional, so plain typing keeps working exactly as before:
         "exact phrase"   a quoted phrase is matched as one whole string
         -word            a leading minus EXCLUDES rows containing that word
         column:value     restricts the match to one column, e.g. village:badagada
       Everything else is a normal term. All terms must match (AND), and each
       term may match in any column or in any linked record's real name.       */
    /* Normalise the typed text first. A name copied from elsewhere often carries a
       non breaking space, a zero width character or doubled spaces, and a plain
       LIKE would then never match. This is why an exact paste sometimes failed. */
    $raw = (string)($_GET['q'] ?? '');
    $raw = str_replace(["\xC2\xA0","\xE2\x80\x8B","\xE2\x80\x8C","\xE2\x80\x8D","\xEF\xBB\xBF"], ' ', $raw);
    $raw = preg_replace('/\s+/u', ' ', trim($raw));
    preg_match_all('/"[^"]*"|\S+/u', $raw, $mm);
    $terms = array_slice($mm[0], 0, 6);
    // Secrets are never searchable. Matching against them would reveal whether a
    // guess is correct, one character at a time.
    $noSearch=['password_hash','active_session_token','active_tab_id','password','token'];
    $searchCols=array_values(array_diff($cols,$noSearch));
    // Friendly aliases so a user can type what they see on screen
    $alias=['name'=>['beneficiary_farmer_name','shg_name','project_name','donor_name','programme_name','indicator_name','vdc_name','scheme_name','member_name','user_name'],
            'phone'=>['contact_number','phone','village_contact_person_cell_no'],
            'id'=>[$ownIdCol ?? 'id'],
            'village'=>['village','village_or_ward'],
            'place'=>['village','village_or_ward','gram_panchayat','block','district'],
            'crop'=>['crop'],'season'=>['season'],'gender'=>['gender'],'caste'=>['caste'],
            'district'=>['district'],'block'=>['block'],'gp'=>['gram_panchayat'],
            'scheme'=>['scheme_name'],'department'=>['department'],'status'=>['repayment_status','project_status','programme_status','status'],
            'remarks'=>['remarks']];
    /* THREE TIERS, TRIED IN ORDER OF PRECISION.
         tier 1  this table's own columns only
         tier 2  plus the SUBJECT of the row, meaning the linked record whose name
                 is actually shown in the list: the beneficiary on Production, the
                 group on Loans, the indicator on Progress
         tier 3  plus organisational links: project, donor, thematic area
       Each tier is only used when the one before it finds nothing. Lumping the
       subject together with the organisational links was the bug behind searching
       a person and receiving everyone who shares a project whose NAME contains
       those letters, for example a project named after Gopalpur when the search
       was for Gopal. */
    $SUBJECT_FK = ['crops'=>'beneficiary_id','loans'=>'shg_id','progress'=>'indicator_id','shg_members'=>'shg_id'];
    $subjectFk  = $SUBJECT_FK[$resource] ?? null;
    foreach($terms as $term){
      $neg = false;
      if($term!=='' && $term[0]==='-' && strlen($term)>1){ $neg=true; $term=substr($term,1); }
      $only = null;
      if(preg_match('/^([A-Za-z_]{2,30}):(.*)$/', $term, $cm)){
        $key=strtolower($cm[1]); $val=$cm[2];
        $cand = isset($alias[$key]) ? $alias[$key] : [$key];
        $cand = array_values(array_intersect($cand, $searchCols));
        if($cand && $val!==''){ $only=$cand; $term=$val; }
      }
      $term = trim(trim($term, '"'));
      if($term===''){ continue; }
      $like='%'.$term.'%';
      $targetCols = $only ?: $searchCols;
      $ors=[]; $vals=[];
      foreach($targetCols as $c){ $ors[]="`$c` LIKE ?"; $vals[]=$like; }
      if(!$ors) continue;
      $wrap = function($list) use($neg){ return ($neg?'NOT ':'').'('.implode(' OR ',$list).')'; };
      // tier 1
      $sWhere[] = $wrap($ors); foreach($vals as $v) $sParams[]=$v;
      // tier 2 — own columns plus the subject of the row
      $mors=$ors; $mvals=$vals;
      if(!$only && $subjectFk && in_array($subjectFk,$cols) && isset($FK_NAMES[$subjectFk])){
        list($ftbl,$fidc,$fnamec)=$FK_NAMES[$subjectFk];
        $mors[]="`$subjectFk` IN (SELECT `$fidc` FROM `$ftbl` WHERE `$fnamec` LIKE ?)";
        $mvals[]=$like;
      }
      $mWhere[] = $wrap($mors); foreach($mvals as $v) $mParams[]=$v;
      // tier 3 — everything, including organisational links
      $bors=$mors; $bvals=$mvals;
      if(!$only){
        foreach($FK_NAMES as $fk=>$info){
          if($fk===$subjectFk || !in_array($fk,$cols)) continue;
          list($ftbl,$fidc,$fnamec)=$info;
          $bors[]="`$fk` IN (SELECT `$fidc` FROM `$ftbl` WHERE `$fnamec` LIKE ?)";
          $bvals[]=$like;
        }
      }
      $bWhere[] = $wrap($bors); foreach($bvals as $v) $bParams[]=$v;
      if(!$neg && $firstTerm===null) $firstTerm=$term;
      $usedTerms++;
    }
    /* Typed something that reduced to nothing, for example a lone quotation mark.
       Return NO rows rather than the whole table. */
    if($usedTerms===0){ $sWhere[]='1=0'; $mWhere[]='1=0'; $bWhere[]='1=0'; }
  }

  /* Relevance: rows whose own label column matches come first, a name beginning
     with the query ahead of one that merely contains it. */
  $LABEL_COL = ['beneficiaries'=>'beneficiary_farmer_name','shgs'=>'shg_name','shg_members'=>'member_name',
    'projects'=>'project_name','donors'=>'donor_name','programmes'=>'programme_name','indicators'=>'indicator_name',
    'geographies'=>'village_or_ward','villages'=>'village','crops'=>'crop','activities'=>'project_activity',
    'gramsabha'=>'vdc_name','convergence'=>'scheme_name','users'=>'user_name'];
  $orderBy = ' ORDER BY id DESC';
  $orderParams = [];
  if($firstTerm!==null && isset($LABEL_COL[$resource]) && in_array($LABEL_COL[$resource],$cols)){
    $lc=$LABEL_COL[$resource];
    $orderBy = " ORDER BY (CASE WHEN `$lc` LIKE ? THEN 0 WHEN `$lc` LIKE ? THEN 1 ELSE 2 END), id DESC";
    $orderParams = [$firstTerm.'%', '%'.$firstTerm.'%'];
  }

  $mkWhere = function($extra) use($where){
    $all = array_merge($where, $extra);
    return $all ? (' WHERE '.implode(' AND ',$all)) : '';
  };
  if ($id) {
    $st=db()->prepare("SELECT * FROM `$table` WHERE id=?"); $st->execute([$id]);
    $row = $st->fetch();
    if($row && $resource==='users') unset($row['password_hash']);
    out(['row'=>$row]);
  }
  $limit=min((int)($_GET['limit']??5000),10000); $offset=(int)($_GET['offset']??0);
  // Try each tier in turn and stop at the first that finds anything.
  $tier='exact'; $w=$mkWhere($sWhere); $params=array_merge($baseParams,$sParams);
  $countWith=function($ww,$pp) use($table){ $c=db()->prepare("SELECT COUNT(*) c FROM `$table`$ww"); $c->execute($pp); return (int)$c->fetch()['c']; };
  $total=$countWith($w,$params);
  if($total===0 && $sWhere){
    foreach([['subject',$mWhere,$mParams],['linked',$bWhere,$bParams]] as $try){
      list($tname,$tw,$tp)=$try;
      if(!$tw || $tp===$sParams) continue;
      $w2=$mkWhere($tw); $p2=array_merge($baseParams,$tp);
      $t2=$countWith($w2,$p2);
      if($t2>0){ $w=$w2; $params=$p2; $total=$t2; $tier=$tname; break; }
    }
  }
  $st=db()->prepare("SELECT * FROM `$table`$w".$orderBy." LIMIT $limit OFFSET $offset");
  $st->execute(array_merge($params, $orderParams));
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
  // Production rows: attach each beneficiary's income context — total BASELINE income
  // (all sources, else the registration annual income) and CUMULATIVE production income
  // across EVERY record they have — so the list shows their income change till now.
  if($resource==='crops' && $rows){
    /* Income context for each row, RESPECTING THE YEAR FILTER.
       With no year chosen the comparison is baseline against the average of every
       year that has production. With a year chosen it is baseline against THAT
       year alone, so the figures on screen always describe the year being viewed. */
    $yearSel = trim((string)($_GET['project_year'] ?? ''));
    $bids=[]; foreach($rows as $r){ if(!empty($r['beneficiary_id'])) $bids[$r['beneficiary_id']]=1; }
    $bids=array_keys($bids);
    if($bids){
      $bl=[]; $cum=[]; $yrs=[];
      try{
        $ph=implode(',',array_fill(0,count($bids),'?'));
        $benCols=table_columns('beneficiaries');
        $blCands=['bl_paddy_income','bl_millet_income','bl_vegetable_income','bl_tuber_income','bl_pulses_income','bl_oilseed_income','bl_mushroom_income','bl_goat_income','bl_poultry_income','bl_micro_enterprise_income','bl_other_income'];
        $have=array_values(array_intersect($blCands,$benCols));
        $expr=[];
        foreach($have as $ic){ $ec=str_replace('_income','_expenditure',$ic);
          $expr[] = in_array($ec,$benCols) ? "(COALESCE($ic,0)-COALESCE($ec,0))" : "COALESCE($ic,0)"; }
        $blExpr = $expr ? ('('.implode('+',$expr).')') : '0';
        $stb=db()->prepare("SELECT beneficiary_id bid, CASE WHEN $blExpr<>0 THEN $blExpr ELSE COALESCE(current_income_per_annum_inr,0) END bl FROM beneficiaries WHERE beneficiary_id IN ($ph)");
        $stb->execute($bids); foreach($stb->fetchAll() as $r2){ $bl[$r2['bid']]=(float)$r2['bl']; }
        // NET on the production side, falling back to gross for rows saved before Net Profit existed
        $NP="COALESCE(net_profit_inr,income_inr)";
        if($yearSel!==''){
          $args=$bids; $args[]=$yearSel;
          $stc=db()->prepare("SELECT beneficiary_id bid, COALESCE(SUM($NP),0) s FROM crops WHERE beneficiary_id IN ($ph) AND TRIM(project_year)=? GROUP BY beneficiary_id");
          $stc->execute($args);
          foreach($stc->fetchAll() as $r2){ $cum[$r2['bid']]=(float)$r2['s']; $yrs[$r2['bid']]=1; }
        } else {
          $stc=db()->prepare("SELECT beneficiary_id bid, COALESCE(SUM($NP),0) s, COUNT(DISTINCT NULLIF(TRIM(project_year),'')) ny FROM crops WHERE beneficiary_id IN ($ph) GROUP BY beneficiary_id");
          $stc->execute($bids);
          foreach($stc->fetchAll() as $r2){ $cum[$r2['bid']]=(float)$r2['s']; $yrs[$r2['bid']]=max(1,(int)$r2['ny']); }
        }
      }catch(Exception $e){}
      foreach($rows as &$r){ $bid=$r['beneficiary_id']??'';
        $r['_bl_total']=$bl[$bid]??null; $r['_cum_income']=$cum[$bid]??null;
        $r['_years_n']=$yrs[$bid]??1; $r['_year_scope']=$yearSel; } unset($r);
    }
  }
  /* Beneficiary rows: the income view. `income_year` decides what is shown.
       Baseline (or empty)  the income recorded at registration
       Year-N               that beneficiary's income from Year-N production only
     This is what lets a reader follow one household from baseline through each
     year. It is computed here, never stored, so it cannot go stale. */
  if($resource==='beneficiaries' && $rows){
    $iy = trim((string)($_GET['income_year'] ?? ''));
    $benCols=table_columns('beneficiaries');
    $blCands=['bl_paddy_income','bl_millet_income','bl_vegetable_income','bl_tuber_income','bl_pulses_income','bl_oilseed_income','bl_mushroom_income','bl_goat_income','bl_poultry_income','bl_micro_enterprise_income','bl_other_income'];
    $have=array_values(array_intersect($blCands,$benCols));
    foreach($rows as &$r){
      $gross=0.0; $exp=0.0;
      foreach($have as $ic){
        $gross += (float)($r[$ic] ?? 0);
        $ec = str_replace('_income','_expenditure',$ic);
        if(in_array($ec,$benCols)) $exp += (float)($r[$ec] ?? 0);
      }
      $net = $gross - $exp;
      $r['_bl_total'] = ($net!=0) ? $net : (float)($r['current_income_per_annum_inr'] ?? 0);
    } unset($r);
    if($iy!=='' && strcasecmp($iy,'Baseline')!==0){
      $bids=[]; foreach($rows as $r){ if(!empty($r['beneficiary_id'])) $bids[$r['beneficiary_id']]=1; }
      $bids=array_keys($bids);
      $ysum=[];
      if($bids){
        try{
          $ph=implode(',',array_fill(0,count($bids),'?'));
          $args=$bids; $args[]=$iy;
          $st2=db()->prepare("SELECT beneficiary_id bid, COALESCE(SUM(COALESCE(net_profit_inr,income_inr)),0) s, COUNT(*) n
                              FROM crops WHERE beneficiary_id IN ($ph) AND TRIM(project_year)=? GROUP BY beneficiary_id");
          $st2->execute($args);
          foreach($st2->fetchAll() as $r2){ $ysum[$r2['bid']]=['s'=>(float)$r2['s'],'n'=>(int)$r2['n']]; }
        }catch(Exception $e){}
      }
      foreach($rows as &$r){
        $bid=$r['beneficiary_id']??'';
        $r['_year_income'] = isset($ysum[$bid]) ? $ysum[$bid]['s'] : null;   // null means nothing logged for that year
        $r['_year_records']= isset($ysum[$bid]) ? $ysum[$bid]['n'] : 0;
        $r['_income_year'] = $iy;
      } unset($r);
    } else {
      foreach($rows as &$r){ $r['_income_year']='Baseline'; } unset($r);
    }
  }

  // SHG Loan rows: attach the SHG's village & block, so a loan is placeable at a glance
  if($resource==='loans' && $rows){
    $sids=[]; foreach($rows as $r){ if(!empty($r['shg_id'])) $sids[$r['shg_id']]=1; }
    $sids=array_keys($sids);
    if($sids){
      try{
        $ph=implode(',',array_fill(0,count($sids),'?'));
        $sts=db()->prepare("SELECT shg_id, village, block FROM shgs WHERE shg_id IN ($ph)");
        $sts->execute($sids); $sm=[];
        foreach($sts->fetchAll() as $r2){ $sm[$r2['shg_id']]=$r2; }
        foreach($rows as &$r){ $s=$sm[$r['shg_id']??'']??null; $r['_shg_village']=$s['village']??null; $r['_shg_block']=$s['block']??null; } unset($r);
      }catch(Exception $e){}
    }
  }
  // Indicator-progress rows: attach the indicator's TOTAL project target — progress is
  // always measured against the target set in the Indicators section (single source of truth)
  if($resource==='progress' && $rows){
    $iids=[]; foreach($rows as $r){ if(!empty($r['indicator_id'])) $iids[$r['indicator_id']]=1; }
    $iids=array_keys($iids);
    if($iids){
      try{
        $ph=implode(',',array_fill(0,count($iids),'?'));
        $sti=db()->prepare("SELECT indicator_id, project_target FROM indicators WHERE indicator_id IN ($ph)");
        $sti->execute($iids); $tg=[]; foreach($sti->fetchAll() as $r2){ $tg[$r2['indicator_id']]=$r2['project_target']; }
        foreach($rows as &$r){ $r['_ind_target']=$tg[$r['indicator_id']]??null; } unset($r);
      }catch(Exception $e){}
    }
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
  out(['rows'=>$rows,'total'=>$total,'limit'=>$limit,'offset'=>$offset,'tier'=>$tier,'broadened'=>($tier!=='exact'),'q'=>$raw]);
 } catch (Throwable $e) {
  // Never let a query error surface to the browser as a bare 500 / "failed to fetch".
  out(['error'=>'Could not load '.$resource.' right now. '.safe_err($e,'')], 500);
 }
}

if ($method==='POST') {
  // users: admins OR matrix-granted user managers
  if($resource==='users'){
    if(!can_manage_users('e')) out(['error'=>'You do not have permission to create users'],403);
    $b=body(); $cols=table_columns($table);
    if(empty($b['username']) || empty($b['password'])) out(['error'=>'Username and password are required'],400);
    if(!is_admin() && stripos($b['role']??'','admin')!==false) out(['error'=>'Only an admin can create admin accounts'],403);
    if(strlen($b['password'])<6) out(['error'=>'Password must be at least 6 characters'],400);
    $b['password_hash']=password_hash($b['password'],PASSWORD_BCRYPT);
    unset($b['password']);
    $b['password_changed_at']=ist_now();
    // Only a ROOT admin may create another root-protected account
    $meU=current_user();
    if(array_key_exists('is_root',$b)){
      if(empty($meU['is_root'])) unset($b['is_root']);
      else $b['is_root']=$b['is_root']?1:0;
    }
    $set=[]; $ph=[]; $vals=[];
    foreach($b as $k=>$v){
      if(in_array($k,$cols) && !in_array($k,['id','created_at','active_session_token','active_tab_id','session_expires_at','last_login','last_seen','failed_login_count','locked_until'])){
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
  // 🎯 Target lock — new activities / indicators / HQ plan rows CARRY targets,
  // so creating them needs the explicit Targets grant (Users & Access → 🎯 Targets)
  if(in_array($resource,['activities','indicators','hq_targets']) && !can_edit_targets())
    out(['error'=>'Targets are locked — only target-setters (HQ) can add this. Ask your admin to tick 🎯 Targets in Users & Access.'],403);
  $b=body(); $cols=table_columns($table);
  // 📌 Project-restricted user cannot create records under another project
  { $ap=assigned_projects();
    if($ap && !empty($b['project_id']) && !in_array((string)$b['project_id'],$ap,true))
      out(['error'=>'You are assigned to specific project(s) — records for other projects are not allowed.'],403); }
  // Multi-user accountability — stamp who entered the record (column exists after upgrade8)
  $me=current_user();
  if($me && in_array('created_by',$cols)) $b['created_by']=$me['username'];
  // Blank registration date = today (IST) — a blank would hide the person from date-filtered reports
  if($resource==='beneficiaries' && empty($b['registration_date'])) $b['registration_date']=date('Y-m-d');
  $genId=null; $lockName=null;
  // Serialise business-ID generation across simultaneous saves (many users at once):
  // a per-resource MySQL named lock guarantees no two concurrent creates get the same ID.
  if(isset($ID_GEN[$resource])){
    $idCol=$ID_GEN[$resource][0];
    if(in_array($idCol,$cols)){
      $lockName='vmis_id_'.$resource;
      try{ $lk=db()->prepare("SELECT GET_LOCK(?,5)"); $lk->execute([$lockName]); }catch(Exception $e){ $lockName=null; }
      $genId=next_business_id($resource); $b[$idCol]=$genId;
    }
  }
  $releaseLock=function() use(&$lockName){ if($lockName){ try{ db()->prepare("SELECT RELEASE_LOCK(?)")->execute([$lockName]); }catch(Exception $e){} $lockName=null; } };
  $set=[]; $ph=[]; $vals=[];
  foreach($b as $k=>$v){
    if(in_array($k,$cols) && !in_array($k,['id','created_at','updated_at'])){
      $set[]="`$k`"; $ph[]='?'; $vals[]=($v===''?null:$v);
    }
  }
  if(!$set){ $releaseLock(); out(['error'=>'No valid fields'],400); }
  $sql="INSERT INTO `$table` (".implode(',',$set).") VALUES (".implode(',',$ph).")";
  try{ $st=db()->prepare($sql); $st->execute($vals); }
  catch(Exception $e){ $releaseLock(); out(['error'=>'Save failed','detail'=>safe_err($e)],500); }
  $newid=db()->lastInsertId();
  $releaseLock();
  audit('create',$resource,$genId?:$newid,"Added $resource ".($genId?:('#'.$newid)),null,$b);
  out(['ok'=>true,'id'=>$newid,'business_id'=>$genId],201);
}

if ($method==='PUT') {
  if(!$id) out(['error'=>'id required'],400);
  if($resource==='users'){
    if(!can_manage_users('e')) out(['error'=>'You do not have permission to edit users'],403);
    $cols=table_columns($table);
    $st=db()->prepare("SELECT * FROM users WHERE id=?"); $st->execute([$id]); $existing=$st->fetch();
    if(!$existing) out(['error'=>'User not found'],404);
    $b=body();
    // Root-protected accounts can only be touched by another root admin
    if(!empty($existing['is_root']) && empty((current_user()??[])['is_root'])) out(['error'=>'Only a root admin can modify a root-protected account'],403);
    // Matrix-granted managers handle non-admin accounts only (no promoting to Admin either)
    if(!is_admin()){
      if(stripos($existing['role']??'','admin')!==false) out(['error'=>'Only an admin can edit admin accounts'],403);
      if(isset($b['role']) && stripos($b['role'],'admin')!==false) out(['error'=>'Only an admin can promote a user to admin'],403);
    }
    // Root protection — only a ROOT admin may grant or revoke it (a plain admin could
    // previously slip is_root onto a non-root user), and the system always keeps at
    // least one root admin so nobody can lock everyone out.
    $meU=current_user();
    if(array_key_exists('is_root',$b)){
      if(empty($meU['is_root'])) unset($b['is_root']);
      else $b['is_root']=$b['is_root']?1:0;
    }
    if(!empty($existing['is_root'])){
      if(isset($b['role']) && stripos($b['role'],'admin')===false) out(['error'=>'Root admin role cannot be changed'],403);
      if(isset($b['username']) && strtolower($b['username'])!==strtolower($existing['username'])) out(['error'=>'Root admin username cannot be changed'],403);
      if(array_key_exists('is_root',$b) && !$b['is_root']){
        $rc=(int)db()->query("SELECT COUNT(*) c FROM users WHERE is_root=1")->fetch()['c'];
        if($rc<=1) out(['error'=>'Cannot remove root protection from the last root admin'],403);
      }
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
  // 🎯 Target lock — without the Targets grant the HQ-set numbers cannot change:
  // hq_targets rows are fully blocked; on activities / indicators / projects the
  // target columns are stripped so achievements still save but targets stay put.
  if(!can_edit_targets()){
    if($resource==='hq_targets') out(['error'=>'HQ targets are locked — only target-setters can change them. Ask your admin to tick 🎯 Targets in Users & Access.'],403);
    foreach(target_columns($resource) as $tc) unset($b[$tc]);
  }
  // Multi-user accountability — stamp who last changed the record (column exists after upgrade8)
  $me=current_user();
  if($me && in_array('updated_by',$cols)) $b['updated_by']=$me['username'];
  unset($b['created_by']);   // never let an edit overwrite who originally entered it
  $st=db()->prepare("SELECT * FROM `$table` WHERE id=?"); $st->execute([$id]); $before=$st->fetch();
  // 📌 Project-restricted user: can neither move a record to another project
  //    nor touch a record that already belongs to one
  { $ap=assigned_projects();
    if($ap && is_array($before)){
      if(!empty($b['project_id']) && !in_array((string)$b['project_id'],$ap,true))
        out(['error'=>'You are assigned to specific project(s) — records for other projects are not allowed.'],403);
      if(!empty($before['project_id']) && !in_array((string)$before['project_id'],$ap,true))
        out(['error'=>'This record belongs to a project you are not assigned to.'],403);
    } }
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
  // Forensic audit message — the record's business ID + exactly which fields changed
  // (the full before/after values are stored alongside and shown in the Audit Trail)
  $bizRef=null;
  if(isset($ID_GEN[$resource]) && is_array($before)){ $bc=$ID_GEN[$resource][0]; if(!empty($before[$bc])) $bizRef=$before[$bc]; }
  $changed=[];
  foreach($b as $k=>$v){
    if(!in_array($k,$cols) || in_array($k,['id','created_at','updated_at','updated_by','created_by'])) continue;
    $ov=($before[$k] ?? null); $ov=($ov===null)?'':trim((string)$ov); $nv=($v===null)?'':trim((string)$v);
    if($ov===$nv) continue;
    if($ov!=='' && $nv!=='' && is_numeric($ov) && is_numeric($nv) && (float)$ov===(float)$nv) continue;
    $changed[]=$k;
  }
  $msg="Edited $resource ".($bizRef?:"#$id");
  if($changed) $msg.=' — changed '.count($changed).' field'.(count($changed)>1?'s':'').': '.implode(', ',array_slice($changed,0,8)).(count($changed)>8?' +'.(count($changed)-8).' more':'');
  audit('update',$resource,$bizRef?:$id,$msg,$before,$b);
  out(['ok'=>true]);
}

if ($method==='DELETE') {
  if(!$id) out(['error'=>'id required'],400);
  if($resource==='users'){
    if(!can_manage_users('d')) out(['error'=>'You do not have permission to delete users'],403);
    $st=db()->prepare("SELECT id,username,role,is_root FROM users WHERE id=?"); $st->execute([$id]); $target=$st->fetch();
    if($target && !is_admin() && stripos($target['role']??'','admin')!==false) out(['error'=>'Only an admin can delete admin accounts'],403);
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
  // 🎯 Target lock — deleting an activity / indicator / HQ plan row destroys its HQ-set targets
  if(in_array($resource,['activities','indicators','hq_targets']) && !can_edit_targets())
    out(['error'=>'Targets are locked — only target-setters (HQ) can delete this. Ask your admin to tick 🎯 Targets in Users & Access.'],403);
  $st=db()->prepare("SELECT * FROM `$table` WHERE id=?"); $st->execute([$id]); $before=$st->fetch();
  // 📌 Project-restricted user cannot delete another project's record
  { $ap=assigned_projects();
    if($ap && is_array($before) && !empty($before['project_id']) && !in_array((string)$before['project_id'],$ap,true))
      out(['error'=>'This record belongs to a project you are not assigned to.'],403); }
  $st=db()->prepare("DELETE FROM `$table` WHERE id=?"); $st->execute([$id]);
  // ── Chain hygiene — nothing may keep pointing at a deleted donor/project ──
  if($resource==='donors' && !empty($before['donor_id'])){
    try{ $n=db()->prepare("DELETE FROM donor_mappings WHERE donor_id=?"); $n->execute([$before['donor_id']]);
      if($n->rowCount()) audit('delete','mappings',$before['donor_id'],"Auto-unmapped ".$n->rowCount()." project link(s) because donor ".$before['donor_id']." was deleted");
    }catch(Exception $e){}
  }
  if($resource==='projects' && !empty($before['project_id'])){
    try{ $n=db()->prepare("DELETE FROM donor_mappings WHERE project_id=?"); $n->execute([$before['project_id']]);
      if($n->rowCount()) audit('delete','mappings',$before['project_id'],"Removed ".$n->rowCount()." donor link(s) because project ".$before['project_id']." was deleted");
    }catch(Exception $e){}
    try{ if(in_array('project_id',table_columns('geographies'))){
      $n=db()->prepare("UPDATE geographies SET project_id=NULL WHERE project_id=?"); $n->execute([$before['project_id']]);
      if($n->rowCount()) audit('update','geographies',$before['project_id'],"Unlinked ".$n->rowCount()." geographies (places kept) because project ".$before['project_id']." was deleted");
    }}catch(Exception $e){}
  }
  /* ── DERIVED DATA MUST NOT OUTLIVE ITS SOURCE ──
     Every figure the system reports (income change, the eleven indicators,
     dashboard totals, report sheets) is CALCULATED from these tables at the
     moment it is shown. Nothing is pre-stored. So the only way stale or false
     data can survive a deletion is if a CHILD record is left behind pointing at
     a parent that no longer exists. Those orphans would still be counted.
     The cascades below remove them, and each one is written to the Audit Trail
     so the whole chain of a deletion can be traced afterwards. */

  // A beneficiary's production and output records go with them. Left behind, their
  // income would keep inflating the dashboard, the indicators and every report for a
  // person who is no longer registered.
  if($resource==='beneficiaries' && !empty($before['beneficiary_id'])){
    try{
      $n=db()->prepare("DELETE FROM crops WHERE beneficiary_id=?"); $n->execute([$before['beneficiary_id']]);
      if($n->rowCount()) audit('delete','crops',$before['beneficiary_id'],
        "Removed ".$n->rowCount()." production and output record(s) because beneficiary ".$before['beneficiary_id']." was deleted. Their income no longer counts anywhere.");
    }catch(Exception $e){}
  }

  // A group's members and its loans go with the group. Orphan members would keep
  // counting toward the SHG finance indicator, and orphan loans toward loan totals.
  if($resource==='shgs' && !empty($before['shg_id'])){
    foreach([['shg_members','member(s)'],['loans','loan record(s)']] as $pair){
      list($tbl,$what)=$pair;
      try{
        $n=db()->prepare("DELETE FROM `$tbl` WHERE shg_id=?"); $n->execute([$before['shg_id']]);
        if($n->rowCount()) audit('delete',$tbl==='loans'?'loans':'shg_members',$before['shg_id'],
          "Removed ".$n->rowCount()." ".$what." because SHG ".$before['shg_id']." was deleted.");
      }catch(Exception $e){}
    }
  }

  // Legacy indicator progress rows follow their indicator.
  if($resource==='indicators' && !empty($before['indicator_id'])){
    try{
      $n=db()->prepare("DELETE FROM indicator_progress WHERE indicator_id=?"); $n->execute([$before['indicator_id']]);
      if($n->rowCount()) audit('delete','progress',$before['indicator_id'],
        "Removed ".$n->rowCount()." progress record(s) because indicator ".$before['indicator_id']." was deleted.");
    }catch(Exception $e){}
  }

  // A deleted place must not leave records pointing at it. The records themselves are
  // KEPT, because they still hold their own district, block and village text; only the
  // broken link is cleared, so nothing is lost and nothing points at a missing place.
  if($resource==='geographies' && !empty($before['geography_id'])){
    foreach(['beneficiaries','crops','shgs','villages','gram_sabha','convergence'] as $tbl){
      try{
        if(!in_array('geography_id', table_columns($tbl))) continue;
        $n=db()->prepare("UPDATE `$tbl` SET geography_id=NULL WHERE geography_id=?");
        $n->execute([$before['geography_id']]);
        if($n->rowCount()) audit('update',$tbl,$before['geography_id'],
          "Unlinked ".$n->rowCount()." ".$tbl." record(s) from place ".$before['geography_id']." because that place was deleted. The records were kept.");
      }catch(Exception $e){}
    }
  }

  // Forensic audit message — the deleted record's business ID (its full data is stored alongside)
  $bizRef=null;
  if(isset($ID_GEN[$resource]) && is_array($before)){ $bc=$ID_GEN[$resource][0]; if(!empty($before[$bc])) $bizRef=$before[$bc]; }
  audit('delete',$resource,$bizRef?:$id,"Deleted $resource ".($bizRef?:"#$id"),$before,null);
  out(['ok'=>true]);
}

out(['error'=>'Unsupported request'],400);
