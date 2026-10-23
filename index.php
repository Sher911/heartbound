<?php $title='Home'; require 'header.php'; ?>
<section class="hero">
  <div class="copy-block">
    <h1><span>Heartbound</span></h1>
    <p><?= e($SITE['about']) ?></p>
    <div class="btns"><a class="btn" href="booking.php">Book us for your event</a><a class="btn alt" href="members.php">Meet the dancers</a></div>
  </div>
  <div class="arch hslides" id="hcar" data-sec="<?= (int)($HOME_PHOTO_SECONDS ?? 4) ?>">
    <?php foreach (($HOME_PHOTOS ?? []) as $i => $ph) echo img('home', $ph, 'Heartbound photo '.($i+1), $i===0 ? 'on' : ''); ?>
    <div class="ph">Add photos to assets/home/<br>and list them in config.php</div>
    <?php if (count($HOME_PHOTOS ?? []) > 1): ?><div class="hdots"><?php foreach ($HOME_PHOTOS as $i => $ph): ?><button type="button" aria-label="Photo <?= $i+1 ?>"<?= $i===0 ? ' class="on"' : '' ?>></button><?php endforeach; ?></div><?php endif; ?>
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
// Home photo carousel: fades between the photos listed in config.php ($HOME_PHOTOS)
(()=>{const h=document.getElementById('hcar');if(!h)return;
const im=[...h.querySelectorAll('img')],dots=[...h.querySelectorAll('.hdots button')];let i=0,tm;
const ok=k=>im[k]&&im[k].style.display!=='none';
const show=k=>{im.forEach((x,j)=>x.classList.toggle('on',j===k));dots.forEach((x,j)=>x.classList.toggle('on',j===k));i=k;};
const next=()=>{for(let s=1;s<=im.length;s++){const k=(i+s)%im.length;if(ok(k))return show(k);}};
im.forEach((x,j)=>x.addEventListener('error',()=>{if(dots[j])dots[j].hidden=true;if(j===i)next();}));
const start=()=>{clearInterval(tm);if(im.length>1&&!matchMedia('(prefers-reduced-motion: reduce)').matches)tm=setInterval(next,(+h.dataset.sec||4)*1000);};
dots.forEach((d,j)=>d.onclick=()=>{show(j);start();});
start();})();
(()=>{const c=document.getElementById('car'),t=c.querySelector('.track'),n=t.children.length;let i=0;
const go=k=>{i=(k+n)%n;t.style.transform='translateX(-'+i*100+'%)'};
c.querySelector('.prev').onclick=()=>go(i-1);c.querySelector('.next').onclick=()=>go(i+1);
let tm=setInterval(()=>go(i+1),5000);c.onmouseenter=()=>clearInterval(tm);})();
</script>
<?php require 'footer.php'; ?>
