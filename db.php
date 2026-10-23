<?php
session_start();
require __DIR__.'/config.php';
$db = new PDO('sqlite:'.__DIR__.'/data.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE IF NOT EXISTS bookings(id INTEGER PRIMARY KEY AUTOINCREMENT,name TEXT,email TEXT,phone TEXT,event_type TEXT,event_date TEXT,venue TEXT,perf_type TEXT,performers INT,paid INT,pay_per_person REAL,details TEXT,status TEXT DEFAULT 'pending',created TEXT DEFAULT CURRENT_TIMESTAMP)");
$db->exec("CREATE TABLE IF NOT EXISTS blocked(date TEXT PRIMARY KEY,note TEXT)");

// ---- Members now live in the database so admins can edit / delete them ----
$db->exec("CREATE TABLE IF NOT EXISTS members(id INTEGER PRIMARY KEY AUTOINCREMENT,sort INT DEFAULT 0,name TEXT,role TEXT,photo TEXT,bio TEXT,fullname TEXT,specialty TEXT,favourite TEXT,funfact TEXT)");
$db->exec("CREATE TABLE IF NOT EXISTS meta(k TEXT PRIMARY KEY,v TEXT)");
// First run only: copy the members from config.php into the database
if (!$db->query("SELECT 1 FROM meta WHERE k='members_seeded'")->fetch()) {
  $db->beginTransaction();
  $ins = $db->prepare("INSERT INTO members(sort,name,role,photo,bio,fullname,specialty,favourite,funfact) VALUES(?,?,?,?,?,?,?,?,?)");
  foreach ($MEMBERS as $i => $m) {
    $ins->execute([$i,$m['name']??'',$m['role']??'',$m['photo']??'',$m['bio']??'',$m['fullname']??'',$m['specialty']??'',$m['favourite']??'',$m['funfact']??'']);
  }
  $db->exec("INSERT INTO meta(k,v) VALUES('members_seeded','1')");
  $db->commit();
}
// From here on, $MEMBERS comes from the database (same shape as in config.php)
$MEMBERS = $db->query("SELECT id,name,role,photo,bio,fullname,specialty,favourite,funfact FROM members ORDER BY sort,id")->fetchAll(PDO::FETCH_ASSOC);

// ---- Past performances live in the database so admins can add / edit / delete them ----
$db->exec("CREATE TABLE IF NOT EXISTS performances(id INTEGER PRIMARY KEY AUTOINCREMENT,title TEXT,perf_date TEXT,venue TEXT,photo TEXT,text TEXT)");
// Older installs: add the video column if it is missing
$pcols = array_column($db->query("PRAGMA table_info(performances)")->fetchAll(PDO::FETCH_ASSOC),'name');
if (!in_array('video',$pcols,true)) $db->exec("ALTER TABLE performances ADD COLUMN video TEXT DEFAULT ''");
// Newest added first. (Same shape the About page already uses: title, date, venue, photo, text, video)
$PERFORMANCES = $db->query("SELECT id,title,perf_date AS date,venue,photo,text,video FROM performances ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Max size for uploaded performance videos
const VIDEO_MAX_MB = 2000;
const VIDEO_MAX_BYTES = VIDEO_MAX_MB*1024*1024;

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES); }
function is_admin(){ return !empty($_SESSION['adm']); }
function csrf(){ if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function csrf_ok(){ return isset($_POST['csrf']) && hash_equals(csrf(), (string)$_POST['csrf']); }
function unavailable($db,$d){
  $s=$db->prepare("SELECT 1 FROM blocked WHERE date=? UNION SELECT 1 FROM bookings WHERE event_date=? AND status='confirmed'");
  $s->execute([$d,$d]); return (bool)$s->fetch();
}
function img($dir,$file,$alt,$cls=''){
  return '<img class="'.$cls.'" src="assets/'.$dir.'/'.e($file).'" alt="'.e($alt).'" onerror="this.style.display=\'none\'">';
}

// Turns a stored video value into something the page can play.
// Accepts a YouTube / Vimeo link, a direct .mp4/.webm link, or a file name inside assets/videos/.
function video_info($v){
  $v=trim((string)$v); if($v==='') return null;
  if (preg_match('~^https?://~i',$v)) {
    if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?(?:[^#]*&)?v=|embed/|shorts/|live/))([A-Za-z0-9_-]{11})~i',$v,$m))
      return ['kind'=>'iframe','src'=>'https://www.youtube-nocookie.com/embed/'.$m[1]];
    if (preg_match('~vimeo\.com/(?:video/)?(\d+)~i',$v,$m))
      return ['kind'=>'iframe','src'=>'https://player.vimeo.com/video/'.$m[1]];
    if (preg_match('~\.(mp4|webm)(\?.*)?$~i',$v)) return ['kind'=>'file','src'=>$v];
    return null;
  }
  return ['kind'=>'file','src'=>'assets/videos/'.rawurlencode($v)];
}
