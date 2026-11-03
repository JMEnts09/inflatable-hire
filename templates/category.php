<?php
$pg = data()['pages'][$key];
$items = page_products($pg);
$prices = array_values(array_filter(array_column($items, 'price_from'), fn($x) => $x > 0));
$schema = [['@type' => 'Service', 'name' => $pg['h1'], 'serviceType' => $pg['h1'], 'description' => $pg['meta'],
    'provider' => ['@id' => url('/#business')], 'areaServed' => ['@type' => 'Country', 'name' => 'United Kingdom'],
    'offers' => $prices ? ['@type' => 'AggregateOffer', 'priceCurrency' => 'GBP', 'lowPrice' => min($prices), 'highPrice' => max($prices), 'offerCount' => count($items)] : null]];
if (!empty($pg['faq'])) $schema[] = faq_schema($pg['faq']);
page_start(['title' => $pg['title'], 'meta' => $pg['meta'], 'path' => '/' . $key, 'image' => $pg['image'],
    'crumbs' => [$pg['h1'] => '/' . $key], 'schema' => $schema]);
?>
<section class="page-hero">
  <div class="wrap split">
    <div>
      <p class="kicker"><?= e($pg['kicker']) ?></p>
      <h1><?= e($pg['h1']) ?></h1>
      <p class="lead"><?= e($pg['intro']) ?></p>
      <?php if ($prices): ?><p class="from"><?= str_replace(e(money(min($prices))), '<b>' . e(money(min($prices))) . '</b>', e(tf('cat.from', ['price' => money(min($prices))]))) ?></p><?php endif; ?>
      <div class="hero-btns"><a class="btn" href="#range"><?= e(t('cat.btn1')) ?></a><a class="btn btn-ghost" href="/contact?interest=<?= e(urlencode($pg['h1'])) ?>"><?= e(t('cat.btn2')) ?></a></div>
    </div>
    <div><?= picture($pg['image'], $pg['image_alt'] ?? $pg['h1'], 'rounded', true) ?></div>
  </div>
</section>

<?php if ($pg['cat'] === 'all' && empty($pg['match'])): ?>
<nav class="wrap cat-jump" aria-label="Categories"><?php foreach (category_keys() as $k): if ($k === $key || !empty(data()['pages'][$k]['match'])) continue; ?><a href="#<?= e($k) ?>"><?= e(data()['pages'][$k]['h1']) ?></a><?php endforeach; ?></nav>
<?php foreach (category_keys() as $k): if ($k === $key || !empty(data()['pages'][$k]['match'])) continue; $sub = page_products(data()['pages'][$k]); if (!$sub) continue; ?>
<section class="wrap section" id="<?= e($k) ?>">
  <h2 class="sec-title"><a href="/<?= e($k) ?>"><?= e(data()['pages'][$k]['h1']) ?></a> <small><?= count($sub) ?> items</small></h2>
  <div class="grid3"><?php foreach ($sub as $p) product_card($p); ?></div>
</section>
<?php endforeach; ?>
<?php else: ?>
<section class="wrap section" id="range">
  <h2 class="sec-title"><?= e(tf('cat.range_heading', ['h1' => $pg['h1'], 'count' => count($items)])) ?></h2>
  <div class="grid3"><?php foreach ($items as $p) product_card($p); ?></div>
</section>
<?php endif; ?>

<section class="section alt">
  <div class="wrap split">
    <div class="prose"><?php foreach ($pg['sections'] as $sec): ?><h2><?= e($sec['h2']) ?></h2><?= $sec['html'] ?><?php endforeach; ?></div>
    <aside class="sticky"><?php availability_widget(); ?></aside>
  </div>
</section>

<?php if (!empty($pg['event'])): ?>
<section class="wrap section">
  <h2 class="sec-title">Plan another kind of event</h2>
  <ul class="area-chips"><?php foreach (event_keys() as $k): if ($k === $key) continue; ?><li><a href="/<?= e($k) ?>"><?= e(data()['pages'][$k]['h1']) ?></a></li><?php endforeach; ?></ul>
  <h2 class="sec-title" style="margin-top:28px">Popular areas</h2>
  <ul class="area-chips"><?php foreach (data()['locations'] as $l): ?><li><a href="/<?= e($key) ?>/<?= e($l['slug']) ?>"><?= e(page_label($key)) ?> <?= e($l['city']) ?></a></li><?php endforeach; ?></ul>
</section>
<?php endif; ?>
<?php if (!empty($pg['local'])): ?>
<section class="wrap section">
  <h2 class="sec-title"><?= e(tf('cat.areas_heading', ['label' => page_label($key)])) ?></h2>
  <p><?= e(tf('cat.areas_intro', ['label' => page_label($key), 'count' => count(all_locations())])) ?></p>
  <div class="region-list"><?php foreach (locations_by_region() as $region => $ls): ?>
    <details<?= in_array($region, ['Greater London', 'West Midlands', 'Greater Manchester', 'West Yorkshire', 'Scotland'], true) ? ' open' : '' ?>><summary><?= e(page_label($key)) ?> hire in <?= e($region) ?> <small>(<?= count($ls) ?>)</small></summary>
    <ul class="area-chips"><?php foreach ($ls as $l): ?><li><a href="/<?= e($key) ?>/<?= e($l['slug']) ?>"><?= e($l['city']) ?></a></li><?php endforeach; ?></ul></details>
  <?php endforeach; ?></div>
</section>
<?php endif; faq_block($pg['faq'] ?? []); page_end();
