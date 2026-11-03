<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('DATA_FILE', ROOT . '/data/content.json');
define('CACHE_DIR', ROOT . '/data/cache');

function data(): array {
    static $d = null;
    // First run on a new server: start from the default content shipped in the repo.
    // content.json is never overwritten by deploys, so admin edits stay safe.
    if (!is_file(DATA_FILE) && is_file(ROOT . '/data/content.default.json')) {
        copy(ROOT . '/data/content.default.json', DATA_FILE);
    }
    if ($d === null) {
        $d = json_decode((string)file_get_contents(DATA_FILE), true) ?: [];
        $d = content_upgrade($d);
    }
    return $d;
}

/** Fingerprint of a content value, to tell whether the admin has edited it since it shipped. */
function content_fp($v): string {
    $flat = [];
    if (is_array($v)) array_walk_recursive($v, function ($x) use (&$flat) { $flat[] = (string)$x; });
    else $flat = [(string)$v];
    return md5(preg_replace('/\s+/', '', strip_tags(implode('|', $flat))));
}

/** One-off upgrades for sites whose content.json was created before a new feature shipped (admin edits are kept). */
function content_upgrade(array $d): array {
    $rev = (int)($d['settings']['content_rev'] ?? 1);
    if ($rev >= 3 || !is_file(ROOT . '/data/content.default.json')) return $d;
    $def = json_decode((string)file_get_contents(ROOT . '/data/content.default.json'), true) ?: [];
    if ($rev < 2) {   // rev 2: Wipeout, Bungee Run and Gladiator Duel pages; town pages for slides and challenges
        foreach ($def['pages'] ?? [] as $k => $pg) if (!isset($d['pages'][$k])) $d['pages'][$k] = $pg;
        foreach (['inflatable-slide-hire', 'inflatable-challenge-hire'] as $k) if (isset($d['pages'][$k])) $d['pages'][$k]['local'] = true;
        foreach ($d['pages'] as $k => &$pg) {
            foreach (['town_intro', 'town_body', 'town_faq', 'keywords'] as $f) if (!isset($pg[$f]) && isset($def['pages'][$k][$f])) $pg[$f] = $def['pages'][$k][$f];
            if (($pg['label'] ?? '') === 'Wipeout & Challenge Inflatable') $pg['label'] = 'Inflatable Challenge';
        }
        unset($pg);
        foreach (['home', 'inflatable-hire'] as $k) foreach (['title', 'meta'] as $f)
            if (isset($def['pages'][$k][$f], $d['pages'][$k])) $d['pages'][$k][$f] = $def['pages'][$k][$f];
    }
    if ($rev < 3) {   // rev 3: event pages, city guides, FAQ topics
        foreach ($def['pages'] ?? [] as $k => $pg) if (!isset($d['pages'][$k])) $d['pages'][$k] = $pg;
        foreach ($d['pages'] as $k => &$pg) foreach (['sections', 'faq', 'title', 'meta', 'intro'] as $f)
            if (isset($def['pages'][$k][$f], $def['prev_fp'][$k][$f]) && content_fp($pg[$f] ?? '') === $def['prev_fp'][$k][$f]) $pg[$f] = $def['pages'][$k][$f];   // only if never edited in the admin
        unset($pg);
        $byslug = []; foreach ($def['locations'] ?? [] as $l) $byslug[$l['slug']] = $l;
        foreach ($d['locations'] as &$l) foreach (['body', 'faq', 'guide_title'] as $f) if (!isset($l[$f]) && isset($byslug[$l['slug']][$f])) $l[$f] = $byslug[$l['slug']][$f];
        unset($l);
        $have = array_column($d['locations'], 'slug');
        foreach ($def['locations'] ?? [] as $l) if (!in_array($l['slug'], $have, true)) $d['locations'][] = $l;
        if (!isset($d['faq_topics']) && isset($def['faq_topics'])) $d['faq_topics'] = $def['faq_topics'];
    }
    $d['settings']['content_rev'] = 3;
    if (is_writable(DATA_FILE)) save_data($d);
    return $d;
}

