<?php
declare(strict_types=1);
require dirname(__DIR__) . '/inc/functions.php';
require dirname(__DIR__) . '/inc/totp.php';
start_session();
header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');

$d = data();
$admin = $d['admin'] ?? [];
$msg = ''; $err = '';
$tab = preg_replace('/[^a-z]/', '', (string)($_GET['tab'] ?? 'settings')) ?: 'settings';

function admin_shell_start(string $title): void { ?>
<!doctype html><html lang="en-GB"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?> · Admin</title><meta name="robots" content="noindex,nofollow">
<style>
:root{--red:#D7141A}*{box-sizing:border-box}body{margin:0;font:15px/1.5 system-ui,sans-serif;background:#f4f4f6;color:#1a1a22}
header{background:#0b0b0f;color:#fff;padding:12px 20px;display:flex;justify-content:space-between;align-items:center}header a{color:#fff}
.tabs{display:flex;gap:4px;flex-wrap:wrap;padding:10px 20px;background:#fff;border-bottom:1px solid #ddd}
.tabs a{padding:8px 14px;border-radius:8px;text-decoration:none;color:#333;font-weight:600}.tabs a.on{background:var(--red);color:#fff}
main{max-width:1100px;margin:20px auto;padding:0 16px}.box{background:#fff;border:1px solid #e2e2e8;border-radius:12px;padding:20px;margin-bottom:16px}
label{display:block;font-weight:600;font-size:13px;margin:10px 0 4px}input,select,textarea{width:100%;padding:8px 10px;border:1px solid #ccc;border-radius:8px;font:inherit}
textarea{min-height:90px;font-family:ui-monospace,monospace;font-size:13px}.g2{display:grid;grid-template-columns:1fr 1fr;gap:12px}.g3{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
@media(max-width:700px){.g2,.g3{grid-template-columns:1fr}}
button,.btn{background:var(--red);color:#fff;border:0;border-radius:8px;padding:10px 18px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block}
.btn2{background:#333}.ok{background:#e7f8ee;border:1px solid #9bd8b3;padding:10px 14px;border-radius:8px;margin-bottom:12px}.err{background:#fde8e8;border:1px solid #f0a0a0;padding:10px 14px;border-radius:8px;margin-bottom:12px}
fieldset{border:1px solid #e2e2e8;border-radius:10px;margin:0 0 14px;padding:12px 16px}legend{font-weight:700;padding:0 6px}
.thumbs{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px}.thumbs img{border-radius:8px;aspect-ratio:4/3;object-fit:cover;width:100%}
.save{position:sticky;bottom:0;background:#fff;padding:12px;border-top:1px solid #ddd;margin:0 -20px -20px;border-radius:0 0 12px 12px}
small{color:#666}.login{max-width:400px;margin:60px auto}
code{background:#f0f0f3;padding:2px 6px;border-radius:4px}
</style></head><body>
<?php }
function admin_shell_end(): void { echo '</body></html>'; }

/* ---------------- first-run setup ---------------- */
if (empty($admin['pass_hash']) || empty($admin['totp_secret'])) {
    if (empty($_SESSION['setup_secret'])) $_SESSION['setup_secret'] = totp_new_secret();
    $secret = $_SESSION['setup_secret'];
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
        $u = trim((string)($_POST['user'] ?? '')); $p1 = (string)($_POST['pass'] ?? '');
        if (!empty($admin['pass_hash']) && !password_verify((string)($_POST['old'] ?? ''), $admin['pass_hash'])) $err = 'Enter your current password to reset 2-step sign-in.';
        elseif (strlen($p1) < 10) $err = 'Use a password of at least 10 characters.';
        elseif (!totp_verify($secret, (string)($_POST['code'] ?? ''))) $err = 'That authenticator code didn\'t match – check the time on your phone and try again.';
        else {
            $d['admin'] = ['user' => $u ?: 'admin', 'pass_hash' => password_hash($p1, PASSWORD_DEFAULT), 'totp_secret' => $secret];
            save_data($d); unset($_SESSION['setup_secret']);
            $_SESSION['auth'] = true; session_regenerate_id(true);
            header('Location: ./'); exit;
        }
    }
    admin_shell_start('Set up admin'); ?>
<div class="login box"><h2>Set up your admin login</h2>
<p>1. Scan this with Google Authenticator / Microsoft Authenticator (or enter the key manually).</p>
<div id="qr" style="margin:10px 0"></div><p><small>Key: <code><?= e(chunk_split($secret, 4, ' ')) ?></code></small></p>
<?php if ($err): ?><div class="err"><?= e($err) ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<label>Username</label><input name="user" value="<?= e($admin['user'] ?? 'admin') ?>" required>
<?php if (!empty($admin['pass_hash'])): ?><label>Current password</label><input type="password" name="old" required><?php endif; ?>
<label>New password (10+ characters)</label><input type="password" name="pass" required minlength="10" autocomplete="new-password">
<label>2. Enter the 6-digit code from the app</label><input name="code" inputmode="numeric" pattern="\d{6}" required autocomplete="one-time-code">
<p><button>Save &amp; sign in</button></p></form></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>new QRCode(document.getElementById('qr'),{text:<?= json_encode(totp_uri($secret, 'admin', s('site_name'))) ?>,width:180,height:180});</script>
<?php admin_shell_end(); exit;
}

/* ---------------- login (password → 2FA code) ---------------- */
if (isset($_GET['logout'])) { session_destroy(); header('Location: ./'); exit; }
if (empty($_SESSION['auth'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
        if (rate_limited('login', 8, 900)) $err = 'Too many attempts. Try again in 15 minutes.';
        elseif (empty($_SESSION['pw_ok'])) {
            if (hash_equals($admin['user'], (string)($_POST['user'] ?? '')) && password_verify((string)($_POST['pass'] ?? ''), $admin['pass_hash'])) $_SESSION['pw_ok'] = time();
            else $err = 'Incorrect username or password.';
        } else {
            if (time() - (int)$_SESSION['pw_ok'] > 300) { unset($_SESSION['pw_ok']); $err = 'Code timed out – sign in again.'; }
            elseif (totp_verify($admin['totp_secret'], (string)($_POST['code'] ?? ''))) { unset($_SESSION['pw_ok']); session_regenerate_id(true); $_SESSION['auth'] = true; header('Location: ./'); exit; }
            else $err = 'Incorrect code.';
        }
    }
    admin_shell_start('Sign in'); ?>
<div class="login box"><h2>Admin sign in</h2><?php if ($err): ?><div class="err"><?= e($err) ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<?php if (empty($_SESSION['pw_ok'])): ?>
<label>Username</label><input name="user" required autocomplete="username"><label>Password</label><input type="password" name="pass" required autocomplete="current-password">
<?php else: ?>
<label>6-digit code from your authenticator app</label><input name="code" inputmode="numeric" pattern="\d{6}" required autofocus autocomplete="one-time-code">
<?php endif; ?>
<p><button>Continue</button></p></form></div>
<?php admin_shell_end(); exit;
}

/* ---------------- saving ---------------- */
$builtinImgs = array_values(array_unique(array_map(fn($f) => preg_replace('/(-480|-800)?\.webp$/', '', basename($f)), glob(ROOT . '/assets/img/*.webp'))));
$uploads = array_map(fn($f) => 'uploads/' . basename($f), glob(ROOT . '/assets/img/uploads/*.{webp,jpg,jpeg,png}', GLOB_BRACE));
$allImgs = array_merge($builtinImgs, $uploads);
$clean = fn($v, $max = 5000) => trim(mb_substr((string)$v, 0, $max));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { $err = 'Session expired – please try again.'; }
    else {
        $act = $_POST['act'] ?? '';
        if ($act === 'settings') {
            foreach ($d['settings'] as $k => $v) {
                if (is_bool($v)) $d['settings'][$k] = !empty($_POST['s'][$k]);
                elseif (is_int($v)) $d['settings'][$k] = (int)($_POST['s'][$k] ?? $v);
                elseif ($k === 'crm_api_key' && ($_POST['s'][$k] ?? '') === '') continue;   // blank = keep existing key
                else $d['settings'][$k] = $clean($_POST['s'][$k] ?? '', 500);
            }
            if (!array_key_exists('prefer_crm_images', $d['settings'])) $d['settings']['prefer_crm_images'] = !empty($_POST['s']['prefer_crm_images']);
            $msg = 'Settings saved.';
        } elseif ($act === 'crm_sync') {
            $list = crm_fetch_products(true);
            $msg = $list === null ? '' : 'Synced ' . count($list) . ' products from the CRM.';
            if ($list === null) $err = 'Could not reach the CRM. Check the base URL, API key and products path.';
        } elseif ($act === 'products') {
            $out = [];
            foreach ($_POST['p'] ?? [] as $row) {
                if (!empty($row['remove']) || trim($row['name'] ?? '') === '') continue;
                $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($row['slug'] ?: $row['name'])), '-');
                $prices = ['1' => (float)$row['price_from']];
                foreach (['2', '3', '4', '7'] as $dd) if (($row['p' . $dd] ?? '') !== '') $prices[$dd] = (float)$row['p' . $dd];
                $spec = array_filter(['dimensions' => $clean($row['dimensions'] ?? '', 120), 'power' => $clean($row['power'] ?? '', 120), 'setup' => $clean($row['setup'] ?? '', 60)]);
                $out[] = ['slug' => $slug, 'name' => $clean($row['name'], 200), 'cats' => array_values(array_filter(array_map('trim', explode(',', (string)$row['cats'])))),
                    'price_from' => (float)$row['price_from'], 'prices' => $prices, 'players' => $clean($row['players'], 40), 'image' => in_array($row['image'], $allImgs, true) ? $row['image'] : 'inflatable-hire-hero',
                    'short' => $clean($row['short'], 600), 'spec' => $spec, 'crm_id' => $clean($row['crm_id'], 80), 'crm_slug' => $clean($row['crm_slug'] ?? '', 120), 'staffed' => !empty($row['staffed'])];
            }
            $d['products'] = $out; $msg = 'Products saved.';
        } elseif ($act === 'page' && isset($d['pages'][$_POST['key'] ?? ''])) {
            $k = $_POST['key']; $pg = &$d['pages'][$k];
            foreach (['title', 'meta', 'h1', 'kicker', 'intro', 'image'] as $f) $pg[$f] = $clean($_POST[$f] ?? '', 1000);
            $pg['sections'] = [];
            foreach ($_POST['sec'] ?? [] as $sx) if (trim($sx['h2'] ?? '') !== '' && empty($sx['remove'])) $pg['sections'][] = ['h2' => $clean($sx['h2'], 200), 'html' => $clean($sx['html'], 20000)];
            $pg['faq'] = [];
            foreach ($_POST['faq'] ?? [] as $fx) if (trim($fx['q'] ?? '') !== '' && empty($fx['remove'])) $pg['faq'][] = ['q' => $clean($fx['q'], 300), 'a' => $clean($fx['a'], 3000)];
            unset($pg); $msg = 'Page saved.';
        } elseif ($act === 'locations') {
            $out = [];
            foreach ($_POST['l'] ?? [] as $row) {
                if (!empty($row['remove']) || trim($row['city'] ?? '') === '') continue;
                $out[] = ['slug' => trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($row['city'])), '-'), 'city' => $clean($row['city'], 80), 'region' => $clean($row['region'], 80),
                    'nearby' => $clean($row['nearby'], 300), 'venues' => $clean($row['venues'], 300), 'intro' => $clean($row['intro'], 1000)];
            }
            $d['locations'] = $out; $msg = 'Locations saved.';
        } elseif ($act === 'gallery') {
            $out = [];
            foreach ($_POST['g'] ?? [] as $row) if (empty($row['remove']) && in_array($row['img'], $allImgs, true)) $out[] = ['img' => $row['img'], 'alt' => $clean($row['alt'], 200)];
            if (!empty($_FILES['upload']['tmp_name']) && is_uploaded_file($_FILES['upload']['tmp_name'])) {
                $info = @getimagesize($_FILES['upload']['tmp_name']);
                if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true) || $_FILES['upload']['size'] > 12e6) $err = 'Upload a JPG, PNG or WebP under 12MB.';
                else {
                    $base = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(pathinfo($_FILES['upload']['name'], PATHINFO_FILENAME))), '-') ?: 'image';
                    $name = $base . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
                    $dest = ROOT . '/assets/img/uploads/' . $name;
                    if (function_exists('imagecreatefromstring') && function_exists('imagewebp')) {   // re-encode: strips anything that isn't pixels
                        $im = imagecreatefromstring((string)file_get_contents($_FILES['upload']['tmp_name']));
                        if ($im && imagesx($im) > 1600) $im = imagescale($im, 1600);
                        $ok = $im && imagewebp($im, $dest . '.webp', 80); $file = $name . '.webp';
                    } else {
                        $ext = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'][$info[2]];
                        $ok = move_uploaded_file($_FILES['upload']['tmp_name'], "$dest.$ext"); $file = "$name.$ext";
                    }
                    if ($ok) { $out[] = ['img' => 'uploads/' . $file, 'alt' => $clean($_POST['upload_alt'] ?? 'Inflatable hire', 200)]; $msg = 'Image uploaded. '; }
                    else $err = 'Could not save the image.';
                }
            }
            $d['gallery'] = $out; $msg .= 'Gallery saved.';
        } elseif ($act === 'password') {
            if (!password_verify((string)$_POST['old'], $admin['pass_hash'])) $err = 'Current password is wrong.';
            elseif (strlen((string)$_POST['new']) < 10) $err = 'New password must be 10+ characters.';
            else { $d['admin']['pass_hash'] = password_hash((string)$_POST['new'], PASSWORD_DEFAULT); $msg = 'Password changed.'; }
        }
        if (!$err && $act !== 'crm_sync') { if (!save_data($d)) $err = 'Could not write data/content.json – check folder permissions.'; }
        if (!$err) { $_SESSION['flash'] = $msg; header('Location: ?tab=' . $tab . (isset($_POST['key']) ? '&key=' . urlencode((string)$_POST['key']) : '')); exit; }
    }
}
$msg = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']);
$d = json_decode((string)file_get_contents(DATA_FILE), true);

admin_shell_start('Admin');
$tabs = ['settings' => 'Settings', 'crm' => 'CRM & stock', 'products' => 'Products', 'pages' => 'Pages', 'locations' => 'Locations', 'gallery' => 'Gallery', 'enquiries' => 'Enquiries', 'account' => 'Account'];
$imgSelect = function (string $name, string $cur) use ($allImgs) {
    $h = '<select name="' . e($name) . '">'; foreach ($allImgs as $i) $h .= '<option' . ($i === $cur ? ' selected' : '') . '>' . e($i) . '</option>'; return $h . '</select>';
};
?>
<header><b><?= e(s('site_name')) ?> · Admin</b><span><a href="/" target="_blank">View site</a> · <a href="?logout=1">Sign out</a></span></header>
<nav class="tabs"><?php foreach ($tabs as $k => $l): ?><a href="?tab=<?= $k ?>" class="<?= $tab === $k ? 'on' : '' ?>"><?= $l ?></a><?php endforeach; ?></nav>
<main>
<?php if ($msg): ?><div class="ok"><?= e($msg) ?></div><?php endif; ?><?php if ($err): ?><div class="err"><?= e($err) ?></div><?php endif; ?>
<?php $csrf = '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';

if ($tab === 'settings'): ?>
<form method="post" class="box"><?= $csrf ?><input type="hidden" name="act" value="settings"><h2>Site settings</h2>
<?php foreach ($d['settings'] as $k => $v): if (str_starts_with($k, 'crm_') || $k === 'prefer_crm_images') continue; ?>
  <label><?= e(ucwords(str_replace('_', ' ', $k))) ?></label>
  <?php if (is_bool($v)): ?><input type="checkbox" name="s[<?= e($k) ?>]" value="1" <?= $v ? 'checked' : '' ?> style="width:auto"><?php else: ?><input name="s[<?= e($k) ?>]" value="<?= e((string)$v) ?>"><?php endif; ?>
<?php endforeach; foreach ($d['settings'] as $k => $v) if (str_starts_with($k, 'crm_') || $k === 'prefer_crm_images') echo '<input type="hidden" name="s[' . e($k) . ']" value="' . e(is_bool($v) ? ($v ? '1' : '') : ($k === 'crm_api_key' ? '' : (string)$v)) . '">'; ?>
<p><small>Base URL must be the live domain (e.g. https://www.yourdomain.co.uk) – it's used for canonical tags, the sitemap and schema.</small></p>
<div class="save"><button>Save settings</button></div></form>

<?php elseif ($tab === 'crm'): $st = crm_status(); ?>
<div class="box"><h2>CRM connection</h2>
<p>Stock levels, prices, specs, spec sheets, product images and availability are pulled live from your CRM. Products are matched by <b>CRM ID</b> (or slug) on the Products tab; any inflatable stock in the CRM that isn't on the site yet is added automatically.</p>
<p>Status: <b><?= $st['enabled'] ? 'Enabled' : 'Disabled' ?></b> · Last sync: <b><?= $st['cached_at'] ? date('d M Y H:i', $st['cached_at']) : 'never' ?></b> · CRM products cached: <b><?= $st['count'] ?></b></p>
<form method="post" style="display:inline"><?= $csrf ?><input type="hidden" name="act" value="crm_sync"><button class="btn2">Sync now / test connection</button></form></div>
<form method="post" class="box"><?= $csrf ?><input type="hidden" name="act" value="settings"><h2>CRM settings</h2>
<?php foreach ($d['settings'] as $k => $v): if (!str_starts_with($k, 'crm_') && $k !== 'prefer_crm_images') { echo '<input type="hidden" name="s[' . e($k) . ']" value="' . e(is_bool($v) ? ($v ? '1' : '') : (string)$v) . '">'; continue; } ?>
  <label><?= e(ucwords(str_replace(['crm_', '_'], ['CRM ', ' '], $k))) ?></label>
  <?php if (is_bool($v)): ?><input type="checkbox" name="s[<?= e($k) ?>]" value="1" <?= $v ? 'checked' : '' ?> style="width:auto">
  <?php elseif ($k === 'crm_api_key'): ?><input type="password" name="s[crm_api_key]" placeholder="<?= $v ? '•••••••• (saved – leave blank to keep)' : 'Paste API key' ?>" autocomplete="off">
  <?php else: ?><input name="s[<?= e($k) ?>]" value="<?= e((string)$v) ?>"><?php endif; ?>
<?php endforeach; ?>
<?php if (!isset($d['settings']['prefer_crm_images'])): ?><label>Prefer CRM product images</label><input type="checkbox" name="s[prefer_crm_images]" value="1" checked style="width:auto"><?php endif; ?>
<p><small>Expected endpoints: <code>GET products path</code> → <code>{"products":[{id, slug, name, price_from, prices, stock_qty, images, spec, spec_sheet_url}]}</code>; <code>GET availability path?product=&amp;start=&amp;days=&amp;qty=</code> → <code>{available_qty, total_qty, status}</code>; <code>POST enquiry path</code> → <code>{ok, reference}</code>. All with <code>Authorization: Bearer &lt;key&gt;</code>.</small></p>
<div class="save"><button>Save CRM settings</button></div></form>

<?php elseif ($tab === 'products'): ?>
<form method="post" class="box"><?= $csrf ?><input type="hidden" name="act" value="products"><h2>Products</h2>
<p><small>Categories (comma-separated): <?php foreach ($d['pages'] as $pk => $pp) if (!empty($pp['cat']) && $pp['cat'] !== 'all') echo '<code>' . e($pp['cat']) . '</code> '; ?>– the first one decides the product's main page. Prices here are fallbacks – live CRM prices take priority when a CRM ID is set. Leave a multi-day price blank to use the standard rule.</small></p>
<?php foreach (array_merge($d['products'], [['slug' => '', 'name' => '', 'cats' => [], 'price_from' => '', 'prices' => [], 'spec' => [], 'players' => '', 'image' => '', 'short' => '', 'crm_id' => '']]) as $i => $p): ?>
<fieldset><legend><?= e($p['name'] ?: '+ Add new product') ?></legend>
<div class="g3"><div><label>Name</label><input name="p[<?= $i ?>][name]" value="<?= e($p['name']) ?>"></div><div><label>URL slug</label><input name="p[<?= $i ?>][slug]" value="<?= e($p['slug']) ?>"></div><div><label>CRM ID</label><input name="p[<?= $i ?>][crm_id]" value="<?= e($p['crm_id'] ?? '') ?>"></div></div>
<div class="g3"><div><label>Categories</label><input name="p[<?= $i ?>][cats]" value="<?= e(implode(', ', $p['cats'])) ?>"></div><div><label>1 day price (£)</label><input name="p[<?= $i ?>][price_from]" value="<?= e((string)$p['price_from']) ?>"></div><div><label>Suitable for</label><input name="p[<?= $i ?>][players]" value="<?= e($p['players']) ?>" placeholder="e.g. Adults &amp; children"></div></div>
<div class="g3"><?php foreach (['2', '3', '4', '7'] as $dd): ?><div><label><?= $dd ?> day price (£)</label><input name="p[<?= $i ?>][p<?= $dd ?>]" value="<?= e((string)($p['prices'][$dd] ?? '')) ?>"></div><?php endforeach; ?></div>
<div class="g3"><div><label>Size</label><input name="p[<?= $i ?>][dimensions]" value="<?= e($p['spec']['dimensions'] ?? '') ?>"></div><div><label>Power</label><input name="p[<?= $i ?>][power]" value="<?= e($p['spec']['power'] ?? '') ?>"></div><div><label>Set-up time</label><input name="p[<?= $i ?>][setup]" value="<?= e($p['spec']['setup'] ?? '') ?>"></div></div>
<div class="g3"><div><label>Image</label><?= $imgSelect("p[$i][image]", (string)$p['image']) ?></div><div><label>CRM slug (optional)</label><input name="p[<?= $i ?>][crm_slug]" value="<?= e($p['crm_slug'] ?? '') ?>"></div><div><label>&nbsp;</label><label style="font-weight:400"><input type="checkbox" style="width:auto" name="p[<?= $i ?>][staffed]" value="1" <?= !empty($p['staffed']) ? 'checked' : '' ?>> Staffed (full price every day)</label></div></div>
<label>Short description</label><textarea name="p[<?= $i ?>][short]"><?= e($p['short']) ?></textarea>
<?php if ($p['name']): ?><label style="font-weight:400"><input type="checkbox" style="width:auto" name="p[<?= $i ?>][remove]" value="1"> Remove this product</label><?php endif; ?>
</fieldset>
<?php endforeach; ?><div class="save"><button>Save products</button></div></form>

<?php elseif ($tab === 'pages'): $key = (string)($_GET['key'] ?? 'home'); if (!isset($d['pages'][$key])) $key = 'home'; $pg = $d['pages'][$key]; ?>
<div class="box"><label>Choose page</label><select onchange="location='?tab=pages&key='+this.value"><?php foreach ($d['pages'] as $k => $x): ?><option value="<?= e($k) ?>"<?= $k === $key ? ' selected' : '' ?>><?= e($x['h1']) ?></option><?php endforeach; ?></select></div>
<form method="post" class="box"><?= $csrf ?><input type="hidden" name="act" value="page"><input type="hidden" name="key" value="<?= e($key) ?>">
<h2><?= e($pg['h1']) ?></h2>
<label>SEO title <small>(aim for under 60 characters)</small></label><input name="title" value="<?= e($pg['title']) ?>" maxlength="90">
<label>Meta description <small>(aim for 140–155 characters)</small></label><textarea name="meta" style="min-height:60px"><?= e($pg['meta']) ?></textarea>
<div class="g2"><div><label>H1 heading</label><input name="h1" value="<?= e($pg['h1']) ?>"></div><div><label>Kicker (small line above H1)</label><input name="kicker" value="<?= e($pg['kicker'] ?? '') ?>"></div></div>
<label>Intro</label><textarea name="intro"><?= e($pg['intro']) ?></textarea>
<label>Main image</label><?= $imgSelect('image', (string)$pg['image']) ?>
<h3>Content sections <small>(HTML allowed: &lt;p&gt; &lt;ul&gt; &lt;li&gt; &lt;strong&gt; &lt;a&gt;)</small></h3>
<?php foreach (array_merge($pg['sections'], [['h2' => '', 'html' => '']]) as $i => $sx): ?>
<fieldset><legend><?= e($sx['h2'] ?: '+ Add section') ?></legend><label>Heading (H2)</label><input name="sec[<?= $i ?>][h2]" value="<?= e($sx['h2']) ?>"><label>Content</label><textarea name="sec[<?= $i ?>][html]" style="min-height:140px"><?= e($sx['html']) ?></textarea>
<?php if ($sx['h2']): ?><label style="font-weight:400"><input type="checkbox" style="width:auto" name="sec[<?= $i ?>][remove]" value="1"> Remove</label><?php endif; ?></fieldset>
<?php endforeach; ?>
<h3>FAQs</h3>
<?php foreach (array_merge($pg['faq'] ?? [], [['q' => '', 'a' => '']]) as $i => $fx): ?>
<fieldset><legend><?= e($fx['q'] ?: '+ Add FAQ') ?></legend><label>Question</label><input name="faq[<?= $i ?>][q]" value="<?= e($fx['q']) ?>"><label>Answer</label><textarea name="faq[<?= $i ?>][a]"><?= e($fx['a']) ?></textarea>
<?php if ($fx['q']): ?><label style="font-weight:400"><input type="checkbox" style="width:auto" name="faq[<?= $i ?>][remove]" value="1"> Remove</label><?php endif; ?></fieldset>
<?php endforeach; ?>
<div class="save"><button>Save page</button></div></form>

<?php elseif ($tab === 'locations'): ?>
<form method="post" class="box"><?= $csrf ?><input type="hidden" name="act" value="locations"><h2>Location pages</h2>
<p><small>Each location creates one page per local category (<?= e(implode(', ', array_map('page_label', local_keys()))) ?>). Adding real local venues and a custom intro makes each page stronger in Google.</small></p>
<?php foreach (array_merge($d['locations'], [['city' => '', 'region' => '', 'nearby' => '', 'venues' => '', 'intro' => '']]) as $i => $l): ?>
<fieldset><legend><?= e($l['city'] ?: '+ Add location') ?></legend>
<div class="g2"><div><label>Town / city</label><input name="l[<?= $i ?>][city]" value="<?= e($l['city']) ?>"></div><div><label>Region</label><input name="l[<?= $i ?>][region]" value="<?= e($l['region']) ?>"></div></div>
<div class="g2"><div><label>Nearby areas</label><input name="l[<?= $i ?>][nearby]" value="<?= e($l['nearby']) ?>"></div><div><label>Venues</label><input name="l[<?= $i ?>][venues]" value="<?= e($l['venues']) ?>"></div></div>
<label>Custom intro (optional)</label><textarea name="l[<?= $i ?>][intro]" style="min-height:60px"><?= e($l['intro']) ?></textarea>
<?php if ($l['city']): ?><label style="font-weight:400"><input type="checkbox" style="width:auto" name="l[<?= $i ?>][remove]" value="1"> Remove</label><?php endif; ?></fieldset>
<?php endforeach; ?><div class="save"><button>Save locations</button></div></form>

<?php elseif ($tab === 'gallery'): ?>
<form method="post" enctype="multipart/form-data" class="box"><?= $csrf ?><input type="hidden" name="act" value="gallery"><h2>Gallery &amp; images</h2>
<fieldset><legend>Upload a new photo</legend><input type="file" name="upload" accept="image/jpeg,image/png,image/webp"><label>Alt text (describe the photo – good for Google Images)</label><input name="upload_alt" placeholder="e.g. 100ft assault course at a corporate fun day in Leeds"></fieldset>
<div class="thumbs"><?php foreach ($d['gallery'] as $i => $g): ?>
<div><img src="<?= e(img_src($g['img'], 480)) ?>" alt=""><input type="hidden" name="g[<?= $i ?>][img]" value="<?= e($g['img']) ?>"><label>Alt text</label><input name="g[<?= $i ?>][alt]" value="<?= e($g['alt']) ?>"><label style="font-weight:400"><input type="checkbox" style="width:auto" name="g[<?= $i ?>][remove]" value="1"> Remove</label></div>
<?php endforeach; ?></div>
<div class="save"><button>Save gallery</button></div></form>

<?php elseif ($tab === 'enquiries'): $lines = is_file(ROOT . '/data/enquiries.log') ? array_reverse(file(ROOT . '/data/enquiries.log', FILE_IGNORE_NEW_LINES)) : []; ?>
<div class="box"><h2>Recent enquiries</h2><p><small>Every enquiry is also emailed to <?= e(s('notify_email')) ?> and sent to the CRM.</small></p>
<?php if (!$lines) echo '<p>No enquiries yet.</p>'; foreach (array_slice($lines, 0, 100) as $ln): $q = json_decode($ln, true); if (!$q) continue; ?>
<fieldset><legend><?= e($q['name']) ?> · <?= e(date('d M Y H:i', strtotime($q['at']))) ?> <?= $q['ref'] ? '· ' . e($q['ref']) : '' ?></legend>
<?= e($q['email']) ?> <?= e($q['phone']) ?> · <?= e($q['product_name']) ?> × <?= (int)$q['qty'] ?> · <?= e($q['date']) ?> · <?= e($q['venue']) ?><br><small><?= nl2br(e($q['message'])) ?></small></fieldset>
<?php endforeach; ?></div>

<?php elseif ($tab === 'account'): ?>
<form method="post" class="box"><?= $csrf ?><input type="hidden" name="act" value="password"><h2>Change password</h2>
<label>Current password</label><input type="password" name="old" required><label>New password (10+ characters)</label><input type="password" name="new" required minlength="10">
<p><button>Change password</button></p><p><small>To reset 2-step sign-in, clear <code>totp_secret</code> in data/content.json via IONOS file manager – the setup screen will appear on next visit.</small></p></form>
<?php endif; ?>
</main>
<?php admin_shell_end();
