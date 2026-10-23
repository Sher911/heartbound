<?php require_once __DIR__.'/db.php'; $cur = basename($_SERVER['SCRIPT_NAME']); ?>
<!doctype html><html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title ?? $SITE['name']) ?> | <?= e($SITE['name']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;800&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="icon" href="assets/logo.png">
<link rel="stylesheet" href="style.css"></head><body>
<div class="adminbar">
<?php if (is_admin()): ?>
  <span>Admin mode</span> <a href="admin.php">Dashboard</a> <a href="admin.php?out=1">Log out</a>
<?php else: ?>
  <details class="adm"><summary>Admin login</summary>
    <form method="post" action="admin.php"><input type="password" name="pw" placeholder="Admin password" aria-label="Admin password" required><button>Log in</button></form>
  </details>
<?php endif; ?>
</div>
<header class="top"><a class="brand" href="index.php"><img src="assets/logo.png" alt="<?= e($SITE['name']) ?> logo"><span><?= e($SITE['name']) ?></span></a>
<nav>
<?php foreach (['index.php'=>'Home','members.php'=>'Members','about.php'=>'About us','booking.php'=>'Event bookings'] as $f=>$l): ?>
<a href="<?= $f ?>" class="<?= $cur===$f?'on':'' ?>"><?= $l ?></a>
<?php endforeach; ?>
</nav></header><main>
