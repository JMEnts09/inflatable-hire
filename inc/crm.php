<?php
/**
 * JM CRM connection, via the read-only feed Worker (jm-crm-feed, source in crm-feed/worker.js).
 *
 * The CRM is the master for prices, stock, specs, descriptions and photos. Every 30 seconds (Admin → CRM & stock)
 * the site asks the feed for every product it sells plus everything in its CRM categories, so:
 *   - price, multi-day price, stock, size, weight, power, users and set-up time changes show within 30 seconds
 *   - CRM descriptions replace the site's description (can be switched off in admin)
 *   - CRM photos replace the site's photos (can be switched off in admin)
 *   - a product hidden in the CRM disappears from the site; new online stock in the CRM categories is added
 * If the feed can't be reached the site keeps the last good copy, then the imported copy in data/crm-prices.json.
 *
 *   GET {base}/products?ids=1,2&category=A|B  → {"products":[{id,name,online,categories,price,prices{1,2,3,4,7},qty,size,weight,power,users,setup_time,description,photos[]}]}
 *   GET {base}/availability?product=ID&start=YYYY-MM-DD&days=N&qty=N → {status, available_qty, total_qty}
 */

const CRM_FEED_DEFAULT = 'https://jm-crm-feed.plain-wood-3958.workers.dev';
const CRM_CATEGORIES_DEFAULT = 'Bouncy Castles & Slides|Action Inflatables|Assault Courses|Bungee Runs|Climbing Walls|Inflatables|Wipeout & Last Man Standing|Rodeo Bull Hire|Surf Simulator Hire';
const CRM_EXCLUDE_DEFAULT = '90581,90827,90828,90217,90281,90677,90788';   // sports-day package, trampolines, bunting, solid climbing walls, rodeo rain cover

function crm_setting(string $k): string {
    $v = trim((string)s($k));
    $legacy = ['https://crm.jments.co.uk', 'https://crm.jments.co.uk/', '/api/public/products?category=inflatables', '/api/public/availability', '/api/public/enquiries'];
    if ($v === '' || in_array($v, $legacy, true)) {
        return ['crm_base_url' => CRM_FEED_DEFAULT, 'crm_products_path' => '/products?ids={ids}&category={categories}',
            'crm_availability_path' => '/availability', 'crm_categories' => CRM_CATEGORIES_DEFAULT, 'crm_exclude' => CRM_EXCLUDE_DEFAULT][$k] ?? '';
    }
    return $v;
}

function crm_on(): bool { return (bool)s('crm_enabled', true) && crm_setting('crm_base_url') !== ''; }

/** CRM IDs of every product the site sells (including ones hidden on the site, so their data stays current). */
function crm_ids(): array {
    $ids = [];
    foreach (data()['products'] ?? [] as $p) if (preg_match('/^\d{1,12}$/', (string)($p['crm_id'] ?? ''))) $ids[] = (string)$p['crm_id'];
    return array_values(array_unique($ids));
}
function crm_excluded(): array { return array_filter(array_map('trim', explode(',', crm_setting('crm_exclude')))); }

/** Imported copy of the CRM (prices, specs, photos) used when the feed can't be reached. */
function crm_snapshot(): array {
    static $snap = null;
    if ($snap === null) {
        $f = ROOT . '/data/crm-prices.json';
        $snap = is_file($f) ? (json_decode((string)file_get_contents($f), true) ?: []) : [];
    }
    return $snap;
}

/** Feed row → the fields this site uses. */
function crm_normalise(array $r): array {
    $out = ['id' => (string)($r['id'] ?? ''), 'name' => (string)($r['name'] ?? ''), 'online' => !empty($r['online']), 'categories' => (string)($r['categories'] ?? '')];
    $pr = [];
    foreach ((array)($r['prices'] ?? []) as $d => $v) if ($v !== null && $v !== '' && (float)$v > 0) $pr[(string)(int)$d] = (float)$v;
    if (!isset($pr['1']) && (float)($r['price'] ?? 0) > 0) $pr['1'] = (float)$r['price'];
    ksort($pr, SORT_NUMERIC);
    if ($pr) { $out['prices'] = $pr; $out['price_from'] = $pr['1'] ?? min($pr); }
    if (isset($r['qty']) && is_numeric($r['qty'])) $out['stock_qty'] = (int)$r['qty'];
    $unit = fn($v, $u) => preg_match('/^\d+(\.\d+)?$/', trim((string)$v)) ? trim((string)$v) . $u : trim((string)$v);
    $spec = [];
    if (trim((string)($r['size'] ?? '')) !== '') $spec['dimensions'] = trim($r['size']);
    if (trim((string)($r['weight'] ?? '')) !== '') $spec['weight'] = $unit($r['weight'], 'kg');
    if (trim((string)($r['power'] ?? '')) !== '' && !preg_match('/^n\/?a$/i', trim($r['power']))) $spec['power'] = $unit($r['power'], 'W');
    if (trim((string)($r['setup_time'] ?? '')) !== '') $spec['setup'] = trim($r['setup_time']);
    if ($spec) $out['spec'] = $spec;
    if (trim((string)($r['users'] ?? '')) !== '') $out['players'] = trim($r['users']);
    if (trim((string)($r['description'] ?? '')) !== '') $out['description'] = (string)$r['description'];
    $photos = array_values(array_filter((array)($r['photos'] ?? []), fn($u) => is_string($u) && preg_match('#^https://#', $u)));
    if ($photos) $out['photos'] = $photos;
    $out['updated_at'] = (string)($r['updated_at'] ?? '');
    return $out;
}

