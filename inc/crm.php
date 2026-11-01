<?php
/**
 * JM CRM (crm.jments.co.uk) connector.
 *
 * The CRM is the master for stock, prices, specs and availability. This site only reads from it.
 * Endpoints are configurable in Admin → CRM. Expected responses (JSON):
 *
 *   GET  {products_path}               → {"products":[{ "id", "slug", "name", "price_from", "prices":{"1":695,"2":1042}, "stock_qty",
 *                                          "staffed":false, "images":["https://..."], "spec":{"dimensions","weight","power","players"},
 *                                          "spec_sheet_url", "category" }]}
 *   GET  {availability_path}?product=ID&start=YYYY-MM-DD&days=N&qty=N
 *                                       → {"available_qty":2, "total_qty":3, "status":"available|limited|unavailable"}
 *   POST {enquiry_path}  (JSON body)   → {"ok":true, "reference":"ENQ-1234"}
 *
 * Every request sends "Authorization: Bearer {crm_api_key}". If the CRM is unreachable the site falls back to the
 * locally stored product data and shows "we'll confirm availability" rather than breaking.
 */

function crm_on(): bool { return (bool)s('crm_enabled') && s('crm_base_url') !== ''; }

function crm_request(string $method, string $path, ?array $body = null, int $timeout = 6): ?array {
    if (!crm_on()) return null;
    $urlFull = rtrim((string)s('crm_base_url'), '/') . $path;
    $headers = ['Accept: application/json', 'User-Agent: JMInflatableHire/1.0'];
    if (s('crm_api_key')) $headers[] = 'Authorization: Bearer ' . s('crm_api_key');
    if ($body !== null) $headers[] = 'Content-Type: application/json';

    if (function_exists('curl_init')) {
        $ch = curl_init($urlFull);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $timeout, CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HTTPHEADER => $headers, CURLOPT_FOLLOWLOCATION => false,
        ]);
        if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        $res = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        $ctx = stream_context_create(['http' => ['method' => $method, 'header' => implode("\r\n", $headers),
            'content' => $body !== null ? json_encode($body) : null, 'timeout' => $timeout, 'ignore_errors' => true]]);
        $res = @file_get_contents($urlFull, false, $ctx);
        $code = 0;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) $code = (int)$m[1];
    }
    if ($res === false || $code < 200 || $code >= 300) return null;
    $j = json_decode((string)$res, true);
    return is_array($j) ? $j : null;
}

/** Raw CRM product list, cached. */
function crm_fetch_products(bool $force = false): ?array {
    $f = CACHE_DIR . '/crm_products.json';
    $ttl = max(1, (int)s('crm_cache_minutes', 15)) * 60;
    if (!$force && is_file($f) && filemtime($f) > time() - $ttl) {
        return json_decode((string)file_get_contents($f), true);
    }
    $fail = CACHE_DIR . '/crm_fail';
    if (!$force && is_file($fail) && filemtime($fail) > time() - 300) {     // CRM was down <5 min ago: don't hammer it
        return is_file($f) ? json_decode((string)file_get_contents($f), true) : null;
    }
    $j = crm_request('GET', (string)s('crm_products_path'));
    $list = $j['products'] ?? (array_is_list($j ?? []) ? $j : null);
    if (is_array($list)) {
        file_put_contents($f, json_encode($list));
        @unlink($fail);
        return $list;
    }
    touch($fail);
    // CRM down: serve the last good copy if we have one.
    return is_file($f) ? json_decode((string)file_get_contents($f), true) : null;
}

function crm_status(): array {
    $f = CACHE_DIR . '/crm_products.json';
    return ['enabled' => crm_on(), 'cached_at' => is_file($f) ? filemtime($f) : null,
            'count' => is_file($f) ? count(json_decode((string)file_get_contents($f), true) ?: []) : 0];
}

/** Local products merged with live CRM stock, prices, specs and images. */
function crm_products(): array {
    static $merged = null;
    if ($merged !== null) return $merged;
    $local = data()['products'] ?? [];
    $remote = crm_on() ? (crm_fetch_products() ?? []) : [];
    $byKey = [];
    foreach ($remote as $r) {
        if (!empty($r['id'])) $byKey['id:' . $r['id']] = $r;
        if (!empty($r['slug'])) $byKey['slug:' . $r['slug']] = $r;
    }
    $used = [];
    foreach ($local as &$p) {
        $r = $byKey['id:' . ($p['crm_id'] ?? '')] ?? $byKey['slug:' . ($p['crm_slug'] ?? $p['slug'])] ?? null;
        if ($r) {
            $used[$r['id'] ?? $r['slug']] = true;
            $p = crm_apply($p, $r);
        }
    }
    unset($p);
    // Any inflatable stock in the CRM that isn't on the site yet is added automatically.
    foreach ($remote as $r) {
        $k = $r['id'] ?? $r['slug'] ?? null;
        if ($k === null || isset($used[$k]) || empty($r['slug']) || empty($r['name'])) continue;
        $local[] = crm_apply(['slug' => $r['slug'], 'name' => $r['name'], 'cats' => crm_guess_cats($r),
            'short' => $r['short'] ?? ($r['description'] ?? ''), 'image' => crm_guess_image(crm_guess_cats($r)), 'crm_id' => $r['id'] ?? ''], $r);
    }
    return $merged = array_values(array_filter($local, fn($p) => empty($p['hidden'])));
}

function crm_apply(array $p, array $r): array {
    foreach (['price_from', 'prices', 'stock_qty', 'staffed', 'spec', 'spec_sheet_url'] as $k) {
        if (isset($r[$k]) && $r[$k] !== '' && $r[$k] !== null) $p[$k] = $r[$k];
    }
    if (!empty($r['spec']['players'])) $p['players'] = $r['spec']['players'];
    if (!empty($r['images'][0]) && s('prefer_crm_images', true)) $p['crm_image'] = $r['images'][0];
    if (empty($p['crm_id']) && !empty($r['id'])) $p['crm_id'] = $r['id'];
    $p['crm_live'] = true;
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

/** Live availability for a product on a date. */
function crm_availability(array $p, string $start, int $days, int $qty): array {
    $id = $p['crm_id'] ?: ($p['crm_slug'] ?? $p['slug']);
    $q = http_build_query(['product' => $id, 'start' => $start, 'days' => $days, 'qty' => $qty]);
    $path = (string)s('crm_availability_path');
    $j = crm_request('GET', $path . (str_contains($path, '?') ? '&' : '?') . $q);
    if ($j === null) return ['status' => 'unknown'];
    $avail = (int)($j['available_qty'] ?? 0);
    $status = $j['status'] ?? ($avail >= $qty ? 'available' : ($avail > 0 ? 'limited' : 'unavailable'));
    return ['status' => $status, 'available_qty' => $avail, 'total_qty' => (int)($j['total_qty'] ?? ($p['stock_qty'] ?? 0))];
}

function crm_send_enquiry(array $enq): ?array {
    if (!crm_on() || !s('crm_enquiry_path')) return null;
    return crm_request('POST', (string)s('crm_enquiry_path'), $enq + ['source' => parse_url(base(), PHP_URL_HOST) ?: 'inflatable-site']);
}
