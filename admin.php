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
function save_photo($name){
  if (empty($_FILES['photo']['tmp_name']) || $_FILES['photo']['error']!==UPLOAD_ERR_OK) return null;
  $ext=strtolower(pathinfo($_FILES['photo']['name'],PATHINFO_EXTENSION));
  if (!in_array($ext,['jpg','jpeg','png','webp'],true) || $_FILES['photo']['size']>5*1024*1024) return false;
  $dir=__DIR__.'/assets/members'; if(!is_dir($dir)) @mkdir($dir,0755,true);
  $fn=(preg_replace('/[^a-z0-9]+/','-',strtolower($name))?:'member').'-'.time().'.'.$ext;
  return move_uploaded_file($_FILES['photo']['tmp_name'],$dir.'/'.$fn) ? $fn : false;
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
<div class="admtabs"><a href="#bookings">Bookings</a><a href="#dates">Block dates</a><a href="#members">Members</a><a href="admin.php?out=1">Log out</a></div>
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
</section><?php require 'footer.php'; ?>
