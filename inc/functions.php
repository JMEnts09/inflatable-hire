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
    }
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

/** Images: responsive <picture> from the pre-sized WebP set (or an uploaded file). */
function img_src(string $name, int $w = 1600): string {
    if (str_starts_with($name, 'uploads/')) return '/assets/img/' . $name;
    $suf = $w >= 1600 ? '' : "-$w";
    return "/assets/img/{$name}{$suf}.webp";
}
function picture(string $name, string $alt, string $class = '', bool $eager = false, string $sizes = '(max-width: 760px) 100vw, 50vw'): string {
    $load = $eager ? 'eager" fetchpriority="high' : 'lazy';
    if (str_starts_with($name, 'uploads/')) {
        return '<img class="' . e($class) . '" src="/assets/img/' . e($name) . '" alt="' . e($alt) . '" loading="' . $load . '" decoding="async">';
    }
    $srcset = img_src($name, 480) . ' 480w, ' . img_src($name, 800) . ' 800w, ' . img_src($name, 1600) . ' 1600w';
    return '<img class="' . e($class) . '" src="' . img_src($name, 800) . '" srcset="' . $srcset . '" sizes="' . e($sizes) . '" alt="' . e($alt) . '" width="1600" height="1200" loading="' . $load . '" decoding="async">';
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
    return array_keys(array_filter(data()['pages'] ?? [], fn($pg) => isset($pg['cat'])));
}
function local_keys(): array {
    return array_keys(array_filter(data()['pages'] ?? [], fn($pg) => isset($pg['cat']) && !empty($pg['local'])));
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
function find_location(string $slug): ?array {
    foreach (data()['locations'] ?? [] as $l) if ($l['slug'] === $slug) return $l;
    return null;
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
