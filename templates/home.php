<?php
$pg = data()['pages']['home'];
$cats = [];
foreach (category_keys() as $k) {
    if (data()['pages'][$k]['cat'] === 'all') continue;
    $c = data()['pages'][$k];
    $cats[] = ['/' . $k, $c['h1'], $c['kicker'] ?? '', $c['image'], count(products_in($c['cat']))];
}
$all = crm_products();
$featured = [];
foreach (tl('home.popular') as $slug) if ($p = find_product($slug)) $featured[] = $p;
if (!$featured) $featured = array_slice($all, 0, 6);
page_start(['title' => $pg['title'], 'meta' => $pg['meta'], 'path' => '/', 'image' => $pg['image'],
    'schema' => [faq_schema($pg['faq'] ?? []), ['@type' => 'Service', 'name' => s('site_name'), 'serviceType' => 'Inflatable and bouncy castle hire',
        'provider' => ['@id' => url('/#business')], 'areaServed' => ['@type' => 'Country', 'name' => 'United Kingdom'],
        'offers' => $all ? ['@type' => 'AggregateOffer', 'priceCurrency' => 'GBP', 'lowPrice' => min(array_column($all, 'price_from')), 'offerCount' => count($all)] : null]]]);
?>
<section class="hero">
  <div class="hero-bg"><?= picture($pg['image'], $pg['image_alt'] ?? $pg['h1'], '', true, '100vw') ?></div>
  <div class="wrap hero-inner">
    <p class="kicker"><?= e($pg['kicker']) ?></p>
    <h1><?= e($pg['h1']) ?><?php if (t('home.subhead') !== ''): ?><span><?= e(t('home.subhead')) ?></span><?php endif; ?></h1>
    <p class="lead"><?= e($pg['intro']) ?></p>
    <div class="hero-btns"><a class="btn" href="/inflatable-hire"><?= e(tf('home.btn1', ['count' => count($all)])) ?></a><a class="btn btn-ghost" href="/availability"><?= e(t('home.btn2')) ?></a></div>
    <ul class="trust"><?php foreach (tl('home.trust') as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul>
  </div>
</section>

<section class="wrap section">
  <h2 class="sec-title"><?= e(t('home.cats_heading')) ?></h2>
  <div class="cat-grid">
    <?php foreach ($cats as [$href, $title, $d, $img, $n]): ?>
      <a class="cat" href="<?= e($href) ?>"><?= picture($img, $title, '', false, '(max-width:760px) 100vw, 33vw') ?><div><h3><?= e($title) ?></h3><p><?= e($d) ?></p><span><?= e(tf('home.cats_more', ['count' => $n])) ?></span></div></a>
    <?php endforeach; ?>
  </div>
</section>

<section class="section alt">
  <div class="wrap split">
    <div class="prose"><?php foreach (array_slice($pg['sections'], 0, 2) as $sec): ?><h2><?= e($sec['h2']) ?></h2><?= $sec['html'] ?><?php endforeach; ?></div>
    <aside><?php availability_widget(); ?></aside>
  </div>
</section>

<section class="wrap section">
  <h2 class="sec-title"><?= e(t('home.popular_heading')) ?></h2>
  <div class="grid3"><?php foreach ($featured as $p) product_card($p); ?></div>
  <p class="center"><a class="btn btn-ghost" href="/inflatable-hire"><?= e(t('home.popular_btn')) ?></a></p>
</section>

<section class="section alt">
  <div class="wrap split rev">
    <div><?= t('home.side_image') !== '' ? picture(t('home.side_image'), t('home.side_alt'), 'rounded') : '' ?></div>
    <div class="prose"><?php foreach (array_slice($pg['sections'], 2) as $sec): ?><h2><?= e($sec['h2']) ?></h2><?= $sec['html'] ?><?php endforeach; ?>
      <h2><?= e(t('home.steps_heading')) ?></h2>
      <ol class="steps"><?php foreach (tl('home.steps', [], true) as [$b, $rest]): ?><li><b><?= e($b) ?></b> <?= e($rest) ?></li><?php endforeach; ?></ol>
    </div>
  </div>
</section>

<section class="wrap section">
  <h2 class="sec-title"><?= e(t('home.areas_heading')) ?></h2>
  <ul class="area-chips"><?php foreach (data()['locations'] as $l): ?><li><a href="/<?= e(local_keys()[0] ?? 'inflatable-hire') ?>/<?= e($l['slug']) ?>"><?= e($l['city']) ?></a></li><?php endforeach; ?></ul>
</section>
<?php faq_block($pg['faq'] ?? []); page_end();
