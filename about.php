<?php $title='About us'; require 'header.php'; ?>
<section class="wrap">
  <h1 class="h1s">About Heartbound</h1>
  <div class="story">
    <p class="kr" lang="ko">하트바운드</p>
    <p><strong>Heartbound</strong>, also known as <strong lang="ko">하트바운드</strong>, is a group that is tied together from a core place of love.</p>
    <p>We are a group of 10 members building our dreams together. We don't just want to perform together. We want to create memories along the way, with each other and with everyone who shares a stage, a room or an event with us.</p>
  </div>
 
  <h2>Our past performances</h2>
  <?php if (!$PERFORMANCES): ?>
    <p class="lead">Our performance gallery is coming soon.</p>
  <?php else: ?>
  <div class="grid two">
  <?php foreach ($PERFORMANCES as $p): $meta=trim(implode(', ', array_filter([$p['venue'],$p['date']]))); $vi=video_info($p['video']??''); ?>
    <article class="card wide"><div class="pic land"><span>&#9829;</span><?= img('gallery',$p['photo'],$p['title']) ?></div>
    <h3><?= e($p['title']) ?></h3><?php if($meta): ?><em><?= e($meta) ?></em><?php endif; ?><?php if($p['text']): ?><p><?= nl2br(e($p['text'])) ?></p><?php endif; ?>
    <?php if($vi): ?><button type="button" class="btn vbtn" data-kind="<?= e($vi['kind']) ?>" data-src="<?= e($vi['src']) ?>" data-title="<?= e($p['title']) ?>">&#9654; Watch video</button><?php endif; ?></article>
  <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
<dialog id="vdlg" class="mdlg vdlg" aria-labelledby="vtitle">
  <button type="button" class="mclose" aria-label="Close">&times;</button>
  <div class="vbody"><h2 id="vtitle"></h2><div id="vstage"></div></div>
</dialog>
<script>
(()=>{
const d=document.getElementById('vdlg'),st=document.getElementById('vstage'),t=document.getElementById('vtitle');
document.querySelectorAll('.vbtn').forEach(b=>b.onclick=()=>{
  st.innerHTML='';let el;
  if(b.dataset.kind==='iframe'){
    el=document.createElement('iframe');el.src=b.dataset.src;el.title=b.dataset.title;
    el.allow='autoplay; encrypted-media; picture-in-picture; fullscreen';el.allowFullscreen=true;
  }else{
    el=document.createElement('video');el.controls=true;el.autoplay=true;el.playsInline=true;el.src=b.dataset.src;
  }
  st.append(el);t.textContent=b.dataset.title;d.showModal();
});
d.addEventListener('close',()=>{st.innerHTML='';});
d.querySelector('.mclose').onclick=()=>d.close();
d.onclick=e=>{if(e.target===d)d.close();};
})();
</script>
<?php require 'footer.php'; ?>
