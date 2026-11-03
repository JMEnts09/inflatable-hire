<?php
$pg = data()['pages'][$key];
$c = $loc['city'];
$label = page_label($key);
$items = page_products($pg);
$prices = array_column($items, 'price_from');
$from = $prices ? money(min($prices)) : '';
$v = crc32($c . $key) % 3;
$near = $loc['near'] ?? [];
$vars = ['label' => $label, 'city' => $c, 'region' => $loc['region'], 'nearby' => $loc['nearby'], 'venues' => $loc['venues'],
    'price' => $from, 'angle' => $pg['angle'] ?? strtolower($pg['h1']), 'count' => count($items),
    'near1' => $near[0]['city'] ?? $loc['region'], 'near2' => $near[1]['city'] ?? $loc['region']];
$fill = fn(string $s, bool $esc = false) => strtr($s, array_combine(array_map(fn($k) => '{' . $k . '}', array_keys($vars)), array_map(fn($x) => $esc ? e((string)$x) : (string)$x, array_values($vars))));
$h1 = "$label Hire $c";

// FAQs: this service's own town questions first, then the general ones.
$faqs = [];
foreach ((array)($loc['faq'] ?? []) as $row) $faqs[] = ['q' => $fill($row['q']), 'a' => $fill($row['a'])];
foreach ((array)($pg['town_faq'] ?? []) as $row) $faqs[] = ['q' => $fill($row['q']), 'a' => $fill($row['a'])];
foreach (tl('town.faq', $vars, true) as $r) $faqs[] = ['q' => $r[0], 'a' => $r[1]];

$geo = isset($loc['lat'], $loc['lng']) ? ['@type' => 'GeoCoordinates', 'latitude' => $loc['lat'], 'longitude' => $loc['lng']] : null;
page_start(['title' => tf('town.title', $vars), 'meta' => tf('town.meta', $vars), 'path' => "/$key/{$loc['slug']}", 'image' => $pg['image'],
    'crumbs' => [$pg['h1'] => "/$key", $c => "/$key/{$loc['slug']}"],
    'schema' => [['@type' => 'Service', 'name' => $h1, 'serviceType' => $pg['h1'], 'provider' => ['@id' => url('/#business')],
        'areaServed' => array_filter(['@type' => 'City', 'name' => $c, 'containedInPlace' => ['@type' => 'AdministrativeArea', 'name' => $loc['region']], 'geo' => $geo]),
        'offers' => $prices ? ['@type' => 'AggregateOffer', 'priceCurrency' => 'GBP', 'lowPrice' => min($prices), 'offerCount' => count($items)] : null],
        faq_schema($faqs)]]);
$show = $pg['cat'] === 'all' && empty($pg['match']) ? array_slice($items, 0, 12) : $items;
?>
<section class="page-hero">
  <div class="wrap split">
    <div>
      <p class="kicker"><?= e($c) ?> · <?= e($loc['region']) ?></p>
      <h1><?= e($h1) ?></h1>
      <p class="lead"><?= e($loc['intro'] ?: ($pg['town_intro'][$v] ?? null ? $fill($pg['town_intro'][$v]) : tf('town.intro' . ($v + 1), $vars))) ?></p>
      <?php if ($from): ?><p class="from">From <b><?= $from ?></b> + VAT · delivered to <?= e($c) ?></p><?php endif; ?>
      <div class="hero-btns"><a class="btn" href="#check"><?= e(t('checker.button')) ?></a><a class="btn btn-ghost" href="/contact?interest=<?= e(urlencode($h1)) ?>"><?= e(t('cat.btn2')) ?></a></div>
    </div>
    <div><?= picture($pg['image'], "$h1 – set up at an event", 'rounded', true) ?></div>
  </div>
</section>

<section class="wrap section">
  <h2 class="sec-title"><?= e(['Available for ' . $c . ' events', $label . ' hire in ' . $c . ' – ' . count($items) . ' to choose from', 'Book for your ' . $c . ' event'][$v]) ?></h2>
  <div class="grid3"><?php foreach ($show as $p) product_card($p); ?></div>
  <?php if (count($show) < count($items)): ?><p class="center"><a class="btn btn-ghost" href="/<?= e($key) ?>">See all <?= count($items) ?> inflatables</a></p><?php endif; ?>
</section>

<section class="section alt">
  <div class="wrap split">
    <div class="prose">
      <?= !empty($pg['town_body']) ? $fill($pg['town_body'], true) : '' ?>
      <?php if (!empty($loc['body'])): ?><?= $fill($loc['body']) ?><?php endif; ?>
      <?= th('town.body', $vars) ?>
      <?php if ($near && array_filter($near, fn($n) => page_has_town($key, $n['slug']))): ?>
      <h2><?= e($label) ?> hire near <?= e($c) ?></h2>
      <p>We also cover the towns around <?= e($c) ?>: <?php $links = []; foreach ($near as $n) if (page_has_town($key, $n['slug'])) $links[] = '<a href="/' . e($key) . '/' . e($n['slug']) . '">' . e($label) . ' hire ' . e($n['city']) . '</a> (' . $n['miles'] . ' miles)'; echo implode(', ', $links); ?>.</p>
      <?php endif; ?>
      <p>Also popular in <?= e($c) ?>: <?php $links = []; foreach (local_keys() as $k) if ($k !== $key) $links[] = '<a href="/' . e($k) . '/' . e($loc['slug']) . '">' . e(page_label($k)) . ' hire ' . e($c) . '</a>'; echo implode(', ', $links); ?>.</p>
    </div>
    <aside class="sticky"><?php availability_widget(); ?></aside>
  </div>
</section>
<?php faq_block($faqs, "$h1 – FAQs"); page_end();