function save_data(array $d): bool {
    $tmp = DATA_FILE . '.tmp';
    $ok = file_put_contents($tmp, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    if ($ok === false) return false;
    @copy(DATA_FILE, ROOT . '/data/content.backup.json');
    return rename($tmp, DATA_FILE);
}

function s(string $k, $default = '') { return data()['settings'][$k] ?? $default; }
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function base(): string {
    $b = rtrim((string)s('base_url'), '/');
    if ($b === '' && !empty($_SERVER['HTTP_HOST']) && preg_match('/^[a-z0-9.-]+(:\d+)?$/i', $_SERVER['HTTP_HOST'])) {   // until Base URL is set in admin
        $b = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
    }
    return $b;
}
function url(string $path = '/'): string { return base() . $path; }

function money($n): string { return '£' . number_format((float)$n, 0); }

/**
 * Images. A name is either a built-in set ("rodeo-bull-hire" → .webp, -800.webp, -480.webp in assets/img),
 * an uploaded set ("uploads/my-photo-a1b2c3", same three sizes) or a single uploaded file with an extension
 * ("uploads/old-photo.jpg").
 */
function img_has_ext(string $name): bool { return (bool)preg_match('/\.(webp|jpe?g|png|gif|svg)$/i', $name); }
function img_src(string $name, int $w = 1600): string {
    if ($name === '') return '';
    if (img_has_ext($name)) return '/assets/img/' . $name;
    $suf = $w >= 1600 ? '' : "-$w";
    if ($suf !== '' && str_starts_with($name, 'uploads/') && !is_file(ROOT . "/assets/img/{$name}{$suf}.webp")) $suf = '';
    return "/assets/img/{$name}{$suf}.webp";
}
function img_exists(string $name): bool {
    return $name !== '' && is_file(ROOT . (img_has_ext($name) ? '/assets/img/' . $name : "/assets/img/{$name}.webp"));
}
function picture(string $name, string $alt, string $class = '', bool $eager = false, string $sizes = '(max-width: 760px) 100vw, 50vw'): string {
    $load = $eager ? 'eager" fetchpriority="high' : 'lazy';
    if (img_has_ext($name)) {
        return '<img class="' . e($class) . '" src="' . e(img_src($name)) . '" alt="' . e($alt) . '" loading="' . $load . '" decoding="async">';
    }
    $srcset = img_src($name, 480) . ' 480w, ' . img_src($name, 800) . ' 800w, ' . img_src($name, 1600) . ' 1600w';
    return '<img class="' . e($class) . '" src="' . img_src($name, 800) . '" srcset="' . $srcset . '" sizes="' . e($sizes) . '" alt="' . e($alt) . '" width="1600" height="1200" loading="' . $load . '" decoding="async">';
}

/** Every image name available to pick in admin: built-in sets first, then uploads (newest first). */
function media_library(): array {
    $built = array_values(array_unique(array_map(fn($f) => preg_replace('/(-480|-800)?\.webp$/', '', basename($f)), glob(ROOT . '/assets/img/*.webp') ?: [])));
    sort($built);
    $up = [];
    foreach (glob(ROOT . '/assets/img/uploads/*.{webp,jpg,jpeg,png,gif}', GLOB_BRACE) ?: [] as $f) {
        $b = basename($f);
        if (preg_match('/-(480|800)\.webp$/', $b)) continue;
        $up[filemtime($f) . '|' . $b] = preg_match('/\.webp$/', $b) && is_file(dirname($f) . '/' . substr($b, 0, -5) . '-800.webp') ? 'uploads/' . substr($b, 0, -5) : 'uploads/' . $b;
    }
    krsort($up);
    return array_merge(array_values($up), $built);
}

/**
 * Save an uploaded photo into assets/img/uploads as a 1600/800/480 WebP set (re-encoding strips anything that
 * isn't pixels). Returns the image name, or null with $err set.
 */
function save_upload(array $file, ?string &$err = null): ?string {
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) { $err = 'No file received.'; return null; }
    $info = @getimagesize($file['tmp_name']);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF], true)) { $err = 'Upload a JPG, PNG, WebP or GIF photo.'; return null; }
    if ($file['size'] > 15e6) { $err = 'That photo is over 15MB – please resize it first.'; return null; }
    $base = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(pathinfo((string)$file['name'], PATHINFO_FILENAME))), '-') ?: 'photo';
    $name = substr($base, 0, 60) . '-' . bin2hex(random_bytes(3));
    $dir = ROOT . '/assets/img/uploads';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    if (function_exists('imagecreatefromstring') && function_exists('imagewebp')) {
        $im = @imagecreatefromstring((string)file_get_contents($file['tmp_name']));
        if (!$im) { $err = 'That file could not be read as a photo.'; return null; }
        if (function_exists('exif_read_data') && $info[2] === IMAGETYPE_JPEG) {      // respect phone camera rotation
            $o = (int)(@exif_read_data($file['tmp_name'])['Orientation'] ?? 1);
            if ($o === 3) $im = imagerotate($im, 180, 0); elseif ($o === 6) $im = imagerotate($im, -90, 0); elseif ($o === 8) $im = imagerotate($im, 90, 0);
        }
        imagepalettetotruecolor($im); imagealphablending($im, true); imagesavealpha($im, true);
        foreach ([1600 => '', 800 => '-800', 480 => '-480'] as $w => $suf) {
            $x = imagesx($im) > $w ? imagescale($im, $w) : $im;
            if (!imagewebp($x, "$dir/{$name}{$suf}.webp", 80)) { $err = 'Could not save the photo – check folder permissions on assets/img/uploads.'; return null; }
        }
        return 'uploads/' . $name;
    }
    $ext = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'][$info[2]];
    if (!move_uploaded_file($file['tmp_name'], "$dir/$name.$ext")) { $err = 'Could not save the photo – check folder permissions on assets/img/uploads.'; return null; }
    return "uploads/$name.$ext";
}

