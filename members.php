<?php $title='Members'; require 'header.php'; ?>
<section class="wrap"><h1 class="h1s">The dancers</h1><p class="lead">The people you will be booking. Tap a dancer to learn more about them.</p>
<div class="grid">
<?php foreach ($MEMBERS as $i=>$m): ?>
  <article class="card click" tabindex="0" role="button" data-i="<?= $i ?>" aria-label="View details for <?= e($m['name']) ?>">
  <div class="pic"><span><?= e(mb_substr($m['name'],0,1)) ?></span><?= img('members',$m['photo'],$m['name']) ?></div>
  <h3><?= e($m['name']) ?></h3><em><?= e($m['role']) ?></em><p><?= e($m['bio']) ?></p><small class="more">View details &#8250;</small></article>
<?php endforeach; ?>
</div></section>

<dialog id="mdlg" class="mdlg" aria-labelledby="mname">
  <button type="button" class="mclose" aria-label="Close">&times;</button>
  <div class="mbody">
    <div class="pic mpic"><span id="minit"></span><img id="mimg" alt=""></div>
    <div class="mtext">
      <h2 id="mname"></h2><em id="mrole"></em>
      <p id="mbio"></p>
      <dl id="mdl"></dl>
      <a class="btn" href="booking.php">Book us for your event</a>
    </div>
  </div>
</dialog>

<script>
(()=>{
const M=<?= json_encode($MEMBERS, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) ?>;
const L={fullname:'Full name',specialty:'Specialty',favourite:'Favourite K-pop / dance',funfact:'Fun fact'};
const d=document.getElementById('mdlg'),$=id=>document.getElementById(id);
function open(i){
  const m=M[i];
  $('minit').textContent=m.name.charAt(0);
  const im=$('mimg');im.style.display='';im.onerror=()=>im.style.display='none';
  im.src='assets/members/'+m.photo;im.alt=m.name;
  $('mname').textContent=m.name;$('mrole').textContent=m.role;$('mbio').textContent=m.bio||'';
  const dl=$('mdl');dl.innerHTML='';
  for(const k in L){ if(m[k]){const t=document.createElement('dt'),v=document.createElement('dd');
    t.textContent=L[k];v.textContent=m[k];dl.append(t,v);} }
  d.showModal();
}
document.querySelectorAll('.card.click').forEach(c=>{
  c.onclick=()=>open(c.dataset.i);
  c.onkeydown=e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();open(c.dataset.i);}};
});
d.querySelector('.mclose').onclick=()=>d.close();
d.onclick=e=>{if(e.target===d)d.close();};
})();
</script>
<?php require 'footer.php'; ?>
