<?php
require_once __DIR__.'/db.php';

// ---- Log out / log in (handled before any output so redirects work) ----
if (isset($_GET['out'])) { unset($_SESSION['adm']); header('Location: admin.php'); exit; }
if (isset($_POST['pw'])) {
  if (hash_equals((string)$SITE['admin_password'], (string)$_POST['pw'])) { session_regenerate_id(true); $_SESSION['adm']=1; header('Location: admin.php'); exit; }
  else $bad=1;
}

// ---- Admin actions ----
$flash='';
function save_file($key,$name,$sub,$exts,$max,$fallback){
  if (empty($_FILES[$key]) || $_FILES[$key]['error']===UPLOAD_ERR_NO_FILE) return null;
  if ($_FILES[$key]['error']!==UPLOAD_ERR_OK) return false;
  $ext=strtolower(pathinfo($_FILES[$key]['name'],PATHINFO_EXTENSION));
  if (!in_array($ext,$exts,true) || $_FILES[$key]['size']>$max) return false;
  $dir=__DIR__.'/assets/'.$sub; if(!is_dir($dir)) @mkdir($dir,0755,true);
  $fn=(preg_replace('/[^a-z0-9]+/','-',strtolower($name))?:$fallback).'-'.time().'.'.$ext;
  return move_uploaded_file($_FILES[$key]['tmp_name'],$dir.'/'.$fn) ? $fn : false;
}
function save_photo($name,$sub='members',$fallback='member'){
  return save_file('photo',$name,$sub,['jpg','jpeg','png','webp'],5*1024*1024,$fallback);
}
if (is_admin() && isset($_POST['act'])) {
  if (!csrf_ok()) { http_response_code(400); exit('Invalid request. Go back and refresh the page.'); }
  $a=$_POST['act']; $go='admin.php';
  if ($a==='status' && in_array($_POST['s']??'',['pending','confirmed','declined'],true))
    $db->prepare("UPDATE bookings SET status=? WHERE id=?")->execute([$_POST['s'],(int)$_POST['id']]);
  if ($a==='block' && !empty($_POST['date'])) $db->prepare("INSERT OR REPLACE INTO blocked VALUES(?,?)")->execute([$_POST['date'],$_POST['note']??'']);
  if ($a==='unblock') $db->prepare("DELETE FROM blocked WHERE date=?")->execute([$_POST['date']]);
  if ($a==='mem_save' || $a==='mem_add') {
    $name=trim($_POST['name']??''); $go='admin.php#members';
    if ($name==='') { $_SESSION['flash']='Member name cannot be empty.'; }
    else {
      $up=save_photo($name);
      if ($up===false) $_SESSION['flash']='Photo not saved: use a JPG, PNG or WEBP under 5 MB.';
      $f=fn($k)=>trim($_POST[$k]??'');
      if ($a==='mem_save') {
        $id=(int)$_POST['id']; $photo=$up?:$f('photo');
        $db->prepare("UPDATE members SET name=?,role=?,photo=?,bio=?,fullname=?,specialty=?,favourite=?,funfact=? WHERE id=?")
           ->execute([$name,$f('role'),$photo,$f('bio'),$f('fullname'),$f('specialty'),$f('favourite'),$f('funfact'),$id]);
      } else {
        $max=(int)$db->query("SELECT COALESCE(MAX(sort),0) FROM members")->fetchColumn();
        $db->prepare("INSERT INTO members(sort,name,role,photo,bio,fullname,specialty,favourite,funfact) VALUES(?,?,?,?,?,?,?,?,?)")
           ->execute([$max+1,$name,$f('role'),$up?:$f('photo'),$f('bio'),$f('fullname'),$f('specialty'),$f('favourite'),$f('funfact')]);
      }
    }
  }
  if ($a==='perf_save' || $a==='perf_add') {
    $title=trim($_POST['title']??''); $go='admin.php#performances';
    if ($title==='') { $_SESSION['flash']='Performance title cannot be empty.'; }
    else {
      $f=fn($k)=>trim($_POST[$k]??''); $errs=[];
      $up=save_photo($title,'gallery','performance');
      if ($up===false) $errs[]='Photo not saved: use a JPG, PNG or WEBP under 5 MB (your server may also limit upload size).';
      // Video: a new upload wins, then a pasted link, otherwise keep what was there (or remove it)
      $vid=$f('video');
      if (!empty($_POST['video_remove'])) $vid='';
      $vf=save_file('video_file',$title,'videos',['mp4','webm','mov'],VIDEO_MAX_BYTES,'video');
      if ($vf===false) $errs[]='Video file not saved: use MP4, MOV or WEBM under '.VIDEO_MAX_MB.' MB.';
      // Big videos are uploaded in pieces by upload_chunk.php first; the form just sends the saved file name
      $vu=basename((string)($_POST['video_uploaded']??''));
      if (!$vf && $vu!=='' && preg_match('/^[a-z0-9-]+\.(mp4|webm|mov)$/',$vu) && is_file(__DIR__.'/assets/videos/'.$vu)) $vf=$vu;
      if ($vf) $vid=$vf;
      elseif ($f('video_url')!=='') { if (video_info($f('video_url'))) $vid=$f('video_url'); else $errs[]='Video link not recognised: use a YouTube or Vimeo link, or upload a file.'; }
      if ($errs) $_SESSION['flash']=implode(' ',$errs);
      if ($a==='perf_save') {
        $db->prepare("UPDATE performances SET title=?,perf_date=?,venue=?,photo=?,text=?,video=? WHERE id=?")
           ->execute([$title,$f('perf_date'),$f('venue'),$up?:$f('photo'),$f('text'),$vid,(int)$_POST['id']]);
      } else {
        $db->prepare("INSERT INTO performances(title,perf_date,venue,photo,text,video) VALUES(?,?,?,?,?,?)")
           ->execute([$title,$f('perf_date'),$f('venue'),$up?:'',$f('text'),$vid]);
      }
    }
  }
  if ($a==='perf_del') { $db->prepare("DELETE FROM performances WHERE id=?")->execute([(int)$_POST['id']]); $go='admin.php#performances'; }
  if ($a==='mem_del') { $db->prepare("DELETE FROM members WHERE id=?")->execute([(int)$_POST['id']]); $go='admin.php#members'; }
  header('Location: '.$go); exit;
}
if (!empty($_SESSION['flash'])) { $flash=$_SESSION['flash']; unset($_SESSION['flash']); }