/** Delete an uploaded image (all sizes). Built-in images can't be deleted. */
function delete_upload(string $name): bool {
    if (!str_starts_with($name, 'uploads/') || str_contains($name, '..')) return false;
    $p = ROOT . '/assets/img/' . $name;
    $ok = false;
    foreach (img_has_ext($name) ? [$p] : ["$p.webp", "$p-800.webp", "$p-480.webp"] as $f) if (is_file($f)) $ok = @unlink($f) || $ok;
    return $ok;
}

/** Hire price rule: 1 day full; 2 days = 1.5x; 3 days = 1.5x (charged as 2); +0.5x per extra day; staffed = full price per day. */
function hire_multiplier(int $days, bool $staffed = false): float {
    if ($days < 1) $days = 1;
    if ($staffed) return (float)$days;
    if ($days === 1) return 1.0;
    if ($days <= 3) return 1.5;
    return 1.5 + 0.5 * ($days - 3);
}

function product_url(array $p): string { return '/hire/' . $p['slug']; }

function products_in(string $cat): array {
    if ($cat === 'all') return crm_products();
    return array_values(array_filter(crm_products(), fn($p) => in_array($cat, $p['cats'] ?? [], true)));
}

/** Category landing pages (every page with a "cat"), and the ones that also get town pages. */
function category_keys(): array {
    return array_keys(array_filter(data()['pages'] ?? [], fn($pg) => isset($pg['cat']) && empty($pg['event'])));
}
/** Event-type pages (corporate fun days, schools, weddings ...). */
function event_keys(): array {
    return array_keys(array_filter(data()['pages'] ?? [], fn($pg) => isset($pg['cat']) && !empty($pg['event'])));
}
function local_keys(): array {
    return array_keys(array_filter(data()['pages'] ?? [], fn($pg) => isset($pg['cat']) && empty($pg['event']) && !empty($pg['local'])));
}
/** The landing page for a product category (falls back to the all-inflatables page). */
function page_key_for_cat(string $cat): string {
    foreach (data()['pages'] ?? [] as $k => $pg) if (($pg['cat'] ?? null) === $cat) return $k;
    return in_array('inflatable-hire', category_keys(), true) ? 'inflatable-hire' : (category_keys()[0] ?? 'home');
}
function page_label(string $key): string {
    $pg = data()['pages'][$key] ?? [];
    return $pg['label'] ?? preg_replace('/ hire$/i', '', (string)($pg['h1'] ?? ''));
}
function find_product(string $slug): ?array {
    foreach (crm_products() as $p) if ($p['slug'] === $slug) return $p;
    return null;
}
/** Products for a landing page: its category, optionally narrowed by a "match" pattern (e.g. Wipeout, Bungee Run). */
function page_products(array $pg): array {
    $items = products_in($pg['cat'] ?? 'all');
    if (!empty($pg['match'])) {
        $re = '/' . str_replace('/', '\/', $pg['match']) . '/i';
        $all = $pg['cat'] === 'all' ? $items : crm_products();
        $items = array_values(array_filter($all, fn($p) => @preg_match($re, $p['slug'] . ' ' . $p['name'])));
    }
    return $items;
}

