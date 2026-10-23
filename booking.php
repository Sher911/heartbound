<?php
$title='Event bookings'; require 'header.php';
$err=''; $ok=false; $f=$_POST;
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $date=$f['event_date']??''; $paid=($f['paid']??'')==='1'?1:0; $pay=$paid?(float)($f['pay_per_person']??0):null;
  $perf=(int)($f['performers']??0);
  if (!trim($f['name']??'')||!filter_var($f['email']??'',FILTER_VALIDATE_EMAIL)) $err='Enter your name and a valid email.';
  elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)||$date<date('Y-m-d')) $err='Choose a date that has not passed.';
  elseif (unavailable($db,$date)) $err='Sorry, we are not available on '.e($date).'. Pick a green date in the calendar.';
  elseif ($perf<1||$perf>count($MEMBERS)) $err='Choose how many of us should perform.';
  elseif ($paid && $pay<=0) $err='Enter the amount you will pay per person.';
  else {
    $db->prepare("INSERT INTO bookings(name,email,phone,event_type,event_date,venue,perf_type,performers,paid,pay_per_person,details) VALUES(?,?,?,?,?,?,?,?,?,?,?)")
       ->execute([trim($f['name']),$f['email'],$f['phone']??'',$f['event_type']??'',$date,$f['venue']??'',$f['perf_type']??'',$perf,$paid,$pay,$f['details']??'']);
    $ok=true;
  }
}
$m=preg_match('/^\d{4}-\d{2}$/',$_GET['m']??'')?$_GET['m']:date('Y-m');
$first=strtotime($m.'-01'); $days=(int)date('t',$first); $off=(int)date('w',$first);
$prev=date('Y-m',strtotime('-1 month',$first)); $next=date('Y-m',strtotime('+1 month',$first));
$today=date('Y-m-d');
?>
<section class="wrap"><h1 class="h1s">Event bookings</h1>
<p class="lead">Check our calendar, then tell us about your event. We reply by email to confirm.</p>
<div class="two-col">
<div class="panel"><div class="calhead"><a href="?m=<?= $prev ?>">&#8249;</a><strong><?= date('F Y',$first) ?></strong><a href="?m=<?= $next ?>">&#8250;</a></div>
<div class="cal"><?php foreach(['Su','Mo','Tu','We','Th','Fr','Sa'] as $d) echo "<b>$d</b>";
for($i=0;$i<$off;$i++) echo '<i></i>';
for($d=1;$d<=$days;$d++){ $ds=sprintf('%s-%02d',$m,$d);
  $cls=$ds<$today?'past':(unavailable($db,$ds)?'busy':'free');
  echo "<button type=\"button\" class=\"$cls\" data-d=\"$ds\"".($cls==='free'?'':' disabled').">$d</button>"; } ?></div>
<p class="legend"><span class="free"></span>Available <span class="busy"></span>Not available</p></div>

<form method="post" class="panel">
<?php if($ok): ?><div class="msg good">Thank you! Your request is in. We will email you soon to confirm.</div><?php endif; ?>
<?php if($err): ?><div class="msg bad"><?= $err ?></div><?php endif; ?>
<label>Your name<input name="name" required value="<?= e($f['name']??'') ?>"></label>
<div class="row"><label>Email<input type="email" name="email" required value="<?= e($f['email']??'') ?>"></label>
<label>Phone<input name="phone" value="<?= e($f['phone']??'') ?>"></label></div>
<div class="row"><label>Event date<input type="date" id="date" name="event_date" min="<?= $today ?>" required value="<?= e($f['event_date']??'') ?>"></label>
<label>Event type<select name="event_type"><?php foreach($EVENT_TYPES as $t) echo '<option>'.e($t).'</option>'; ?></select></label></div>
<label>Venue and location<input name="venue" value="<?= e($f['venue']??'') ?>"></label>
<div class="row"><label>Type of performance<select name="perf_type"><?php foreach($PERF_TYPES as $t) echo '<option>'.e($t).'</option>'; ?></select></label>
<label>How many of us perform<select name="performers"><?php for($i=1;$i<=count($MEMBERS);$i++) echo "<option>$i</option>"; ?></select></label></div>
<fieldset><legend>Is this a paid gig?</legend>
<label class="inl"><input type="radio" name="paid" value="1" checked> Paid</label>
<label class="inl"><input type="radio" name="paid" value="0"> Not paid</label>
<label id="payrow">Amount per person (BND)<input type="number" step="0.01" min="0" name="pay_per_person" value="<?= e($f['pay_per_person']??'') ?>"></label></fieldset>
<label>Event details<textarea name="details" rows="4" placeholder="Start time, length of set, songs you want, anything else"><?= e($f['details']??'') ?></textarea></label>
<button class="btn" type="submit">Send booking request</button>
</form></div></section>
<script>
const d=document.getElementById('date');
document.querySelectorAll('.cal .free').forEach(b=>b.onclick=()=>{d.value=b.dataset.d;d.scrollIntoView({behavior:'smooth',block:'center'})});
const r=document.querySelectorAll('[name=paid]'),p=document.getElementById('payrow');
const t=()=>p.style.display=document.querySelector('[name=paid]:checked').value==='1'?'block':'none';
r.forEach(x=>x.onchange=t);t();
</script>
<?php require 'footer.php'; ?>
