<?php
$csrf = csrf_token();   // start session before any output
$interest = (string)($_GET['interest'] ?? '');
$pre = (string)($_GET['product'] ?? '');
$date = (string)($_GET['date'] ?? '');
page_start(['title' => 'Get a Quote | JM Inflatable Hire', 'meta' => 'Request a quote for bouncy castle, assault course, inflatable game, Wipeout or rodeo bull hire. Fast replies from our events team.', 'path' => '/contact', 'crumbs' => ['Get a quote' => '/contact']]);
?>
<section class="wrap page-pad split">
  <div>
    <h1>Get an inflatable hire quote</h1>
    <p class="lead">Tell us about your event and we'll come back with availability, a fixed price and the space and power needed for each inflatable.</p>
    <ul class="contact-list">
      <li><b>Email</b><a href="mailto:<?= e(s('email')) ?>"><?= e(s('email')) ?></a></li>
      <?php if (s('phone')): ?><li><b>Phone</b><a href="tel:<?= e(preg_replace('/\s+/', '', (string)s('phone'))) ?>"><?= e(s('phone')) ?></a></li><?php endif; ?>
      <li><b>Coverage</b>UK-wide delivery, set-up &amp; collection</li>
    </ul>
  </div>
  <form class="enquiry" method="post" action="/api/enquiry">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <div class="hp" aria-hidden="true"><label>Leave blank<input name="website" tabindex="-1" autocomplete="off"></label></div>
    <div class="row2"><label>Name*<input name="name" required autocomplete="name"></label><label>Company<input name="company" autocomplete="organization"></label></div>
    <div class="row2"><label>Email*<input type="email" name="email" required autocomplete="email"></label><label>Phone<input type="tel" name="phone" autocomplete="tel"></label></div>
    <div class="row2"><label>Event date<input type="date" name="date" value="<?= e($date) ?>"></label><label>Event postcode / venue<input name="venue"></label></div>
    <label>Inflatable<select name="product"><option value="">Not sure – please advise</option><?php foreach (crm_products() as $p): ?><option value="<?= e($p['slug']) ?>"<?= $pre === $p['slug'] ? ' selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?></select></label>
    <div class="row2"><label>Quantity<input type="number" name="qty" min="1" max="20" value="<?= e((string)max(1, (int)($_GET['qty'] ?? 1))) ?>"></label><label>Days<input type="number" name="days" min="1" max="30" value="<?= e((string)max(1, (int)($_GET['days'] ?? 1))) ?>"></label></div>
    <label class="check"><input type="checkbox" name="staff" value="yes"<?= stripos($interest, 'staff') !== false ? ' checked' : '' ?>> I'd like staff / operators for the day</label>
    <label>Message<textarea name="message" rows="5"><?= e($interest ? "Interested in: $interest" : '') ?></textarea></label>
    <button class="btn btn-block" type="submit">Send enquiry</button>
    <p class="muted small">We'll only use your details to reply to your enquiry.</p>
  </form>
</section>
<?php page_end();