/** Every town/city with a page: the towns edited in the admin plus the built-in UK list (data/places.json). */
function all_locations(): array {
    static $all = null;
    if ($all !== null) return $all;
    $places = is_file(ROOT . '/data/places.json') ? (json_decode((string)file_get_contents(ROOT . '/data/places.json'), true) ?: []) : [];
    $geo = []; foreach ($places as $pl) $geo[$pl['slug']] = $pl;
    $all = [];
    foreach (data()['locations'] ?? [] as $l) { if (empty($l['slug'])) continue; $all[$l['slug']] = $l + ['lat' => $geo[$l['slug']]['lat'] ?? null, 'lng' => $geo[$l['slug']]['lng'] ?? null, 'area' => $geo[$l['slug']]['area'] ?? false]; }
    foreach ($places as $pl) if (!isset($all[$pl['slug']])) $all[$pl['slug']] = $pl + ['nearby' => '', 'venues' => '', 'intro' => ''];
    foreach ($all as $slug => &$l) {
        $near = nearest_locations($l, $all, 6);
        $l['near'] = $near;
        if (trim((string)($l['nearby'] ?? '')) === '') $l['nearby'] = implode(', ', array_map(fn($x) => $x['city'], array_slice($near, 0, 4)));
        if (trim((string)($l['venues'] ?? '')) === '') $l['venues'] = 'local schools, sports clubs, parks, hotels and community venues';
    }
    unset($l);
    uasort($all, fn($a, $b) => strcmp($a['city'], $b['city']));
    return $all;
}
function nearest_locations(array $l, array $all, int $n): array {
    if (!isset($l['lat'], $l['lng'])) return [];
    $d = [];
    foreach ($all as $o) {
        if ($o['slug'] === $l['slug'] || !isset($o['lat'], $o['lng']) || $o['region'] === $o['city'] || !empty($o['area'])) continue;
        $d[$o['slug']] = (($o['lat'] - $l['lat']) ** 2) + ((($o['lng'] - $l['lng']) * cos(deg2rad($l['lat']))) ** 2);
    }
    asort($d);
    return array_map(fn($s) => ['slug' => $s, 'city' => $all[$s]['city'], 'miles' => (int)round(sqrt($d[$s]) * 69)], array_slice(array_keys($d), 0, $n));
}
/** Towns grouped by region, for the areas lists. */
function locations_by_region(): array {
    $g = [];
    foreach (all_locations() as $l) $g[$l['region']][] = $l;
    ksort($g);
    return $g;
}
function slugify(string $s): string { return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($s)), '-'); }
/** Town pages for a page key: every town for service pages, the main towns list for event pages. */
function page_has_town(string $key, string $slug): bool {
    $pg = data()['pages'][$key] ?? null;
    if (!$pg || !isset($pg['cat']) || find_location($slug) === null) return false;
    if (empty($pg['event'])) return !empty($pg['local']);
    return in_array($slug, array_column(data()['locations'] ?? [], 'slug'), true);
}
function find_location(string $slug): ?array {
    return all_locations()[$slug] ?? null;
}

function csrf_token(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) start_session();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_ok(): bool {
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
}
function start_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
    session_name('jmih');
    session_start();
}

/** Simple file-based rate limiter. */
function rate_limited(string $key, int $max, int $window): bool {
    $f = CACHE_DIR . '/rl_' . md5($key . ($_SERVER['REMOTE_ADDR'] ?? '')) . '.json';
    $now = time();
    $hits = is_file($f) ? (json_decode((string)file_get_contents($f), true) ?: []) : [];
    $hits = array_values(array_filter($hits, fn($t) => $t > $now - $window));
    if (count($hits) >= $max) return true;
    $hits[] = $now;
    file_put_contents($f, json_encode($hits));
    return false;
}

function json_out($payload, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload);
    exit;
}

require_once __DIR__ . '/crm.php';
require_once __DIR__ . '/text.php';