$title='Admin'; require 'header.php';

if (!is_admin()): ?>
<section class="wrap narrow"><h1 class="h1s">Group admin</h1>
<form method="post" class="panel"><?php if(!empty($bad)) echo '<div class="msg bad">Wrong password.</div>'; ?>
<label>Password<input type="password" name="pw" required></label><button class="btn">Log in</button></form></section>
<?php require 'footer.php'; exit; endif;

$rows=$db->query("SELECT * FROM bookings ORDER BY event_date")->fetchAll(PDO::FETCH_ASSOC);
$blk=$db->query("SELECT * FROM blocked ORDER BY date")->fetchAll(PDO::FETCH_ASSOC);
$T='<input type="hidden" name="csrf" value="'.e(csrf()).'">';
?>
<section class="wrap"><h1 class="h1s">Booking requests</h1>
<div class="admtabs"><a href="#bookings">Bookings</a><a href="#dates">Block dates</a><a href="#members">Members</a><a href="#performances">Past performances</a><a href="admin.php?out=1">Log out</a></div>
<?php if($flash) echo '<div class="msg bad">'.e($flash).'</div>'; ?>
<?php if($SITE['admin_password']==='change-this-password') echo '<div class="msg bad">Please change the default admin password in config.php.</div>'; ?>

<div id="bookings" class="tablewrap"><table><tr><th>Date</th><th>Who</th><th>Event</th><th>Pay</th><th>Perform</th><th>Status</th><th></th></tr>
<?php foreach($rows as $r): ?><tr>
<td><?= e($r['event_date']) ?></td>
<td><?= e($r['name']) ?><br><small><?= e($r['email']) ?> <?= e($r['phone']) ?></small></td>
<td><?= e($r['event_type']) ?>, <?= e($r['venue']) ?><br><small><?= e($r['details']) ?></small></td>
<td><?= $r['paid']?'BND '.number_format($r['pay_per_person'],2).' / person':'Unpaid' ?></td>
<td><?= e($r['perf_type']) ?><br><small><?= (int)$r['performers'] ?> members</small></td>
<td><span class="tag <?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
<td><form method="post" class="inl"><?= $T ?><input type="hidden" name="act" value="status"><input type="hidden" name="id" value="<?= $r['id'] ?>">
<button name="s" value="confirmed">Confirm</button><button name="s" value="declined">Decline</button><?php if($r['status']!=='pending'): ?><button name="s" value="pending">Reset</button><?php endif; ?></form></td></tr>
<?php endforeach; if(!$rows) echo '<tr><td colspan="7">No requests yet.</td></tr>'; ?></table></div>

