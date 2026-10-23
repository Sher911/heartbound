<?php $title='Home'; require 'header.php'; ?>
<section class="hero">
  <div class="copy-block">
    <h1><span>Heartbound</span></h1>
    <p><?= e($SITE['about']) ?></p>
    <div class="btns"><a class="btn" href="booking.php">Book us for your event</a><a class="btn alt" href="members.php">Meet the dancers</a></div>
  </div>
  <div class="arch"><?= img('', 'group.jpg', 'Heartbound group photo') ?>
    <div class="ph">Add your group photo:<br>assets/group.jpg</div>
    <span class="chip"><?= e($SITE['tagline']) ?></span></div>
</section>
<section class="wrap"><h2>Our past performances</h2>
<div class="carousel" id="car">
  <div class="track">
  <?php foreach ($PERFORMANCES as $p): ?>
    <figure class="slide"><?= img('gallery',$p['photo'],$p['title']) ?><div class="ph"><?= e($p['photo']) ?></div>
    <figcaption><strong><?= e($p['title']) ?></strong> <?= e($p['venue']) ?>, <?= e($p['date']) ?></figcaption></figure>
  <?php endforeach; ?>
  </div>
  <button class="nav prev" aria-label="Previous">&#8249;</button><button class="nav next" aria-label="Next">&#8250;</button>
</div></section>
<script>
(()=>{const c=document.getElementById('car'),t=c.querySelector('.track'),n=t.children.length;let i=0;
const go=k=>{i=(k+n)%n;t.style.transform='translateX(-'+i*100+'%)'};
c.querySelector('.prev').onclick=()=>go(i-1);c.querySelector('.next').onclick=()=>go(i+1);
let tm=setInterval(()=>go(i+1),5000);c.onmouseenter=()=>clearInterval(tm);})();
</script>
<?php require 'footer.php'; ?>
