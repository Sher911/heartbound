<?php
// Receives a big video in small pieces (chunks) so uploads up to 2,000 MB work
// even on hosts with tiny PHP upload limits. Used by the admin "Past performances" form.
require_once __DIR__.'/db.php';
header('Content-Type: application/json');
function out($code,$a){ http_response_code($code); echo json_encode($a); exit; }

if (!is_admin()) out(403,['error'=>'Not logged in as admin.']);
if (!hash_equals(csrf(), (string)($_SERVER['HTTP_X_CSRF'] ?? ''))) out(400,['error'=>'Session expired. Refresh the page.']);

$token = preg_replace('/[^a-f0-9]/','',(string)($_GET['token'] ?? ''));
$idx   = (int)($_GET['idx'] ?? -1);
$total = (int)($_GET['total'] ?? 0);
$size  = (int)($_GET['size'] ?? 0);
$ext   = strtolower((string)($_GET['ext'] ?? ''));
$name  = (string)($_GET['name'] ?? 'video');

if (strlen($token)!==32 || $idx<0 || $total<1 || $idx>=$total) out(400,['error'=>'Bad upload request.']);
if (!in_array($ext, ['mp4','webm','mov'], true)) out(400,['error'=>'Use an MP4, MOV or WEBM video.']);
if ($size<1 || $size>VIDEO_MAX_BYTES) out(400,['error'=>'Video must be under '.VIDEO_MAX_MB.' MB.']);

$dir = __DIR__.'/assets/videos'; if (!is_dir($dir)) @mkdir($dir,0755,true);
$part = $dir.'/.upload-'.$token.'.part';
if ($idx===0 && is_file($part)) @unlink($part);
if ($idx>0 && !is_file($part)) out(400,['error'=>'Upload lost its place. Try again.']);

$in = fopen('php://input','rb'); $fo = fopen($part,'ab');
if (!$in || !$fo) out(500,['error'=>'Server could not write the video. Check assets/videos is writable.']);
stream_copy_to_stream($in,$fo); fclose($in); fclose($fo);
clearstatcache(true,$part);
if (filesize($part) > VIDEO_MAX_BYTES) { @unlink($part); out(400,['error'=>'Video must be under '.VIDEO_MAX_MB.' MB.']); }

if ($idx < $total-1) out(200,['ok'=>1]);

// Last chunk: check it all arrived, then give it its real name
if (filesize($part) !== $size) { @unlink($part); out(400,['error'=>'Upload incomplete. Try again.']); }
$fn = (preg_replace('/[^a-z0-9]+/','-',strtolower($name)) ?: 'video').'-'.time().'.'.$ext;
$fn = trim($fn,'-');
if (!rename($part, $dir.'/'.$fn)) out(500,['error'=>'Could not save the video.']);
// tidy old abandoned pieces (older than a day)
foreach (glob($dir.'/.upload-*.part') ?: [] as $old) if (filemtime($old) < time()-86400) @unlink($old);
out(200,['ok'=>1,'file'=>$fn]);
