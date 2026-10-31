<?php
/** GET /api/availability?product=slug&start=YYYY-MM-DD&days=N&qty=N → live CRM availability + price estimate. */
if (rate_limited('avail', 40, 300)) json_out(['error' => 'Too many checks – please wait a moment.'], 429);
$p = find_product((string)($_GET['product'] ?? ''));
$start = (string)($_GET['start'] ?? '');
$days = max(1, min(30, (int)($_GET['days'] ?? 1)));
$qty = max(1, min(20, (int)($_GET['qty'] ?? 1)));
if (!$p) json_out(['error' => 'Unknown inflatable.'], 400);
$dt = DateTime::createFromFormat('Y-m-d', $start);
if (!$dt || $dt->format('Y-m-d') !== $start || $start < date('Y-m-d')) json_out(['error' => 'Please choose a valid future date.'], 400);

$a = crm_on() ? crm_availability($p, $start, $days, $qty) : ['status' => 'unknown'];
$unit = $p['prices'][(string)$days] ?? round((float)($p['price_from'] ?? 0) * hire_multiplier($days, !empty($p['staffed'])));
json_out($a + [
    'product' => $p['name'],
    'estimate' => $unit * $qty,
    'estimate_text' => money($unit * $qty) . ' + VAT',
    'quote_url' => '/contact?' . http_build_query(['product' => $p['slug'], 'date' => $start, 'days' => $days, 'qty' => $qty]),
]);
