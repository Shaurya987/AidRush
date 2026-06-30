<?php
/* ─────────────────────────────────────────────────────────────
   VIEWS India MIS — Database configuration (SAMPLE)
   Copy this file to `config.php` and fill in your real cPanel
   database credentials. `config.php` is git-ignored so secrets
   never get committed.
   NOTE: this host does NOT add an account-name prefix to DB/user
   names — use the names exactly as shown in cPanel (no prefix).
   ───────────────────────────────────────────────────────────── */
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'your_database_name');   // database name (as shown in cPanel — no prefix)
define('DB_USER', 'your_database_user');   // database user (as shown in cPanel — no prefix)
define('DB_PASS', 'your_database_password'); // its password

/* Indian Standard Time — every PHP date() returns IST */
date_default_timezone_set('Asia/Kolkata');

/* Allow the frontend (any localhost port / Live Server) to call the API with cookies */
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if ($origin) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
}
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Session-Token, X-Tab-Id");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

/* Session (cookie) — secondary defence; primary auth is the X-Session-Token header */
session_set_cookie_params(['lifetime'=>0,'path'=>'/','httponly'=>true,'samesite'=>'Lax']);
session_start();

function db() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=utf8mb4";
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            // IST connection — NOW() / CURRENT_TIMESTAMP now return IST.
            try { $pdo->exec("SET time_zone = '+05:30'"); } catch (Exception $e) {}
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error'=>'Database connection failed','detail'=>$e->getMessage(),
                'hint'=>'Check api/config.php credentials and that the database is imported.']);
            exit;
        }
    }
    return $pdo;
}

function out($data, $code=200){ http_response_code($code); echo json_encode($data); exit; }
function body(){ $raw=file_get_contents('php://input'); $j=json_decode($raw,true); return is_array($j)?$j:[]; }
function client_ip(){
    foreach(['HTTP_X_FORWARDED_FOR','HTTP_CLIENT_IP','REMOTE_ADDR'] as $k){
        if(!empty($_SERVER[$k])) return trim(explode(',', $_SERVER[$k])[0]);
    }
    return '';
}
function client_ua(){ return substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250); }
function header_val($name){
    $key = 'HTTP_' . strtoupper(str_replace('-','_',$name));
    return $_SERVER[$key] ?? '';
}
function ist_now(){ return date('Y-m-d H:i:s'); }   // PHP tz already IST
function gen_token($bytes=32){ return bin2hex(random_bytes($bytes)); }
