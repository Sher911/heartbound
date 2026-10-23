<?php $title='About us'; require 'header.php'; ?>
<section class="wrap">
  <h1 class="h1s">About Heartbound</h1>
  <div class="story">
    <p class="kr" lang="ko">하트바운드</p>
    <p><strong>Heartbound</strong>, also known as <strong lang="ko">하트바운드</strong>, is a group that is tied together from a core place of love.</p>
    <p>We are a group of 10 members building our dreams together. We don't just want to perform together. We want to create memories along the way, with each other and with everyone who shares a stage, a room or an event with us.</p>
  </div>
 
  <h2>Our past performances</h2>
  <div class="grid two">
  <?php foreach ($PERFORMANCES as $p): ?>
    <article class="card wide"><div class="pic land"><span>&#9829;</span><?= img('gallery',$p['photo'],$p['title']) ?></div>
    <h3><?= e($p['title']) ?></h3><em><?= e($p['venue']) ?>, <?= e($p['date']) ?></em><p><?= e($p['text']) ?></p></article>
  <?php endforeach; ?>
  </div>
</section>
<?php require 'footer.php'; ?>