function crm_request(string $path, int $timeout = 5): ?array {
    if (!crm_on()) return null;
    $urlFull = rtrim(crm_setting('crm_base_url'), '/') . $path;
    $headers = ['Accept: application/json', 'User-Agent: JMInflatableHire/1.1'];
    if (s('crm_api_key')) $headers[] = 'Authorization: Bearer ' . s('crm_api_key');
    if (function_exists('curl_init')) {
        $ch = curl_init($urlFull);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $timeout, CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_HTTPHEADER => $headers, CURLOPT_FOLLOWLOCATION => false]);
        $res = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        $ctx = stream_context_create(['http' => ['method' => 'GET', 'header' => implode("\r\n", $headers), 'timeout' => $timeout, 'ignore_errors' => true]]);
        $res = @file_get_contents($urlFull, false, $ctx);
        $code = 0;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) $code = (int)$m[1];
    }
    if ($res === false || $code < 200 || $code >= 300) return null;
    $j = json_decode((string)$res, true);
    return is_array($j) ? $j : null;
}

/** Live CRM rows keyed by CRM ID, refreshed every crm_cache_seconds (default 30). Null when there's no data at all. */
function crm_fetch_products(bool $force = false): ?array {
    $f = CACHE_DIR . '/crm_products.json';
    $ttl = max(0, (int)s('crm_cache_seconds', 30));
    if (!$force && is_file($f) && filemtime($f) > time() - $ttl) return json_decode((string)file_get_contents($f), true);
    $fail = CACHE_DIR . '/crm_fail';
    if (!$force && is_file($fail) && filemtime($fail) > time() - 60) return is_file($f) ? json_decode((string)file_get_contents($f), true) : null;
    // Lock so only one visitor refreshes at a time; everyone else gets the current copy.
    $lock = @fopen(CACHE_DIR . '/crm_lock', 'c');
    if ($lock && !flock($lock, LOCK_EX | LOCK_NB) && !$force) return is_file($f) ? json_decode((string)file_get_contents($f), true) : null;
    $path = strtr(crm_setting('crm_products_path'), ['{ids}' => rawurlencode(implode(',', crm_ids())), '{categories}' => rawurlencode(crm_setting('crm_categories'))]);
    $j = crm_request($path);
    $list = $j['products'] ?? null;
    if (is_array($list)) {
        $rows = [];
        foreach ($list as $r) if (is_array($r) && !empty($r['id'])) $rows[(string)$r['id']] = crm_normalise($r);
        $tmp = $f . '.tmp';
        file_put_contents($tmp, json_encode(['at' => time(), 'rows' => $rows]));
        rename($tmp, $f);
        @unlink($fail);
        if ($lock) flock($lock, LOCK_UN);
        return ['at' => time(), 'rows' => $rows];
    }
    touch($fail);
    if ($lock) flock($lock, LOCK_UN);
    return is_file($f) ? json_decode((string)file_get_contents($f), true) : null;
}

function crm_status(): array {
    $f = CACHE_DIR . '/crm_products.json';
    $c = is_file($f) ? (json_decode((string)file_get_contents($f), true) ?: []) : [];
    $rows = $c['rows'] ?? [];
    return ['enabled' => crm_on(), 'feed' => crm_setting('crm_base_url'), 'cached_at' => $c['at'] ?? null, 'count' => count($rows),
        'photos' => array_sum(array_map(fn($r) => count($r['photos'] ?? []), $rows)),
        'failing' => is_file(CACHE_DIR . '/crm_fail') && filemtime(CACHE_DIR . '/crm_fail') > time() - 120,
        'snapshot_at' => crm_snapshot()['taken_at'] ?? null, 'snapshot_count' => count(crm_snapshot()['products'] ?? [])];
}