<h2 id="dates">Block dates (not available)</h2>
<form method="post" class="row panel"><?= $T ?><input type="hidden" name="act" value="block"><label>Date<input type="date" name="date" required></label><label>Note<input name="note" placeholder="Rehearsal, other event..."></label><button class="btn">Block date</button></form>
<?php foreach($blk as $b): ?><form method="post" class="chipline"><?= $T ?><input type="hidden" name="act" value="unblock"><input type="hidden" name="date" value="<?= e($b['date']) ?>"><?= e($b['date']) ?> <?= e($b['note']) ?> <button>Remove</button></form><?php endforeach; ?>

<h2 id="members" style="margin-top:2em">Manage members</h2>
<p class="hint">Tap a member to edit their details. Changes show on the Members page straight away. Photo: upload a new one, or type a file name that already exists in assets/members/.</p>
<?php
function mfields($m=[]){ $v=fn($k)=>e($m[$k]??'');
  return '<div class="row"><label>Name<input name="name" required value="'.$v('name').'"></label><label>Role<input name="role" value="'.$v('role').'"></label></div>'
  .'<label>Short bio<textarea name="bio" rows="3">'.$v('bio').'</textarea></label>'
  .'<div class="row"><label>Full name<input name="fullname" value="'.$v('fullname').'"></label><label>Specialty<input name="specialty" value="'.$v('specialty').'"></label></div>'
  .'<div class="row"><label>Favourite K-pop / dance<input name="favourite" value="'.$v('favourite').'"></label><label>Fun fact<input name="funfact" value="'.$v('funfact').'"></label></div>'
  .'<div class="row"><label>Photo file name<input name="photo" value="'.$v('photo').'"></label><label>Upload new photo<input type="file" name="photo" accept="image/*"></label></div>';
}
foreach($MEMBERS as $m): ?>
<details class="mcard"><summary><div class="mrow"><div class="who"><?= img('members',$m['photo'],$m['name'],'thumb') ?><div><strong><?= e($m['name']) ?></strong><br><small class="hint"><?= e($m['role']) ?></small></div></div><span class="tag">Edit</span></div></summary>
<form method="post" enctype="multipart/form-data" class="edit"><?= $T ?><input type="hidden" name="act" value="mem_save"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
<?= mfields($m) ?><button class="btn">Save changes</button></form>
<form method="post" onsubmit="return confirm('Delete <?= e(addslashes($m['name'])) ?> permanently?')" style="margin-top:10px"><?= $T ?><input type="hidden" name="act" value="mem_del"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><button class="danger">Delete member</button></form>
</details>
<?php endforeach; if(!$MEMBERS) echo '<p>No members yet.</p>'; ?>

<details class="mcard"><summary><strong>+ Add a new member</strong></summary>
<form method="post" enctype="multipart/form-data" class="edit"><?= $T ?><input type="hidden" name="act" value="mem_add"><?= mfields() ?><button class="btn">Add member</button></form></details>

<h2 id="performances" style="margin-top:2em">Manage past performances</h2>
<p class="hint">These appear on the About page, newest added first. Add a photo and write the description yourself. You can also add a video (a YouTube or Vimeo link, or an uploaded MP4/MOV/WEBM up to <?= number_format(VIDEO_MAX_MB) ?> MB) so visitors can watch it on the About page. Photos save to assets/gallery/ and videos to assets/videos/.</p>
<?php
function pfields($p=[]){ $v=fn($k)=>e($p[$k]??'');
  return '<label>Title<input name="title" required placeholder="e.g. Wedding reception" value="'.$v('title').'"></label>'
  .'<div class="row"><label>Date<input name="perf_date" placeholder="e.g. March 2026" value="'.e($p['date']??'').'"></label><label>Venue<input name="venue" placeholder="Where it was held" value="'.$v('venue').'"></label></div>'
  .'<label>Description<textarea name="text" rows="4" placeholder="Tell people about this performance...">'.$v('text').'</textarea></label>'
  .'<input type="hidden" name="photo" value="'.$v('photo').'">'
  .'<label>Photo (JPG, PNG or WEBP, up to 5 MB)<input type="file" name="photo" accept="image/*"></label>'
  .'<input type="hidden" name="video" value="'.$v('video').'">'
  .'<div class="row"><label>Video link (YouTube or Vimeo)<input name="video_url" placeholder="https://www.youtube.com/watch?v=..." value="'.e(preg_match('~^https?://~i',(string)($p['video']??''))?$p['video']:'').'"></label>'
  .'<label>Or upload a video (MP4, MOV or WEBM, up to '.number_format(VIDEO_MAX_MB).' MB)<input type="file" name="video_file" accept="video/mp4,video/quicktime,video/webm,.mov"></label></div>'
  .'<input type="hidden" name="video_uploaded" value=""><div class="vprog" hidden><progress max="100" value="0"></progress> <span></span></div>'
  .(!empty($p['video'])?'<label class="inl"><input type="checkbox" name="video_remove" value="1">Remove current video'.(preg_match('~^https?://~i',$p['video'])?'':' ('.e($p['video']).')').'</label>':'');
}
foreach($PERFORMANCES as $p): ?>
<details class="mcard"><summary><div class="mrow"><div class="who"><?= img('gallery',$p['photo'],$p['title'],'thumb') ?><div><strong><?= e($p['title']) ?></strong><br><small class="hint"><?= e(trim($p['venue'].' '.($p['date']?'· '.$p['date']:''))) ?></small></div></div><span class="tag">Edit</span></div></summary>
<form method="post" enctype="multipart/form-data" class="edit"><?= $T ?><input type="hidden" name="act" value="perf_save"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
<?= pfields($p) ?><?php if($p['photo']): ?><p class="hint">Current photo: <?= e($p['photo']) ?>. Choose a file above only if you want to replace it.</p><?php endif; ?><button class="btn">Save changes</button></form>
<form method="post" onsubmit="return confirm('Delete this performance permanently?')" style="margin-top:10px"><?= $T ?><input type="hidden" name="act" value="perf_del"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="danger">Delete performance</button></form>
</details>
<?php endforeach; if(!$PERFORMANCES) echo '<p>No performances added yet.</p>'; ?>

