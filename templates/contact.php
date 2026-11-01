<?php
$csrf = csrf_token();   // start session before any output
$interest = (string)($_GET['interest'] ?? '');
$pre = (string)($_GET['product'] ?? '');
$date = (string)($_GET['date'] ?? '');
page_start(['title' => t('contact.title'), 'meta' => t('contact.meta'), 'path' => '/contact', 'crumbs' => [t('nav.cta') => '/contact']]);
$tel = fn($v) => preg_replace('/[^0-9+]/', '', $v);
?>
<section class="wrap page-pad split">
  <div>
    <h1><?= e(t('contact.h1')) ?></h1>
    <div class="lead-html"><?= t('contact.intro') ?></div>
    <ul class="contact-list">
      <?php foreach (tl('contact.details', [], true) as [$label, $val]):
        $out = strtr($val, ['{email}' => s('email'), '{phone}' => s('phone'), '{address}' => s('address'), '{hours}' => s('opening_hours')]);
        if (trim($out) === '') continue;
        if (str_contains($val, '{email}')) $html = '<a href="mailto:' . e($out) . '">' . e($out) . '</a>';
        elseif (str_contains($val, '{phone}')) $html = '<a href="tel:' . e($tel($out)) . '">' . e($out) . '</a>';
        else $html = nl2br(e($out)); ?>
        <li><b><?= e($label) ?></b><span><?= $html ?></span></li>
      <?php endforeach; ?>
    </ul>
    <?php if (t('contact.image') !== ''): ?><?= picture(t('contact.image'), s('site_name'), 'rounded') ?><?php endif; ?>
  </div>
  <form class="enquiry" method="post" action="/api/enquiry">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <div class="hp" aria-hidden="true"><label>Leave blank<input name="website" tabindex="-1" autocomplete="off"></label></div>
    <div class="row2"><label><?= e(t('contact.l_name')) ?><input name="name" required autocomplete="name"></label><label><?= e(t('contact.l_company')) ?><input name="company" autocomplete="organization"></label></div>
    <div class="row2"><label><?= e(t('contact.l_email')) ?><input type="email" name="email" required autocomplete="email"></label><label><?= e(t('contact.l_phone')) ?><input type="tel" name="phone" autocomplete="tel"></label></div>
    <div class="row2"><label><?= e(t('contact.l_date')) ?><input type="date" name="date" value="<?= e($date) ?>"></label><label><?= e(t('contact.l_venue')) ?><input name="venue"></label></div>
    <label><?= e(t('contact.l_product')) ?><select name="product"><option value=""><?= e(t('contact.l_notsure')) ?></option><?php foreach (crm_products() as $p): ?><option value="<?= e($p['slug']) ?>"<?= $pre === $p['slug'] ? ' selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?></select></label>
    <div class="row2"><label><?= e(t('contact.l_qty')) ?><input type="number" name="qty" min="1" max="20" value="<?= e((string)max(1, (int)($_GET['qty'] ?? 1))) ?>"></label><label><?= e(t('contact.l_days')) ?><input type="number" name="days" min="1" max="30" value="<?= e((string)max(1, (int)($_GET['days'] ?? 1))) ?>"></label></div>
    <label class="check"><input type="checkbox" name="staff" value="yes"<?= stripos($interest, 'staff') !== false ? ' checked' : '' ?>> <?= e(t('contact.l_staff')) ?></label>
    <label><?= e(t('contact.l_message')) ?><textarea name="message" rows="5"><?= e($interest ? "Interested in: $interest" : '') ?></textarea></label>
    <button class="btn btn-block" type="submit"><?= e(t('contact.button')) ?></button>
    <p class="muted small"><?= e(t('contact.privacy')) ?></p>
  </form>
</section>
<?php page_end();