/** Site products merged with the CRM. */
function crm_products(): array {
    static $merged = null;
    if ($merged !== null) return $merged;
    $local = data()['products'] ?? [];
    $live = crm_on() ? crm_fetch_products() : null;
    $rows = [];
    foreach (crm_snapshot()['products'] ?? [] as $r) if (!empty($r['id'])) $rows[(string)$r['id']] = crm_normalise($r);
    $isLive = is_array($live) && isset($live['rows']);
    if ($isLive) foreach ($live['rows'] as $id => $r) $rows[(string)$id] = $r;

    $used = [];
    foreach ($local as &$p) {
        $id = (string)($p['crm_id'] ?? '');
        if ($id === '') continue;
        if ($isLive && !isset($live['rows'][$id])) { $p['crm_gone'] = true; continue; }   // hidden or deleted in the CRM → off the site
        if (isset($rows[$id])) { $p = crm_apply($p, $rows[$id], $isLive); $used[$id] = true; }
    }
    unset($p);

    // New online stock in the site's CRM categories is added automatically (live feed only).
    if ($isLive && s('crm_auto_add', true)) {
        $taken = array_column($local, 'slug');
        $cats = array_map('strtolower', array_map('trim', explode('|', crm_setting('crm_categories'))));
        foreach ($live['rows'] as $id => $r) {
            $id = (string)$id;
            if (isset($used[$id]) || in_array($id, crm_excluded(), true) || empty($r['online']) || $r['name'] === '') continue;
            $rc = array_map('strtolower', array_map('trim', explode(',', $r['categories'])));
            if (!array_intersect($rc, $cats)) continue;
            $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(str_replace(['&', "'"], ['and', ''], preg_replace('/\s+hire$/i', '', $r['name'])))), '-');
            if ($slug === '' || in_array($slug, $taken, true)) continue;
            $taken[] = $slug;
            $cats0 = crm_guess_cats(['name' => $r['name'], 'category' => $r['categories']]);
            $local[] = crm_apply(['slug' => $slug, 'name' => preg_replace('/\s+hire$/i', '', $r['name']), 'cats' => $cats0,
                'short' => mb_strimwidth(strip_tags($r['description'] ?? ''), 0, 160, '…'), 'image' => crm_guess_image($cats0), 'crm_id' => $id, 'crm_added' => true], $r, true);
        }
    }
    return $merged = array_values(array_filter($local, fn($p) => empty($p['hidden']) && empty($p['crm_gone'])));
}

function crm_apply(array $p, array $r, bool $live): array {
    if (!empty($r['prices'])) { $p['prices'] = $r['prices']; $p['price_from'] = $r['price_from']; }
    if (!empty($r['stock_qty'])) $p['stock_qty'] = $r['stock_qty']; else unset($p['stock_qty']);   // never advertise "0 in stock"
    if (!empty($r['spec'])) $p['spec'] = array_replace($p['spec'] ?? [], $r['spec']);
    if (!empty($r['players'])) $p['players'] = $r['players'];
    if (!empty($r['description']) && s('crm_use_descriptions', true)) {
        $txt = trim((string)$r['description']);
        $p['description'] = preg_match('/<(p|ul|ol|br|h\d)\b/i', $txt) ? $txt : '<p>' . nl2br(e($txt)) . '</p>';
        if (empty($p['short'])) $p['short'] = mb_strimwidth(strip_tags($txt), 0, 160, '…');
    }
    if (!empty($r['photos']) && s('prefer_crm_images', true)) {
        $p['crm_image'] = $r['photos'][0];
        $p['crm_photos'] = array_slice($r['photos'], 1, 11);
    }
    $p['crm_live'] = $live;
    $p['crm_updated'] = $r['updated_at'] ?? '';
    return $p;
}

function crm_guess_cats(array $r): array {
    $n = strtolower(($r['name'] ?? '') . ' ' . ($r['category'] ?? ''));
    $c = [];
    if (preg_match('/rodeo|surf sim|snowboard sim/', $n)) return ['rodeo'];
    if (preg_match('/assault|obstacle/', $n)) return ['assault-courses'];
    if (preg_match('/castle|bounc|disco dome|a.?frame|toddler/', $n)) $c[] = 'bouncy-castles';
    if (str_contains($n, 'slide')) $c[] = 'slides';
    if (preg_match('/wipeout|last man|gladiator|bungee|wrecking|demolition|leap of faith|base jump|ladder|velcro|pillow|hamster ball|roller|climbing|maze/', $n)) $c[] = 'challenges';
    return $c ?: ['games'];
}

function crm_guess_image(array $cats): string {
    return ['bouncy-castles' => 'bouncy-castle-hire', 'slides' => 'inflatable-slide-hire', 'assault-courses' => 'assault-course-hire',
        'challenges' => 'inflatable-challenge-hire', 'rodeo' => 'rodeo-bull-hire'][$cats[0] ?? ''] ?? 'inflatable-games-hire';
}

/** Live availability for a product on a date, straight from CRM bookings. */
function crm_availability(array $p, string $start, int $days, int $qty): array {
    $id = (string)($p['crm_id'] ?? '');
    if (!preg_match('/^\d+$/', $id)) return ['status' => 'unknown'];
    $path = crm_setting('crm_availability_path');
    $j = crm_request($path . (str_contains($path, '?') ? '&' : '?') . http_build_query(['product' => $id, 'start' => $start, 'days' => $days, 'qty' => $qty]), 4);
    if ($j === null || empty($j['status'])) return ['status' => 'unknown'];
    return ['status' => (string)$j['status'], 'available_qty' => (int)($j['available_qty'] ?? 0), 'total_qty' => (int)($j['total_qty'] ?? 0)];
}

/** The feed is read-only; enquiries go by email (and the enquiries log) until the CRM accepts them. */
function crm_send_enquiry(array $enq): ?array { return null; }
