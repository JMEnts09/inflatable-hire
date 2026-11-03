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
$tab = preg_replace('/[^a-z]/', '', (string)($_GET['tab'] ?? 'pages')) ?: 'pages';
function admin_shell_start(string $title): void { ?>
<!doctype html><html lang="en-GB"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?> · Admin</title><meta name="robots" content="noindex,nofollow">
<style>
:root{--red:#D7141A;--ink:#14141b;--mut:#6b6b78;--line:#e2e2e8;--bg:#f4f4f6;--card:#fff;--ok:#1f8a4c;--warn:#b26a00}
*{box-sizing:border-box}body{margin:0;font:15px/1.5 system-ui,-apple-system,"Segoe UI",sans-serif;background:var(--bg);color:var(--ink)}
a{color:var(--red)}
header.top{background:#0b0b0f;color:#fff;padding:10px max(16px,env(safe-area-inset-left));display:flex;justify-content:space-between;align-items:center;gap:12px;position:sticky;top:0;z-index:20}
header.top a{color:#fff;text-decoration:none;white-space:nowrap}header.top b{font-size:15px;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}header.top span{font-size:14px;white-space:nowrap}
.tabs{display:flex;gap:4px;overflow-x:auto;padding:8px 12px;background:#fff;border-bottom:1px solid var(--line);position:sticky;top:44px;z-index:19;-webkit-overflow-scrolling:touch}
.tabs a{white-space:nowrap;padding:7px 12px;border-radius:999px;text-decoration:none;color:#333;font-weight:600;font-size:14px}.tabs a.on{background:var(--red);color:#fff}
main{max-width:1060px;margin:18px auto;padding:0 max(14px,env(safe-area-inset-left))}
.box{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:18px;margin-bottom:16px}
h2{margin:0 0 10px;font-size:20px}h3{margin:18px 0 6px;font-size:16px}
label{display:block;font-weight:600;font-size:13px;margin:12px 0 4px}label small{font-weight:400;color:var(--mut)}
input,select,textarea{width:100%;padding:9px 11px;border:1px solid #cfcfd6;border-radius:8px;font:inherit;background:#fff}
input:focus,select:focus,textarea:focus,.rte-area:focus{outline:2px solid var(--red);outline-offset:1px}
textarea{min-height:80px}textarea.code{font-family:ui-monospace,monospace;font-size:13px}
.g2{display:grid;grid-template-columns:1fr 1fr;gap:0 14px}.g3{display:grid;grid-template-columns:repeat(3,1fr);gap:0 14px}.g5{display:grid;grid-template-columns:repeat(5,1fr);gap:0 10px}
@media(max-width:700px){.g2,.g3{grid-template-columns:1fr}.g5{grid-template-columns:repeat(3,1fr)}}
button,.btn{background:var(--red);color:#fff;border:0;border-radius:8px;padding:10px 18px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block;font-size:14px}
.btn2{background:#33333d}.btn3{background:#fff;color:var(--ink);border:1px solid #cfcfd6}.btn-sm{padding:6px 11px;font-size:13px}.danger{background:#fff;color:#b00020;border:1px solid #e9b6bd}
.ok{background:#e7f8ee;border:1px solid #9bd8b3;padding:10px 14px;border-radius:8px;margin-bottom:12px}.err{background:#fde8e8;border:1px solid #f0a0a0;padding:10px 14px;border-radius:8px;margin-bottom:12px}
fieldset{border:1px solid var(--line);border-radius:10px;margin:0 0 14px;padding:12px 14px}legend{font-weight:700;padding:0 6px}
.save{position:sticky;bottom:0;background:rgba(255,255,255,.96);padding:12px 18px;border-top:1px solid var(--line);margin:16px -18px -18px;border-radius:0 0 12px 12px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;z-index:5}
small,.muted{color:var(--mut)}.login{max-width:420px;margin:50px auto}code{background:#f0f0f3;padding:1px 5px;border-radius:4px}
.pills{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px}.pills a{padding:6px 12px;border:1px solid var(--line);border-radius:999px;text-decoration:none;color:var(--ink);font-size:13px;background:#fff}.pills a.on{border-color:var(--red);color:var(--red);font-weight:700}
.field-head{display:flex;justify-content:space-between;align-items:baseline;gap:8px}.reset{font-size:12px;color:var(--mut);background:none;border:0;padding:0;cursor:pointer;text-decoration:underline}
.count{font-size:12px;color:var(--mut);font-variant-numeric:tabular-nums}.count.bad{color:#b00020;font-weight:700}.count.good{color:var(--ok)}
/* image picker */
.imgf{display:flex;gap:12px;align-items:center;flex-wrap:wrap}.imgf .thumb{width:112px;height:84px;border-radius:8px;object-fit:cover;background:#eee;border:1px solid var(--line)}
.imgf .thumb.empty{display:grid;place-items:center;color:var(--mut);font-size:12px}.imgf .acts{display:flex;flex-direction:column;gap:6px}
.imgf input[type=file]{padding:6px;font-size:13px;max-width:240px}
.lib{position:fixed;inset:0;background:rgba(10,10,15,.6);z-index:50;display:none;padding:max(12px,env(safe-area-inset-top)) 12px 12px}.lib.open{display:flex}
.lib-in{background:#fff;border-radius:12px;margin:auto;width:min(980px,100%);max-height:100%;display:flex;flex-direction:column}
.lib-top{display:flex;gap:10px;padding:12px;border-bottom:1px solid var(--line)}.lib-grid{flex:1 1 auto;min-height:0;align-content:start;grid-auto-rows:max-content;overflow:auto;padding:12px;display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px}
    .lib-grid button{display:block;width:100%;background:#fff;color:var(--ink);border:2px solid transparent;padding:0;border-radius:8px;overflow:hidden;font-weight:400;font-size:11px;text-align:left}
.lib-grid button:hover,.lib-grid button:focus{border-color:var(--red)}.lib-grid img{width:100%;height:96px;object-fit:cover;display:block;background:#eee}.lib-grid span{display:block;padding:3px 5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
/* rich text */
.rte{border:1px solid #cfcfd6;border-radius:8px;background:#fff}.rte-bar{display:flex;flex-wrap:wrap;gap:2px;padding:5px;border-bottom:1px solid var(--line);background:#fafafb;border-radius:8px 8px 0 0}
.rte-bar button{background:#fff;color:var(--ink);border:1px solid var(--line);padding:4px 9px;font-size:13px;font-weight:600;border-radius:6px}
.rte-area{min-height:120px;padding:10px 12px;line-height:1.55}.rte-area h2,.rte-area h3{margin:.6em 0 .3em}.rte-area p{margin:.4em 0}
.rte textarea{border:0;border-radius:0 0 8px 8px;min-height:160px;font-family:ui-monospace,monospace;font-size:13px}
/* lists & tables */
.plist{display:grid;gap:8px}.prow{display:grid;grid-template-columns:64px 1fr auto;gap:12px;align-items:center;padding:8px;border:1px solid var(--line);border-radius:10px;background:#fff;text-decoration:none;color:var(--ink)}
.prow:hover{border-color:var(--red)}.prow img,.prow .ph{width:64px;height:48px;border-radius:6px;object-fit:cover;background:#eee}.prow b{display:block}.prow small{display:block}
.badge{display:inline-block;font-size:11px;font-weight:700;padding:2px 7px;border-radius:999px;background:#eee;color:#444}.badge.off{background:#fde8e8;color:#b00020}
.mgrid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px}.mgrid figure{margin:0;border:1px solid var(--line);border-radius:10px;overflow:hidden;background:#fff}
.mgrid img{width:100%;aspect-ratio:4/3;object-fit:cover;display:block}.mgrid figcaption{padding:6px 8px;font-size:12px;word-break:break-all}.mgrid form{padding:0 8px 8px}
.serp{border:1px solid var(--line);border-radius:10px;padding:10px 12px;margin-top:8px;background:#fff;font-family:arial,sans-serif}
.serp .u{color:#202124;font-size:12px}.serp .t{color:#1a0dab;font-size:18px;line-height:1.3;margin:2px 0}.serp .m{color:#4d5156;font-size:13px}
.seo-row{border-bottom:1px solid var(--line);padding:12px 0}.seo-row:last-child{border:0}
.extra{display:flex;flex-wrap:wrap;gap:10px}.extra label{font-weight:400;margin:0;text-align:center;font-size:12px}.extra img{width:100px;height:75px;object-fit:cover;border-radius:6px;display:block}
.chk{display:flex;gap:8px;align-items:center;font-weight:400;margin:8px 0}.chk input{width:auto}
.chips{display:flex;flex-wrap:wrap;gap:6px}.chips label{display:flex;gap:6px;align-items:center;font-weight:400;margin:0;border:1px solid var(--line);border-radius:999px;padding:5px 11px;background:#fff}.chips input{width:auto}
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

/* ================================================================== saving */
$clean = fn($v, $max = 5000) => trim(mb_substr(str_replace("\0", '', (string)$v), 0, $max));
$slugify = fn(string $s) => trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(str_replace(['&', "'"], ['and', ''], $s))), '-');

/** Settings shown in admin, grouped. Anything else already in content.json is kept untouched. */
$SETTINGS = [
    'Business & branding' => ['site_name' => 'Website name', 'tagline' => 'Tagline (used in Google & social shares)', 'company' => 'Company name', 'parent_site' => 'Main company website', 'accent' => 'Accent colour (e.g. #D7141A)'],
    'Contact details' => ['email' => 'Email shown on the site', 'phone' => 'Phone number', 'address' => 'Address (shown on contact page & footer)', 'opening_hours' => 'Opening hours', 'notify_email' => 'Send enquiries to'],
    'SEO & tracking' => ['base_url' => 'Live website address (e.g. https://www.yourdomain.co.uk)', 'default_share_image' => 'Default image for social shares', 'ga4_id' => 'Google Analytics 4 ID (G-…)', 'gsc_verification' => 'Google Search Console verification code', 'noindex_site' => 'Hide the whole site from Google (staging only)'],
];
$BOOL_SETTINGS = ['noindex_site', 'crm_enabled', 'prefer_crm_images', 'crm_use_descriptions', 'crm_auto_add'];
$IMAGE_SETTINGS = ['default_share_image'];

/** Handle an image field: an uploaded file wins, otherwise the picked library value. */
$imagePost = function (string $field, string $fileKey) use (&$err): string {
    if (!empty($_FILES[$fileKey]['tmp_name']) && ($_FILES[$fileKey]['error'] ?? 1) === UPLOAD_ERR_OK) {
        $e = null; $n = save_upload($_FILES[$fileKey], $e);
        if ($n) return $n;
        $err = $e;
    }
    $v = (string)($_POST[$field] ?? '');
    return ($v === '' || img_exists($v)) ? $v : '';
};

$catOptions = [];
foreach ($d['pages'] as $k => $pg) if (!empty($pg['cat']) && $pg['cat'] !== 'all') $catOptions[$pg['cat']] = $pg['h1'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = (string)($_POST['act'] ?? '');
    $back = '?tab=' . $tab;
    if (!csrf_ok()) { $err = 'Your session expired – please try again.'; }
    else {
        switch ($act) {
            case 'settings':
                foreach ((array)($_POST['s'] ?? []) as $k => $v) {
                    $k = (string)$k;
                    if (in_array($k, $BOOL_SETTINGS, true)) continue;
                    if ($k === 'crm_api_key' && $v === '') continue;          // blank = keep existing key
                    if ($k === 'crm_cache_seconds') { $d['settings'][$k] = max(10, (int)$v); continue; }
                    $d['settings'][$k] = $clean($v, 500);
                }
                foreach ((array)($_POST['bools'] ?? []) as $k) if (in_array($k, $BOOL_SETTINGS, true)) $d['settings'][$k] = !empty($_POST['s'][$k]);
                foreach ($IMAGE_SETTINGS as $k) if (isset($_POST['s_img'][$k]) || !empty($_FILES['up_' . $k]['tmp_name'])) {
                    $_POST['_img_' . $k] = $_POST['s_img'][$k] ?? '';
                    $d['settings'][$k] = $imagePost('_img_' . $k, 'up_' . $k);
                }
                $msg = 'Settings saved.';
                if (!empty($_POST['back'])) $back = '?tab=' . preg_replace('/[^a-z]/', '', (string)$_POST['back']);
                break;

            case 'crm_sync':
                $list = crm_fetch_products(true);
                if ($list === null || empty($list['rows'])) $err = 'Could not reach the CRM feed. Check the feed address under CRM settings – the site keeps showing the last copy meanwhile.';
                else $msg = 'Synced ' . count($list['rows']) . ' products and ' . array_sum(array_map(fn($r) => count($r['photos'] ?? []), $list['rows'])) . ' photos from the CRM.';
                break;

            case 'text':
                $fields = text_fields();
                foreach ((array)($_POST['t'] ?? []) as $k => $v) {
                    if (!isset($fields[$k])) continue;
                    $type = $fields[$k][2];
                    if ($type === 'image') { $_POST['_ti'] = $v; $v = $imagePost('_ti', 'tu_' . str_replace('.', '_', $k)); }
                    $v = $clean($v, $type === 'html' ? 40000 : 8000);
                    if ($v === trim((string)$fields[$k][3])) unset($d['text'][$k]); else $d['text'][$k] = $v;
                }
                $msg = 'Text saved – it\'s live on the site now.';
                $back = '?tab=' . $tab . '&g=' . urlencode((string)($_POST['g'] ?? ''));
                break;

            case 'page':
                $k = (string)($_POST['key'] ?? '');
                if (!isset($d['pages'][$k])) { $err = 'Unknown page.'; break; }
                $pg = &$d['pages'][$k];
                foreach (['title' => 200, 'meta' => 400, 'h1' => 200, 'kicker' => 200, 'intro' => 2000, 'image_alt' => 200, 'label' => 100, 'angle' => 300] as $f => $max)
                    if (isset($_POST[$f])) $pg[$f] = $clean($_POST[$f], $max);
                $pg['image'] = $imagePost('image', 'up_image') ?: $pg['image'];
                if (isset($pg['cat'])) $pg['local'] = !empty($_POST['local']);
                $pg['sections'] = [];
                foreach ((array)($_POST['sec'] ?? []) as $sx) if (trim($sx['h2'] ?? '') !== '' && empty($sx['remove'])) $pg['sections'][] = ['h2' => $clean($sx['h2'], 200), 'html' => $clean($sx['html'] ?? '', 40000)];
                $pg['faq'] = [];
                foreach ((array)($_POST['faq'] ?? []) as $fx) if (trim($fx['q'] ?? '') !== '' && empty($fx['remove'])) $pg['faq'][] = ['q' => $clean($fx['q'], 300), 'a' => $clean($fx['a'] ?? '', 3000)];
                unset($pg);
                $msg = 'Page saved.'; $back = '?tab=pages&key=' . urlencode($k);
                break;

            case 'product':
                $i = (string)($_POST['i'] ?? 'new');
                $old = ($i !== 'new' && isset($d['products'][(int)$i])) ? $d['products'][(int)$i] : [];
                $name = $clean($_POST['name'] ?? '', 200);
                if ($name === '') { $err = 'Give the product a name.'; break; }
                $slug = $slugify((string)($_POST['slug'] ?? '') ?: $name);
                foreach ($d['products'] as $j => $q) if ($q['slug'] === $slug && (string)$j !== $i) { $err = "Another product already uses the web address /hire/$slug."; break 2; }
                $prices = [];
                foreach (['1', '2', '3', '4', '7'] as $dd) if (($_POST['p' . $dd] ?? '') !== '') $prices[$dd] = (float)$_POST['p' . $dd];
                $images = [];
                foreach ((array)($old['images'] ?? []) as $x) if (!in_array($x, (array)($_POST['rm_extra'] ?? []), true)) $images[] = $x;
                if (!empty($_FILES['up_extra']['name']) && is_array($_FILES['up_extra']['name'])) {
                    foreach ($_FILES['up_extra']['name'] as $n => $_) {
                        if (($_FILES['up_extra']['error'][$n] ?? 1) !== UPLOAD_ERR_OK) continue;
                        $e = null; $one = ['name' => $_FILES['up_extra']['name'][$n], 'tmp_name' => $_FILES['up_extra']['tmp_name'][$n], 'size' => $_FILES['up_extra']['size'][$n]];
                        if ($nm = save_upload($one, $e)) $images[] = $nm; else $err = $e;
                    }
                }
                $p = array_merge($old, [
                    'slug' => $slug, 'name' => $name,
                    'cats' => array_values(array_intersect(array_keys($catOptions), (array)($_POST['cats'] ?? []))) ?: ($old['cats'] ?? []),
                    'price_from' => (float)($_POST['p1'] ?? 0), 'prices' => $prices,
                    'players' => $clean($_POST['players'] ?? '', 60), 'short' => $clean($_POST['short'] ?? '', 600),
                    'description' => $clean($_POST['description'] ?? '', 40000),
                    'image' => $imagePost('image', 'up_image') ?: ($old['image'] ?? 'inflatable-hire-hero'),
                    'image_alt' => $clean($_POST['image_alt'] ?? '', 200), 'images' => $images,
                    'spec' => array_filter(['dimensions' => $clean($_POST['dimensions'] ?? '', 120), 'weight' => $clean($_POST['weight'] ?? '', 60), 'power' => $clean($_POST['power'] ?? '', 120), 'setup' => $clean($_POST['setup'] ?? '', 60)]),
                    'seo_title' => $clean($_POST['seo_title'] ?? '', 200), 'seo_meta' => $clean($_POST['seo_meta'] ?? '', 400),
                    'crm_id' => $clean($_POST['crm_id'] ?? '', 80), 'staffed' => !empty($_POST['staffed']), 'hidden' => !empty($_POST['hidden']),
                ]);
                if ($i === 'new') { $d['products'][] = $p; $i = (string)(count($d['products']) - 1); } else $d['products'][(int)$i] = $p;
                $msg = $err ? '' : 'Product saved.'; $back = '?tab=products&edit=' . $i;
                break;

            case 'product_delete':
                $i = (int)($_POST['i'] ?? -1);
                if (isset($d['products'][$i])) { $name = $d['products'][$i]['name']; array_splice($d['products'], $i, 1); $msg = "Deleted “{$name}”."; }
                $back = '?tab=products';
                break;

            case 'media_upload':
                $n = 0;
                if (!empty($_FILES['files']['name']) && is_array($_FILES['files']['name'])) {
                    foreach ($_FILES['files']['name'] as $j => $_) {
                        if (($_FILES['files']['error'][$j] ?? 1) !== UPLOAD_ERR_OK) continue;
                        $e = null; $one = ['name' => $_FILES['files']['name'][$j], 'tmp_name' => $_FILES['files']['tmp_name'][$j], 'size' => $_FILES['files']['size'][$j]];
                        if (save_upload($one, $e)) $n++; else $err = $e;
                    }
                }
                if ($n) $msg = "$n photo" . ($n > 1 ? 's' : '') . ' uploaded.'; elseif (!$err) $err = 'Choose at least one photo to upload.';
                break;

            case 'media_delete':
                $nm = (string)($_POST['name'] ?? '');
                $used = str_contains(json_encode($d), json_encode($nm));
                if ($used && empty($_POST['force'])) { $err = 'That photo is still used on the site. Change it there first, or tick “delete anyway”.'; break; }
                $msg = delete_upload($nm) ? 'Photo deleted.' : ''; if (!$msg) $err = 'Only uploaded photos can be deleted.';
                break;

            case 'gallery':
                $out = [];
                foreach ((array)($_POST['g'] ?? []) as $row) if (empty($row['remove']) && img_exists((string)($row['img'] ?? ''))) $out[] = ['img' => (string)$row['img'], 'alt' => $clean($row['alt'] ?? '', 200)];
                usort($out, fn($a, $b) => 0);
                if ($add = $imagePost('add_img', 'up_add')) $out[] = ['img' => $add, 'alt' => $clean($_POST['add_alt'] ?? '', 200) ?: s('site_name')];
                $d['gallery'] = $out; $msg = 'Gallery saved.';
                break;

            case 'seo':
                foreach ((array)($_POST['pg'] ?? []) as $k => $v) if (isset($d['pages'][$k])) { $d['pages'][$k]['title'] = $clean($v['title'] ?? '', 200); $d['pages'][$k]['meta'] = $clean($v['meta'] ?? '', 400); }
                foreach ((array)($_POST['t'] ?? []) as $k => $v) if (isset(text_fields()[$k])) { $v = $clean($v, 400); if ($v === text_fields()[$k][3]) unset($d['text'][$k]); else $d['text'][$k] = $v; }
                foreach ((array)($_POST['pr'] ?? []) as $j => $v) if (isset($d['products'][(int)$j])) { $d['products'][(int)$j]['seo_title'] = $clean($v['title'] ?? '', 200); $d['products'][(int)$j]['seo_meta'] = $clean($v['meta'] ?? '', 400); }
                $msg = 'SEO saved.';
                break;

            case 'locations':
                $out = [];
                foreach ((array)($_POST['l'] ?? []) as $row) {
                    if (!empty($row['remove']) || trim($row['city'] ?? '') === '') continue;
                    $out[] = ['slug' => $slugify((string)$row['city']), 'city' => $clean($row['city'], 80), 'region' => $clean($row['region'] ?? '', 80),
                        'nearby' => $clean($row['nearby'] ?? '', 300), 'venues' => $clean($row['venues'] ?? '', 300), 'intro' => $clean($row['intro'] ?? '', 1000),
                        'body' => $clean($row['body'] ?? '', 20000), 'faq' => array_values(array_filter(array_map(function ($line) use ($clean) {
                            $p = array_map('trim', explode('|', $line, 2)); return count($p) === 2 && $p[0] !== '' ? ['q' => $clean($p[0], 300), 'a' => $clean($p[1], 2000)] : null;
                        }, preg_split('/\R/', (string)($row['faq'] ?? '')))))];
                }
                $d['locations'] = $out; $msg = 'Locations saved.';
                break;

            case 'password':
                if (!password_verify((string)($_POST['old'] ?? ''), $admin['pass_hash'])) $err = 'Current password is wrong.';
                elseif (strlen((string)($_POST['new'] ?? '')) < 10) $err = 'New password must be 10+ characters.';
                else { $d['admin']['pass_hash'] = password_hash((string)$_POST['new'], PASSWORD_DEFAULT); $msg = 'Password changed.'; }
                break;

            default: $err = 'Unknown action.';
        }
        $noSave = in_array($act, ['crm_sync', 'media_upload', 'media_delete'], true);
        if (!$err || in_array($act, ['product', 'page', 'text'], true)) {
            if (!$noSave && !save_data($d)) $err = 'Could not write data/content.json – check folder permissions.';
        }
        if (!$err) { $_SESSION['flash'] = $msg; header('Location: ' . $back); exit; }
        if ($msg === '' && $err && in_array($act, ['product', 'page', 'text'], true)) { $_SESSION['flash_err'] = $err . ' Everything else was saved.'; header('Location: ' . $back); exit; }
    }
}
$msg = $msg ?: ($_SESSION['flash'] ?? ''); unset($_SESSION['flash']);
$err = $err ?: ($_SESSION['flash_err'] ?? ''); unset($_SESSION['flash_err']);
$d = json_decode((string)file_get_contents(DATA_FILE), true) ?: $d;

/* ================================================================== view helpers */
$media = media_library();
$csrf = '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';

/** Image field: preview + choose from library + upload new. */
function img_field(string $name, string $cur, string $fileName, bool $allowNone = false): string {
    $thumb = $cur !== '' && img_exists($cur) ? '<img class="thumb" src="' . e(img_src($cur, 480)) . '" alt="">' : '<div class="thumb empty">No image</div>';
    return '<div class="imgf" data-imgf>' . $thumb . '<div class="acts"><input type="hidden" name="' . e($name) . '" value="' . e($cur) . '">'
        . '<button type="button" class="btn3 btn-sm" data-pick>Choose from photos</button>'
        . ($allowNone ? '<button type="button" class="reset" data-clear>Remove image</button>' : '')
        . '<label style="margin:0;font-weight:400"><small>or upload a new photo</small><input type="file" name="' . e($fileName) . '" accept="image/*" data-up></label></div></div>';
}
function counted(string $label, string $name, string $val, int $ideal, bool $area = false, string $ph = ''): string {
    $id = 'f' . substr(md5($name), 0, 8);
    $inp = $area ? '<textarea id="' . $id . '" name="' . e($name) . '" rows="3" data-count="' . $ideal . '" placeholder="' . e($ph) . '">' . e($val) . '</textarea>'
                 : '<input id="' . $id . '" name="' . e($name) . '" value="' . e($val) . '" data-count="' . $ideal . '" placeholder="' . e($ph) . '">';
    return '<div class="field-head"><label for="' . $id . '">' . $label . '</label><span class="count" data-for="' . $id . '"></span></div>' . $inp;
}

admin_shell_start('Admin');
$tabs = ['pages' => 'Pages', 'products' => 'Products', 'text' => 'Site text', 'contact' => 'Contact page', 'seo' => 'SEO', 'media' => 'Photos', 'gallery' => 'Gallery', 'locations' => 'Towns', 'settings' => 'Settings', 'crm' => 'CRM & stock', 'enquiries' => 'Enquiries', 'account' => 'Account'];
if (!isset($tabs[$tab])) $tab = 'pages';
?>
<header class="top"><b><?= e(s('site_name')) ?> · Admin</b><span><a href="/" target="_blank">View site ↗</a> · <a href="?logout=1">Sign out</a></span></header>
<nav class="tabs" aria-label="Admin sections"><?php foreach ($tabs as $k => $l): ?><a href="?tab=<?= $k ?>" class="<?= $tab === $k ? 'on' : '' ?>"><?= $l ?></a><?php endforeach; ?></nav>
<main>
<?php if ($msg): ?><div class="ok" role="status"><?= e($msg) ?></div><?php endif; ?><?php if ($err): ?><div class="err" role="alert"><?= e($err) ?></div><?php endif; ?>

<?php /* ------------------------------------------------------------ PAGES */
if ($tab === 'pages'):
    $key = (string)($_GET['key'] ?? 'home'); if (!isset($d['pages'][$key])) $key = 'home'; $pg = $d['pages'][$key];
    $path = $key === 'home' ? '/' : '/' . $key; ?>
<div class="box"><label for="pgsel">Choose a page to edit</label>
<select id="pgsel" onchange="location='?tab=pages&key='+this.value"><?php foreach ($d['pages'] as $k => $x): ?><option value="<?= e($k) ?>"<?= $k === $key ? ' selected' : '' ?>><?= e($k === 'home' ? 'Home page' : $x['h1']) ?> (<?= e($k === 'home' ? '/' : '/' . $k) ?>)</option><?php endforeach; ?></select>
<p><small>Other wording (buttons, headings, contact page, footer) is under <a href="?tab=text">Site text</a>. Each product has its own page under <a href="?tab=products">Products</a>.</small></p></div>
<form method="post" enctype="multipart/form-data" class="box"><?= $csrf ?><input type="hidden" name="act" value="page"><input type="hidden" name="key" value="<?= e($key) ?>">
<h2><?= e($key === 'home' ? 'Home page' : $pg['h1']) ?> <a class="btn3 btn-sm" href="<?= e($path) ?>" target="_blank">View ↗</a></h2>
<fieldset><legend>Google &amp; social sharing</legend>
<?= counted('SEO title <small>(ideal 50–60 characters)</small>', 'title', $pg['title'] ?? '', 60) ?>
<?= counted('Meta description <small>(ideal 140–155 characters)</small>', 'meta', $pg['meta'] ?? '', 155, true) ?>
<div class="serp" data-serp><div class="u"><?= e(preg_replace('#^https?://#', '', base()) . $path) ?></div><div class="t"></div><div class="m"></div></div>
</fieldset>
<fieldset><legend>Top of the page</legend>
<div class="g2"><div><label for="h1">Main heading (H1)</label><input id="h1" name="h1" value="<?= e($pg['h1']) ?>"></div><div><label for="kicker">Small line above the heading</label><input id="kicker" name="kicker" value="<?= e($pg['kicker'] ?? '') ?>"></div></div>
<label for="intro">Intro paragraph</label><textarea id="intro" name="intro" rows="4"><?= e($pg['intro'] ?? '') ?></textarea>
<label>Main photo</label><?= img_field('image', (string)$pg['image'], 'up_image') ?>
<label for="image_alt">Photo description for Google &amp; screen readers <small>(alt text)</small></label><input id="image_alt" name="image_alt" value="<?= e($pg['image_alt'] ?? '') ?>" placeholder="e.g. Twin inflatable slide at a summer fair in Leeds">
<?php if (isset($pg['cat'])): ?>
<div class="g2"><div><label for="label">Short name <small>(used in town pages, e.g. “Bouncy Castle”)</small></label><input id="label" name="label" value="<?= e($pg['label'] ?? '') ?>"></div>
<div><label for="angle">Description used in town pages</label><input id="angle" name="angle" value="<?= e($pg['angle'] ?? '') ?>"></div></div>
<label class="chk"><input type="checkbox" name="local" value="1" <?= !empty($pg['local']) ? 'checked' : '' ?>> Create a page for every town (e.g. <?= e($path) ?>/leeds)</label>
<?php endif; ?>
</fieldset>
<fieldset><legend>Text sections</legend>
<?php foreach (array_merge($pg['sections'] ?? [], [['h2' => '', 'html' => '']]) as $i => $sx): ?>
<fieldset><legend><?= e($sx['h2'] ?: '+ Add a section') ?></legend>
<label for="sh<?= $i ?>">Heading</label><input id="sh<?= $i ?>" name="sec[<?= $i ?>][h2]" value="<?= e($sx['h2']) ?>">
<label>Text</label><textarea class="rich" name="sec[<?= $i ?>][html]"><?= e($sx['html']) ?></textarea>
<?php if ($sx['h2']): ?><label class="chk"><input type="checkbox" name="sec[<?= $i ?>][remove]" value="1"> Remove this section</label><?php endif; ?>
</fieldset>
<?php endforeach; ?></fieldset>
<fieldset><legend>FAQs <small>(shown at the bottom and in Google results)</small></legend>
<?php foreach (array_merge($pg['faq'] ?? [], [['q' => '', 'a' => '']]) as $i => $fx): ?>
<fieldset><legend><?= e($fx['q'] ?: '+ Add a question') ?></legend><label for="fq<?= $i ?>">Question</label><input id="fq<?= $i ?>" name="faq[<?= $i ?>][q]" value="<?= e($fx['q']) ?>"><label for="fa<?= $i ?>">Answer</label><textarea id="fa<?= $i ?>" name="faq[<?= $i ?>][a]"><?= e($fx['a']) ?></textarea>
<?php if ($fx['q']): ?><label class="chk"><input type="checkbox" name="faq[<?= $i ?>][remove]" value="1"> Remove</label><?php endif; ?></fieldset>
<?php endforeach; ?></fieldset>
<div class="save"><button>Save page</button><a class="btn3 btn-sm" href="<?= e($path) ?>" target="_blank">View page ↗</a></div></form>

<?php /* ------------------------------------------------------------ PRODUCTS */
elseif ($tab === 'products' && isset($_GET['edit'])):
    $i = (string)$_GET['edit']; $isNew = $i === 'new' || !isset($d['products'][(int)$i]);
    $p = $isNew ? ['slug' => '', 'name' => '', 'cats' => [], 'price_from' => '', 'prices' => [], 'players' => '', 'short' => '', 'description' => '', 'image' => '', 'images' => [], 'spec' => [], 'crm_id' => ''] : $d['products'][(int)$i];
    if ($isNew) $i = 'new'; $sp = $p['spec'] ?? []; ?>
<p><a href="?tab=products">← All products</a></p>
<form method="post" enctype="multipart/form-data" class="box"><?= $csrf ?><input type="hidden" name="act" value="product"><input type="hidden" name="i" value="<?= e($i) ?>">
<h2><?= $isNew ? 'Add a product' : e($p['name']) ?> <?php if (!$isNew): ?><a class="btn3 btn-sm" href="/hire/<?= e($p['slug']) ?>" target="_blank">View ↗</a><?php endif; ?></h2>
<fieldset><legend>Basics</legend>
<div class="g2"><div><label for="name">Product name</label><input id="name" name="name" value="<?= e($p['name']) ?>" required></div>
<div><label for="slug">Web address <small>/hire/…</small></label><input id="slug" name="slug" value="<?= e($p['slug']) ?>" placeholder="made from the name if empty"></div></div>
<label>Categories</label><div class="chips"><?php foreach ($catOptions as $c => $l): ?><label><input type="checkbox" name="cats[]" value="<?= e($c) ?>" <?= in_array($c, $p['cats'] ?? [], true) ? 'checked' : '' ?>> <?= e($l) ?></label><?php endforeach; ?></div>
<small>The first ticked category is the product’s main page.</small>
<label for="short">Short description <small>(shown on product cards and at the top of the page)</small></label><textarea id="short" name="short" rows="3"><?= e($p['short'] ?? '') ?></textarea>
<label>Full description <small>(the “About” section on the product page)</small></label><textarea class="rich" name="description"><?= e($p['description'] ?? '') ?></textarea>
</fieldset>
<?php $live = !$isNew && ($p['crm_id'] ?? '') !== '' ? (array_values(array_filter(crm_products(), fn($x) => ($x['crm_id'] ?? '') === $p['crm_id']))[0] ?? null) : null; ?>
<?php if ($live): ?><div class="ok" style="margin-top:12px"><b>Linked to CRM product <?= e($p['crm_id']) ?>.</b> The website shows the CRM’s prices (<?= e(implode(' · ', array_map(fn($d, $v) => $d . 'd ' . money($v), array_keys($live['prices'] ?? []), $live['prices'] ?? []))) ?>)<?= !empty($live['crm_image']) ? ', photos' : '' ?><?= !empty($live['spec']) ? ', specs' : '' ?> and description when the CRM has them – change those in the CRM. The fields below are the fallback.</div><?php endif; ?>
<fieldset><legend>Prices (£ + VAT)</legend><div class="g5">
<?php foreach (['1' => '1 day', '2' => '2 days', '3' => '3 days', '4' => '4 days', '7' => '7 days'] as $dd => $l): ?><div><label for="p<?= $dd ?>"><?= $l ?></label><input id="p<?= $dd ?>" name="p<?= $dd ?>" inputmode="decimal" value="<?= e((string)($dd === '1' ? ($p['price_from'] ?? '') : ($p['prices'][$dd] ?? ''))) ?>"></div><?php endforeach; ?>
</div><small>Leave a price empty to use the standard multi-day rule. Live CRM prices replace these when a CRM ID is set and the CRM is connected.</small>
<label class="chk"><input type="checkbox" name="staffed" value="1" <?= !empty($p['staffed']) ? 'checked' : '' ?>> Staffed item – full price every day</label></fieldset>
<fieldset><legend>Photos</legend>
<label>Main photo</label><?= img_field('image', (string)($p['image'] ?? ''), 'up_image') ?>
<label for="image_alt">Photo description <small>(alt text – helps Google Images)</small></label><input id="image_alt" name="image_alt" value="<?= e($p['image_alt'] ?? '') ?>" placeholder="<?= e($p['name']) ?> hire">
<label>More photos</label>
<?php if (!empty($p['images'])): ?><div class="extra"><?php foreach ($p['images'] as $x): ?><label><img src="<?= e(img_src($x, 480)) ?>" alt=""><input type="checkbox" name="rm_extra[]" value="<?= e($x) ?>" style="width:auto"> remove</label><?php endforeach; ?></div><?php endif; ?>
<input type="file" name="up_extra[]" accept="image/*" multiple><small>You can pick several at once.</small></fieldset>
<fieldset><legend>Specification</legend><div class="g2">
<div><label for="players">Suitable for</label><input id="players" name="players" value="<?= e($p['players'] ?? '') ?>" placeholder="e.g. Adults &amp; children"></div>
<div><label for="dimensions">Size</label><input id="dimensions" name="dimensions" value="<?= e($sp['dimensions'] ?? '') ?>"></div>
<div><label for="weight">Weight</label><input id="weight" name="weight" value="<?= e($sp['weight'] ?? '') ?>"></div>
<div><label for="power">Power</label><input id="power" name="power" value="<?= e($sp['power'] ?? '') ?>"></div>
<div><label for="setup">Set-up time</label><input id="setup" name="setup" value="<?= e($sp['setup'] ?? '') ?>"></div>
<div><label for="crm_id">CRM product ID</label><input id="crm_id" name="crm_id" value="<?= e($p['crm_id'] ?? '') ?>"></div></div></fieldset>
<fieldset><legend>Google</legend>
<?php $defT = tf('product.title', ['name' => $p['name'] ?: 'Product', 'price' => money((float)($p['price_from'] ?? 0))]); $defM = tf('product.meta', ['short' => $p['short'] ?? '', 'price' => money((float)($p['price_from'] ?? 0))]); ?>
<?= counted('SEO title <small>(leave empty to use the standard one)</small>', 'seo_title', $p['seo_title'] ?? '', 60, false, $defT) ?>
<?= counted('Meta description <small>(leave empty to use the standard one)</small>', 'seo_meta', $p['seo_meta'] ?? '', 155, true, mb_strimwidth($defM, 0, 158, '…')) ?>
<div class="serp" data-serp><div class="u"><?= e(preg_replace('#^https?://#', '', base())) ?>/hire/<?= e($p['slug'] ?: 'new-product') ?></div><div class="t"></div><div class="m"></div></div>
</fieldset>
<label class="chk"><input type="checkbox" name="hidden" value="1" <?= !empty($p['hidden']) ? 'checked' : '' ?>> Hide this product from the website</label>
<div class="save"><button>Save product</button><?php if (!$isNew): ?><a class="btn3 btn-sm" href="/hire/<?= e($p['slug']) ?>" target="_blank">View ↗</a><?php endif; ?></div></form>
<?php if (!$isNew): ?>
<form method="post" class="box" data-confirm="Delete <?= e($p['name']) ?> from the website? This can’t be undone."><?= $csrf ?><input type="hidden" name="act" value="product_delete"><input type="hidden" name="i" value="<?= e($i) ?>">
<h3 style="margin-top:0">Delete product</h3><p><small>To take it off the site for a while, tick “Hide this product” above instead.</small></p><button class="danger btn-sm">Delete product</button></form>
<?php endif; ?>

<?php elseif ($tab === 'products'): ?>
<div class="box"><div class="field-head"><h2>Products (<?= count($d['products']) ?>)</h2><a class="btn btn-sm" href="?tab=products&edit=new">+ Add product</a></div>
<input type="search" placeholder="Search products…" data-filter=".prow" aria-label="Search products" style="margin:8px 0 12px">
<div class="plist"><?php foreach ($d['products'] as $j => $p): ?>
<a class="prow" href="?tab=products&edit=<?= $j ?>" data-text="<?= e(strtolower($p['name'] . ' ' . implode(' ', $p['cats'] ?? []))) ?>">
<?= img_exists((string)($p['image'] ?? '')) ? '<img src="' . e(img_src($p['image'], 480)) . '" alt="" loading="lazy">' : '<span class="ph"></span>' ?>
<span><b><?= e($p['name']) ?></b><small><?= e(implode(', ', array_map(fn($c) => $catOptions[$c] ?? $c, $p['cats'] ?? []))) ?></small></span>
<span style="text-align:right"><?= money($p['price_from'] ?? 0) ?><?php if (!empty($p['hidden'])): ?><br><span class="badge off">Hidden</span><?php endif; ?></span></a>
<?php endforeach; ?></div></div>

<?php /* ------------------------------------------------------------ SITE TEXT & CONTACT */
elseif ($tab === 'text' || $tab === 'contact'):
    $groups = [];
    foreach (text_fields() as $k => $f) $groups[$f[0]][$k] = $f;
    if ($tab === 'contact') { $g = 'Contact page'; } else { $g = (string)($_GET['g'] ?? ''); unset($groups['Contact page']); if (!isset($groups[$g])) $g = array_key_first($groups); }
    if ($tab === 'contact'): ?>
<form method="post" enctype="multipart/form-data" class="box"><?= $csrf ?><input type="hidden" name="act" value="settings"><input type="hidden" name="back" value="contact">
<h2>Contact details <a class="btn3 btn-sm" href="/contact" target="_blank">View contact page ↗</a></h2><p><small>Shown on the contact page and in the footer. Enquiries from the form are emailed to the last address.</small></p>
<?php foreach ($SETTINGS['Contact details'] as $k => $l): ?><label for="s_<?= $k ?>"><?= e($l) ?></label>
<?php if (in_array($k, ['address', 'opening_hours'], true)): ?><textarea id="s_<?= $k ?>" name="s[<?= $k ?>]" rows="2"><?= e((string)($d['settings'][$k] ?? '')) ?></textarea><?php else: ?><input id="s_<?= $k ?>" name="s[<?= $k ?>]" value="<?= e((string)($d['settings'][$k] ?? '')) ?>" <?= in_array($k, ['email', 'notify_email'], true) ? 'type="email"' : ($k === 'phone' ? 'type="tel"' : '') ?>><?php endif; ?>
<?php endforeach; ?><div class="save"><button>Save contact details</button></div></form>
<?php else: ?>
<div class="pills"><?php foreach (array_keys($groups) as $gn): ?><a href="?tab=text&g=<?= urlencode($gn) ?>" class="<?= $gn === $g ? 'on' : '' ?>"><?= e($gn) ?></a><?php endforeach; ?></div>
<?php endif; ?>
<form method="post" enctype="multipart/form-data" class="box"><?= $csrf ?><input type="hidden" name="act" value="text"><input type="hidden" name="g" value="<?= e($g) ?>">
<h2><?= e($tab === 'contact' ? 'Contact page wording' : $g) ?></h2>
<p><small>Words in {curly brackets} are filled in automatically – keep them as they are. “Reset” puts back the original wording.</small></p>
<?php foreach ($groups[$g] as $k => [$grp, $label, $type, $def]): $val = (string)($d['text'][$k] ?? $def); $id = 't_' . str_replace('.', '_', $k); ?>
<div class="field-head"><label for="<?= $id ?>"><?= e($label) ?></label><?php if ($type !== 'image'): ?><button type="button" class="reset" data-reset="<?= $id ?>" data-default="<?= e($def) ?>">Reset</button><?php endif; ?></div>
<?php if ($type === 'line'): ?><input id="<?= $id ?>" name="t[<?= e($k) ?>]" value="<?= e($val) ?>">
<?php elseif ($type === 'text'): ?><textarea id="<?= $id ?>" name="t[<?= e($k) ?>]" rows="3"><?= e($val) ?></textarea>
<?php elseif ($type === 'list'): ?><textarea id="<?= $id ?>" name="t[<?= e($k) ?>]" rows="<?= min(10, max(3, substr_count($val, "\n") + 2)) ?>" class="code"><?= e($val) ?></textarea>
<?php elseif ($type === 'html'): ?><textarea id="<?= $id ?>" class="rich" name="t[<?= e($k) ?>]"><?= e($val) ?></textarea>
<?php elseif ($type === 'image'): ?><?= img_field('t[' . $k . ']', $val, 'tu_' . str_replace('.', '_', $k), true) ?><?php endif; ?>
<?php endforeach; ?>
<div class="save"><button>Save</button><a class="btn3 btn-sm" href="<?= $tab === 'contact' ? '/contact' : '/' ?>" target="_blank">View site ↗</a></div></form>

<?php /* ------------------------------------------------------------ SEO */
elseif ($tab === 'seo'): ?>
<form method="post" enctype="multipart/form-data" class="box"><?= $csrf ?><input type="hidden" name="act" value="settings"><input type="hidden" name="back" value="seo">
<h2>SEO settings</h2>
<?php foreach ($SETTINGS['SEO & tracking'] as $k => $l):
    if (in_array($k, $BOOL_SETTINGS, true)): ?><input type="hidden" name="bools[]" value="<?= $k ?>"><label class="chk"><input type="checkbox" name="s[<?= $k ?>]" value="1" <?= !empty($d['settings'][$k]) ? 'checked' : '' ?>> <?= e($l) ?></label>
<?php elseif (in_array($k, $IMAGE_SETTINGS, true)): ?><label><?= e($l) ?></label><?= img_field('s_img[' . $k . ']', (string)($d['settings'][$k] ?? ''), 'up_' . $k, true) ?>
<?php else: ?><label for="s_<?= $k ?>"><?= e($l) ?></label><input id="s_<?= $k ?>" name="s[<?= $k ?>]" value="<?= e((string)($d['settings'][$k] ?? '')) ?>"><?php endif; endforeach; ?>
<p><small>Sitemap for Google Search Console: <code><?= e(url('/sitemap.xml')) ?></code></small></p>
<div class="save"><button>Save SEO settings</button></div></form>
<form method="post" class="box"><?= $csrf ?><input type="hidden" name="act" value="seo">
<h2>Titles &amp; descriptions</h2><p><small>Every page’s Google title and description in one place. Counters turn red when Google is likely to cut the text off. Product boxes left empty use the standard wording from Site text → Product pages.</small></p>
<h3>Main pages</h3>
<?php foreach ($d['pages'] as $k => $pg): ?><div class="seo-row"><b><?= e($k === 'home' ? 'Home page' : $pg['h1']) ?></b> <small><?= e($k === 'home' ? '/' : '/' . $k) ?></small>
<?= counted('Title', "pg[$k][title]", $pg['title'] ?? '', 60) ?><?= counted('Description', "pg[$k][meta]", $pg['meta'] ?? '', 155, true) ?></div><?php endforeach; ?>
<?php foreach (['contact' => '/contact', 'avail' => '/availability', 'gallery' => '/gallery', 'faq' => '/faq', 'areas' => '/areas'] as $k => $u): ?>
<div class="seo-row"><b><?= e(t("$k.h1")) ?></b> <small><?= e($u) ?></small><?= counted('Title', "t[$k.title]", t("$k.title"), 60) ?><?= counted('Description', "t[$k.meta]", t("$k.meta"), 155, true) ?></div><?php endforeach; ?>
<div class="seo-row"><b>Town pages</b> <small>e.g. /rodeo-bull-hire/leeds – {label}, {city}, {region}, {price} are filled in</small><?= counted('Title', 't[town.title]', t('town.title'), 75) ?><?= counted('Description', 't[town.meta]', t('town.meta'), 170, true) ?></div>
<h3>Products</h3>
<input type="search" placeholder="Search products…" data-filter=".seo-prod" aria-label="Search products" style="margin-bottom:6px">
<?php foreach ($d['products'] as $j => $p): $v = ['name' => $p['name'], 'price' => money((float)($p['price_from'] ?? 0)), 'short' => $p['short'] ?? '']; ?>
<div class="seo-row seo-prod" data-text="<?= e(strtolower($p['name'])) ?>"><b><?= e($p['name']) ?></b> <small>/hire/<?= e($p['slug']) ?></small>
<?= counted('Title', "pr[$j][title]", $p['seo_title'] ?? '', 60, false, tf('product.title', $v)) ?><?= counted('Description', "pr[$j][meta]", $p['seo_meta'] ?? '', 155, true, mb_strimwidth(tf('product.meta', $v), 0, 158, '…')) ?></div>
<?php endforeach; ?>
<div class="save"><button>Save all titles &amp; descriptions</button></div></form>

<?php /* ------------------------------------------------------------ PHOTOS */
elseif ($tab === 'media'): ?>
<form method="post" enctype="multipart/form-data" class="box"><?= $csrf ?><input type="hidden" name="act" value="media_upload">
<h2>Upload photos</h2><p><small>JPG, PNG, WebP or GIF up to 15MB. Photos are resized for fast loading automatically. You can pick several at once.</small></p>
<input type="file" name="files[]" accept="image/*" multiple required><p><button>Upload</button></p></form>
<div class="box"><h2>All photos (<?= count($media) ?>)</h2><input type="search" placeholder="Search photos…" data-filter=".mgrid figure" aria-label="Search photos" style="margin-bottom:12px">
<div class="mgrid"><?php foreach ($media as $m): $up = str_starts_with($m, 'uploads/'); ?>
<figure data-text="<?= e($m) ?>"><img src="<?= e(img_src($m, 480)) ?>" alt="" loading="lazy"><figcaption><?= e(preg_replace('#^uploads/#', '', $m)) ?><?= $up ? ' <span class="badge">uploaded</span>' : '' ?></figcaption>
<?php if ($up): ?><form method="post" data-confirm="Delete this photo?"><?= $csrf ?><input type="hidden" name="act" value="media_delete"><input type="hidden" name="name" value="<?= e($m) ?>"><label class="chk" style="font-size:12px"><input type="checkbox" name="force" value="1"> delete anyway</label><button class="danger btn-sm">Delete</button></form><?php endif; ?></figure>
<?php endforeach; ?></div></div>

<?php /* ------------------------------------------------------------ GALLERY */
elseif ($tab === 'gallery'): ?>
<form method="post" enctype="multipart/form-data" class="box"><?= $csrf ?><input type="hidden" name="act" value="gallery">
<h2>Gallery page <a class="btn3 btn-sm" href="/gallery" target="_blank">View ↗</a></h2>
<fieldset><legend>Add a photo</legend><?= img_field('add_img', '', 'up_add', true) ?><label for="add_alt">Caption</label><input id="add_alt" name="add_alt" placeholder="e.g. 100ft assault course at a corporate fun day in Leeds"></fieldset>
<div class="mgrid"><?php foreach ($d['gallery'] as $i => $g): ?>
<figure><img src="<?= e(img_src($g['img'], 480)) ?>" alt=""><figcaption><input type="hidden" name="g[<?= $i ?>][img]" value="<?= e($g['img']) ?>"><label for="ga<?= $i ?>" style="margin:0">Caption</label><input id="ga<?= $i ?>" name="g[<?= $i ?>][alt]" value="<?= e($g['alt']) ?>"><label class="chk"><input type="checkbox" name="g[<?= $i ?>][remove]" value="1"> Remove</label></figcaption></figure>
<?php endforeach; ?></div>
<div class="save"><button>Save gallery</button></div></form>

<?php /* ------------------------------------------------------------ TOWNS */
elseif ($tab === 'locations'): ?>
<form method="post" class="box"><?= $csrf ?><input type="hidden" name="act" value="locations"><h2>Town pages</h2>
<p><small>Each town gets a page for every category with “Create a page for every town” ticked (<?= e(implode(', ', array_map('page_label', local_keys()))) ?>). Real local venues and a custom intro make each page stronger in Google. The wording used on these pages is under Site text → Town pages.</small></p>
<input type="search" placeholder="Search towns…" data-filter=".town" aria-label="Search towns" style="margin-bottom:10px">
<?php foreach (array_merge($d['locations'], [['city' => '', 'region' => '', 'nearby' => '', 'venues' => '', 'intro' => '']]) as $i => $l): ?>
<fieldset class="town" data-text="<?= e(strtolower($l['city'])) ?>"><legend><?= e($l['city'] ?: '+ Add a town') ?></legend>
<div class="g2"><div><label for="lc<?= $i ?>">Town / city</label><input id="lc<?= $i ?>" name="l[<?= $i ?>][city]" value="<?= e($l['city']) ?>"></div><div><label for="lr<?= $i ?>">Region</label><input id="lr<?= $i ?>" name="l[<?= $i ?>][region]" value="<?= e($l['region']) ?>"></div></div>
<div class="g2"><div><label for="ln<?= $i ?>">Nearby areas</label><input id="ln<?= $i ?>" name="l[<?= $i ?>][nearby]" value="<?= e($l['nearby']) ?>"></div><div><label for="lv<?= $i ?>">Venues</label><input id="lv<?= $i ?>" name="l[<?= $i ?>][venues]" value="<?= e($l['venues']) ?>"></div></div>
<label for="li<?= $i ?>">Custom intro <small>(optional)</small></label><textarea id="li<?= $i ?>" name="l[<?= $i ?>][intro]" rows="2"><?= e($l['intro']) ?></textarea>
<label for="lb<?= $i ?>">Local guide <small>(HTML, shown on every service page for this town; {label} = service name)</small></label><textarea id="lb<?= $i ?>" class="code" name="l[<?= $i ?>][body]" rows="4"><?= e($l['body'] ?? '') ?></textarea>
<label for="lq<?= $i ?>">Local FAQs <small>(one per line: Question|Answer)</small></label><textarea id="lq<?= $i ?>" name="l[<?= $i ?>][faq]" rows="3"><?= e(implode("\n", array_map(fn($f) => $f['q'] . '|' . $f['a'], $l['faq'] ?? []))) ?></textarea>
<?php if ($l['city']): ?><label class="chk"><input type="checkbox" name="l[<?= $i ?>][remove]" value="1"> Remove</label><?php endif; ?></fieldset>
<?php endforeach; ?><div class="save"><button>Save towns</button></div></form>

<?php /* ------------------------------------------------------------ SETTINGS */
elseif ($tab === 'settings'): ?>
<form method="post" enctype="multipart/form-data" class="box"><?= $csrf ?><input type="hidden" name="act" value="settings"><input type="hidden" name="back" value="settings"><h2>Settings</h2>
<?php foreach (['Business & branding', 'Contact details'] as $grp): ?><h3><?= e($grp) ?></h3>
<?php foreach ($SETTINGS[$grp] as $k => $l): ?><label for="s_<?= $k ?>"><?= e($l) ?></label>
<?php if ($k === 'accent'): ?><div style="display:flex;gap:8px"><input type="color" value="<?= e(preg_match('/^#[0-9a-f]{6}$/i', (string)s('accent')) ? s('accent') : '#D7141A') ?>" style="width:56px;padding:2px" oninput="this.nextElementSibling.value=this.value" aria-label="Pick colour"><input id="s_<?= $k ?>" name="s[<?= $k ?>]" value="<?= e((string)($d['settings'][$k] ?? '')) ?>"></div>
<?php else: ?><input id="s_<?= $k ?>" name="s[<?= $k ?>]" value="<?= e((string)($d['settings'][$k] ?? '')) ?>"><?php endif; ?>
<?php endforeach; endforeach; ?>
<p><small>Google settings are under <a href="?tab=seo">SEO</a>; the logo and menu are under <a href="?tab=text&g=Header+%26+footer">Site text → Header &amp; footer</a>.</small></p>
<div class="save"><button>Save settings</button></div></form>

<?php elseif ($tab === 'crm'): $st = crm_status(); $ok = $st['cached_at'] && !$st['failing']; ?>
<div class="box"><h2>CRM connection</h2>
<p>The CRM is the master copy. Every <?= (int)s('crm_cache_seconds', 30) ?> seconds the site reads the CRM through the live feed and updates <b>prices</b> (1, 2, 3, 4 and 7 days), <b>stock</b>, <b>specs</b> (size, weight, power, users, set-up time), <b>descriptions</b> and <b>photos</b>. A product hidden in the CRM disappears from the site, and new online stock in the CRM categories below is added automatically. Availability checks read CRM bookings live.</p>
<p>Status: <b style="color:<?= $ok ? 'var(--ok)' : 'var(--warn)' ?>"><?= !$st['enabled'] ? 'Off' : ($ok ? 'Connected' : ($st['failing'] ? 'Feed not reachable – showing the last copy' : 'Not synced yet')) ?></b><br>
Last sync: <b><?= $st['cached_at'] ? date('d M Y H:i:s', (int)$st['cached_at']) : 'never' ?></b> · CRM products: <b><?= $st['count'] ?></b> · CRM photos: <b><?= $st['photos'] ?></b><br>
<small>Backup copy (used if the feed is down): <?= (int)$st['snapshot_count'] ?> products from <?= e((string)$st['snapshot_at']) ?></small></p>
<form method="post"><?= $csrf ?><input type="hidden" name="act" value="crm_sync"><button class="btn2">Sync now</button></form></div>
<form method="post" class="box"><?= $csrf ?><input type="hidden" name="act" value="settings"><input type="hidden" name="back" value="crm"><h2>CRM settings</h2>
<?php foreach (['crm_enabled' => 'Use live data from the CRM', 'prefer_crm_images' => 'Use CRM photos (replace the site’s own photos when the CRM has some)', 'crm_use_descriptions' => 'Use CRM descriptions (when the CRM has one)', 'crm_auto_add' => 'Add new online CRM stock from the categories below automatically'] as $k => $l): ?>
<input type="hidden" name="bools[]" value="<?= $k ?>"><label class="chk"><input type="checkbox" name="s[<?= $k ?>]" value="1" <?= s($k, true) ? 'checked' : '' ?>> <?= e($l) ?></label>
<?php endforeach; ?>
<label for="s_crm_categories">CRM categories this site sells <small>(separate with |)</small></label><input id="s_crm_categories" name="s[crm_categories]" value="<?= e(crm_setting('crm_categories')) ?>">
<label for="s_crm_exclude">Never add these CRM product IDs <small>(comma-separated, e.g. bunting, trampolines)</small></label><input id="s_crm_exclude" name="s[crm_exclude]" value="<?= e(crm_setting('crm_exclude')) ?>">
<label for="s_crm_cache_seconds">Check the CRM every (seconds)</label><input id="s_crm_cache_seconds" name="s[crm_cache_seconds]" inputmode="numeric" value="<?= e((string)s('crm_cache_seconds', 30)) ?>">
<details><summary><small>Advanced</small></summary>
<label for="s_crm_base_url">Feed address</label><input id="s_crm_base_url" name="s[crm_base_url]" value="<?= e(crm_setting('crm_base_url')) ?>">
<label for="s_crm_products_path">Products path</label><input id="s_crm_products_path" name="s[crm_products_path]" value="<?= e(crm_setting('crm_products_path')) ?>">
<label for="s_crm_availability_path">Availability path</label><input id="s_crm_availability_path" name="s[crm_availability_path]" value="<?= e(crm_setting('crm_availability_path')) ?>"></details>
<div class="save"><button>Save CRM settings</button></div></form>

<?php elseif ($tab === 'enquiries'): $lines = is_file(ROOT . '/data/enquiries.log') ? array_reverse(file(ROOT . '/data/enquiries.log', FILE_IGNORE_NEW_LINES)) : []; ?>
<div class="box"><h2>Recent enquiries</h2><p><small>Every enquiry is also emailed to <?= e(s('notify_email')) ?> and sent to the CRM.</small></p>
<?php if (!$lines) echo '<p>No enquiries yet.</p>'; foreach (array_slice($lines, 0, 100) as $ln): $q = json_decode($ln, true); if (!$q) continue; ?>
<fieldset><legend><?= e($q['name']) ?> · <?= e(date('d M Y H:i', strtotime($q['at']))) ?> <?= !empty($q['ref']) ? '· ' . e($q['ref']) : '' ?></legend>
<?= e($q['email']) ?> <?= e($q['phone'] ?? '') ?> · <?= e($q['product_name'] ?? '') ?> × <?= (int)($q['qty'] ?? 1) ?> · <?= e($q['date'] ?? '') ?> · <?= e($q['venue'] ?? '') ?><br><small><?= nl2br(e($q['message'] ?? '')) ?></small></fieldset>
<?php endforeach; ?></div>

<?php elseif ($tab === 'account'): ?>
<form method="post" class="box"><?= $csrf ?><input type="hidden" name="act" value="password"><h2>Change password</h2>
<label for="old">Current password</label><input id="old" type="password" name="old" required autocomplete="current-password"><label for="new">New password (10+ characters)</label><input id="new" type="password" name="new" required minlength="10" autocomplete="new-password">
<p><button>Change password</button></p><p><small>Lost your authenticator? Clear <code>totp_secret</code> in data/content.json via the IONOS file manager – the setup screen appears on your next visit.</small></p></form>
<?php endif; ?>
</main>

<div class="lib" id="lib" role="dialog" aria-modal="true" aria-label="Choose a photo"><div class="lib-in">
<div class="lib-top"><input type="search" id="libq" placeholder="Search photos…" aria-label="Search photos"><button type="button" class="btn3" id="libx">Close</button></div>
<div class="lib-grid" id="libg"></div></div></div>

<script>
(() => {
  const MEDIA = <?= json_encode(array_map(fn($m) => [$m, img_src($m, 480)], $media), JSON_UNESCAPED_SLASHES) ?>;
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];

  // confirm before destructive forms (built into the page – no browser dialogs)
  $$('form[data-confirm]').forEach(f => f.addEventListener('submit', e => {
    if (f.dataset.ok) return; e.preventDefault();
    const b = f.querySelector('button'); if (b.dataset.armed) { f.dataset.ok = 1; f.submit(); return; }
    b.dataset.armed = 1; b.textContent = 'Tap again to confirm'; setTimeout(() => { delete b.dataset.armed; b.textContent = 'Delete'; }, 4000);
  }));

  // search filters
  $$('[data-filter]').forEach(inp => inp.addEventListener('input', () => {
    const q = inp.value.trim().toLowerCase();
    $$(inp.dataset.filter).forEach(el => el.style.display = !q || (el.dataset.text || el.textContent).toLowerCase().includes(q) ? '' : 'none');
  }));

  // reset to default wording
  $$('[data-reset]').forEach(b => b.addEventListener('click', () => {
    const el = document.getElementById(b.dataset.reset); el.value = b.dataset.default;
    if (el._rte) el._rte.innerHTML = b.dataset.default; el.dispatchEvent(new Event('input'));
  }));

  // character counters + Google preview
  const count = el => {
    const out = document.querySelector('.count[data-for="' + el.id + '"]'); if (!out) return;
    const n = (el.value || el.placeholder).length, max = +el.dataset.count;
    out.textContent = (el.value ? '' : 'standard · ') + n + ' / ' + max; out.className = 'count ' + (n > max ? 'bad' : n >= max * 0.7 ? 'good' : '');
  };
  $$('[data-count]').forEach(el => { count(el); el.addEventListener('input', () => count(el)); });
  $$('[data-serp]').forEach(box => {
    const fs = box.closest('fieldset'), ti = fs.querySelector('[data-count="60"]'), me = fs.querySelector('textarea[data-count]');
    const draw = () => { const t = ti.value || ti.placeholder, m = me.value || me.placeholder;
      box.querySelector('.t').textContent = t.length > 60 ? t.slice(0, 58) + '…' : t; box.querySelector('.m').textContent = m.length > 158 ? m.slice(0, 155) + '…' : m; };
    ti.addEventListener('input', draw); me.addEventListener('input', draw); draw();
  });

  // photo picker
  const lib = document.getElementById('lib'), grid = document.getElementById('libg'), q = document.getElementById('libq'); let target = null;
  const render = () => { const s = q.value.toLowerCase(); grid.innerHTML = '';
    MEDIA.filter(m => !s || m[0].toLowerCase().includes(s)).slice(0, 400).forEach(([name, src]) => {
      const b = document.createElement('button'); b.type = 'button'; b.innerHTML = '<img loading="lazy" alt=""><span></span>';
      b.querySelector('img').src = src; b.querySelector('span').textContent = name.replace(/^uploads\//, '');
      b.onclick = () => { const h = target.querySelector('input[type=hidden]'); h.value = name; setThumb(target, src); target.querySelector('[data-up]').value = ''; close(); };
      grid.appendChild(b); }); };
  const setThumb = (box, src) => { const old = box.querySelector('.thumb'); const img = document.createElement('img'); img.className = 'thumb'; img.alt = ''; img.src = src; old.replaceWith(img); };
  const close = () => { lib.classList.remove('open'); target && target.querySelector('[data-pick]').focus(); };
  $$('[data-imgf]').forEach(box => {
    box.querySelector('[data-pick]').onclick = () => { target = box; q.value = ''; render(); lib.classList.add('open'); q.focus(); };
    const clr = box.querySelector('[data-clear]'); if (clr) clr.onclick = () => { box.querySelector('input[type=hidden]').value = ''; const t = box.querySelector('.thumb'); const d = document.createElement('div'); d.className = 'thumb empty'; d.textContent = 'No image'; t.replaceWith(d); };
    box.querySelector('[data-up]').onchange = e => { const f = e.target.files[0]; if (f) setThumb(box, URL.createObjectURL(f)); };
  });
  q.oninput = render; document.getElementById('libx').onclick = close;
  lib.onclick = e => { if (e.target === lib) close(); }; document.addEventListener('keydown', e => { if (e.key === 'Escape' && lib.classList.contains('open')) close(); });

  // rich text editor
  $$('textarea.rich').forEach(ta => {
    const wrap = document.createElement('div'); wrap.className = 'rte';
    const bar = document.createElement('div'); bar.className = 'rte-bar';
    const area = document.createElement('div'); area.className = 'rte-area'; area.contentEditable = 'true'; area.innerHTML = ta.value;
    area.setAttribute('role', 'textbox'); area.setAttribute('aria-multiline', 'true');
    const tools = [['B', 'bold', 'Bold'], ['I', 'italic', 'Italic'], ['H2', 'formatBlock:h2', 'Heading'], ['H3', 'formatBlock:h3', 'Small heading'], ['¶', 'formatBlock:p', 'Paragraph'], ['• List', 'insertUnorderedList', 'Bullet list'], ['1. List', 'insertOrderedList', 'Numbered list'], ['Link', 'link', 'Add link'], ['Unlink', 'unlink', 'Remove link'], ['Clear', 'removeFormat', 'Clear formatting'], ['HTML', 'html', 'Edit HTML']];
    tools.forEach(([label, cmd, title]) => { const b = document.createElement('button'); b.type = 'button'; b.textContent = label; b.title = title;
      b.onmousedown = e => e.preventDefault();
      b.onclick = () => {
        if (cmd === 'html') { const on = ta.style.display !== 'none'; if (on) { area.innerHTML = ta.value; ta.style.display = 'none'; area.style.display = ''; } else { ta.value = area.innerHTML; ta.style.display = ''; area.style.display = 'none'; } return; }
        if (cmd === 'link') { const sel = window.getSelection(); if (!sel.rangeCount || sel.isCollapsed) { flash(b, 'Select text first'); return; }
          const box = document.createElement('input'); box.placeholder = 'https://… or /page'; box.style.cssText = 'margin:4px;flex:1 1 220px';
          const range = sel.getRangeAt(0); bar.appendChild(box); box.focus();
          box.onkeydown = ev => { if (ev.key === 'Enter') { ev.preventDefault(); const url = box.value.trim(); box.remove(); area.focus(); sel.removeAllRanges(); sel.addRange(range); if (url) document.execCommand('createLink', false, url); sync(); } if (ev.key === 'Escape') box.remove(); };
          return; }
        const [c, v] = cmd.split(':'); area.focus(); document.execCommand(c, false, v ? '<' + v + '>' : null); sync(); };
      bar.appendChild(b); });
    const flash = (b, t) => { const o = b.textContent; b.textContent = t; setTimeout(() => b.textContent = o, 1500); };
    const sync = () => { if (ta.style.display === 'none') ta.value = area.innerHTML; };
    area.addEventListener('input', sync);
    area.addEventListener('paste', e => { e.preventDefault(); const txt = (e.clipboardData || window.clipboardData).getData('text/plain'); document.execCommand('insertText', false, txt); });
    ta.parentNode.insertBefore(wrap, ta); wrap.append(bar, area, ta); ta.style.display = 'none'; ta._rte = area;
    ta.form.addEventListener('submit', sync);
  });
})();
</script>
<?php admin_shell_end();
