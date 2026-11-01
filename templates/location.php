<?php
$pg = data()['pages'][$key];
$c = $loc['city'];
$label = page_label($key);
$items = products_in($pg['cat']);
$prices = array_column($items, 'price_from');
$from = $prices ? money(min($prices)) : '';
$v = crc32($c . $key) % 3;
$vars = ['label' => $label, 'city' => $c, 'region' => $loc['region'], 'nearby' => $loc['nearby'], 'venues' => $loc['venues'],
    'price' => $from, 'angle' => $pg['angle'] ?? strtolower($pg['h1'])];
$h1 = "$label Hire $c";
$faqs = array_map(fn($r) => ['q' => $r[0], 'a' => $r[1]], tl('town.faq', $vars, true));
page_start(['title' => tf('town.title', $vars), 'meta' => tf('town.meta', $vars), 'path' => "/$key/{$loc['slug']}", 'image' => $pg['image'],
    'crumbs' => [$pg['h1'] => "/$key", $c => "/$key/{$loc['slug']}"],
    'schema' => [['@type' => 'Service', 'name' => $h1, 'serviceType' => $pg['h1'], 'provider' => ['@id' => url('/#business')],
        'areaServed' => ['@type' => 'City', 'name' => $c], 'offers' => $prices ? ['@type' => 'AggregateOffer', 'priceCurrency' => 'GBP', 'lowPrice' => min($prices)] : null],
        faq_schema($faqs)]]);
$show = $pg['cat'] === 'all' ? array_slice($items, 0, 12) : $items;
?>
<section class="page-hero">
  <div class="wrap split">
    <div>
      <p class="kicker"><?= e($c) ?> · <?= e($loc['region']) ?></p>
      <h1><?= e($h1) ?></h1>
      <p class="lead"><?= e($loc['intro'] ?: tf('town.intro' . ($v + 1), $vars)) ?></p>
      <?php if ($from): ?><p class="from">From <b><?= $from ?></b> + VAT · delivered to <?= e($c) ?></p><?php endif; ?>
      <div class="hero-btns"><a class="btn" href="#check"><?= e(t('checker.button')) ?></a><a class="btn btn-ghost" href="/contact?interest=<?= e(urlencode($h1)) ?>"><?= e(t('cat.btn2')) ?></a></div>
    </div>
    <div><?= picture($pg['image'], "$h1 – set up at an event", 'rounded', true) ?></div>
  </div>
</section>

<section class="wrap section">
  <h2 class="sec-title">Available for <?= e($c) ?> events</h2>
  <div class="grid3"><?php foreach ($show as $p) product_card($p); ?></div>
  <?php if (count($show) < count($items)): ?><p class="center"><a class="btn btn-ghost" href="/<?= e($key) ?>">See all <?= count($items) ?> inflatables</a></p><?php endif; ?>
</section>

<section class="section alt">
  <div class="wrap split">
    <div class="prose">
      <?= th('town.body', $vars) ?>
      <p>Also popular in <?= e($c) ?>: <?php $links = []; foreach (local_keys() as $k) if ($k !== $key) $links[] = '<a href="/' . e($k) . '/' . e($loc['slug']) . '">' . e(page_label($k)) . ' hire ' . e($c) . '</a>'; echo implode(', ', $links); ?>.</p>
    </div>
    <aside class="sticky"><?php availability_widget(); ?></aside>
  </div>
</section>
<?php faq_block($faqs, "$h1 – FAQs"); page_end();
