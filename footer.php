</main>
<footer><div class="foot">
<div><strong><?= e($SITE['name']) ?></strong><p>Questions about an event? Get in touch.</p></div>
<div><p>Email: <a href="mailto:<?= e($SITE['email']) ?>"><?= e($SITE['email']) ?></a></p>
<p>Phone / WhatsApp: <a href="tel:<?= e($SITE['phone']) ?>"><?= e($SITE['phone']) ?></a></p>
<p>Instagram: <?= e($SITE['social']) ?></p><p>Based in <?= e($SITE['area']) ?></p></div>
</div><p class="copy">&copy; <?= date('Y') ?> <?= e($SITE['name']) ?></p></footer>
</body></html>