<details class="mcard"><summary><strong>+ Add a past performance</strong></summary>
<form method="post" enctype="multipart/form-data" class="edit"><?= $T ?><input type="hidden" name="act" value="perf_add"><?= pfields() ?><button class="btn">Add performance</button></form></details>
</section>
<script>
// Uploads big videos in 4 MB pieces (with a progress bar) before saving the performance,
// so files up to <?= VIDEO_MAX_MB ?> MB work without changing the server's PHP upload limits.
(()=>{
const MAX=<?= VIDEO_MAX_BYTES ?>, CHUNK=4*1024*1024, CSRF=<?= json_encode(csrf()) ?>;
document.querySelectorAll('form').forEach(f=>{
  const inp=f.querySelector('input[name="video_file"]'); if(!inp) return;
  f.addEventListener('submit',async ev=>{
    const file=inp.files[0]; if(!file || f.dataset.done) return;
    ev.preventDefault();
    const ext=(file.name.split('.').pop()||'').toLowerCase();
    if(!['mp4','webm','mov'].includes(ext)) return alert('Use an MP4, MOV or WEBM video.');
    if(file.size>MAX) return alert('That video is '+Math.round(file.size/1048576)+' MB. The limit is <?= number_format(VIDEO_MAX_MB) ?> MB.');
    const box=f.querySelector('.vprog'),bar=box.querySelector('progress'),txt=box.querySelector('span'),btns=f.querySelectorAll('button');
    box.hidden=false; btns.forEach(b=>b.disabled=true);
    const token=[...crypto.getRandomValues(new Uint8Array(16))].map(b=>b.toString(16).padStart(2,'0')).join('');
    const total=Math.max(1,Math.ceil(file.size/CHUNK)), title=f.querySelector('[name="title"]').value||'video';
    const stop=msg=>{alert(msg);box.hidden=true;btns.forEach(b=>b.disabled=false);};
    window.onbeforeunload=()=>'Video still uploading';
    try{
      let res;
      for(let i=0;i<total;i++){
        const q=new URLSearchParams({token,idx:i,total,size:file.size,ext,name:title});
        for(let tries=0;;tries++){
          try{
            const r=await fetch('upload_chunk.php?'+q,{method:'POST',headers:{'X-CSRF':CSRF,'Content-Type':'application/octet-stream'},body:file.slice(i*CHUNK,(i+1)*CHUNK),credentials:'same-origin'});
            res=await r.json(); if(!r.ok) throw new Error(res.error||'Upload failed');
            break;
          }catch(e){ if(tries>=3 || /MB|MP4|admin|Refresh|Bad|lost|incomplete/.test(e.message)) throw e; await new Promise(z=>setTimeout(z,1500)); }
        }
        const pct=Math.round((i+1)/total*100); bar.value=pct;
        txt.textContent='Uploading video… '+pct+'% ('+Math.round(Math.min(file.size,(i+1)*CHUNK)/1048576)+' / '+Math.round(file.size/1048576)+' MB)';
      }
      window.onbeforeunload=null;
      f.querySelector('[name="video_uploaded"]').value=res.file;
      inp.value=''; txt.textContent='Video uploaded. Saving…';
      f.dataset.done=1; f.submit();
    }catch(e){ window.onbeforeunload=null; stop('Video upload failed: '+e.message); }
  });
});
})();
</script>
<?php require 'footer.php'; ?>
