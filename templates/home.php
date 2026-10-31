<?php
$pg = data()['pages']['home'];
$cats = [];
foreach (category_keys() as $k) {
    if ($k === 'inflatable-hire') continue;
    $c = data()['pages'][$k];
    $cats[] = ['/' . $k, $c['h1'], $c['kicker'] ?? '', $c['image'], count(products_in($c['cat']))];
}
$all = crm_products();
$pick = ['rodeo-bull', 'army-theme-assault-course-100ft', 'gladiators-duel', 'adult-bouncy-castle-red-and-black-with-roof', 'inflatable-football-darts', 'wipeout-challenge-inflatable'];
$featured = array_values(array_filter($all, fn($p) => in_array($p['slug'], $pick, true)));
if (count($featured) < 6) $featured = array_slice($all, 0, 6);
page_start(['title' => $pg['title'], 'meta' => $pg['meta'], 'path' => '/', 'image' => $pg['image'],
    'schema' => [faq_schema($pg['faq']), ['@type' => 'Service', 'name' => 'Inflatable hire', 'serviceType' => 'Inflatable and bouncy castle hire',
        'provider' => ['@id' => url('/#business')], 'areaServed' => ['@type' => 'Country', 'name' => 'United Kingdom'],
        'offers' => ['@type' => 'AggregateOffer', 'priceCurrency' => 'GBP', 'lowPrice' => min(array_column($all, 'price_from')), 'offerCount' => count($all)]]]]);
?>
<section class="hero">
  <div class="hero-bg"><?= picture($pg['image'], 'Twin inflatable racing slide hire', '', true, '100vw') ?></div>
  <div class="wrap hero-inner">
    <p class="kicker"><?= e($pg['kicker']) ?></p>
    <h1><?= e($pg['h1']) ?><span>Castles · Slides · Assault Courses · Rodeo Bulls</span></h1>
    <p class="lead"><?= e($pg['intro']) ?></p>
    <div class="hero-btns"><a class="btn" href="/inflatable-hire">Browse all <?= count($all) ?> inflatables</a><a class="btn btn-ghost" href="/availability">Check availability</a></div>
    <ul class="trust"><li>Delivered, set up &amp; collected</li><li>PIPA tested &amp; fully insured</li><li>Operators available</li><li>Live stock from our booking system</li></ul>
  </div>
</section>

<section class="wrap section">
  <h2 class="sec-title">Choose your inflatables</h2>
  <div class="cat-grid">
    <?php foreach ($cats as [$href, $t, $d, $img, $n]): ?>
      <a class="cat" href="<?= e($href) ?>"><?= picture($img, $t, '', false, '(max-width:760px) 100vw, 25vw') ?><div><h3><?= e($t) ?></h3><p><?= e($d) ?></p><span><?= $n ?> to choose from →</span></div></a>
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
  <h2 class="sec-title">Popular inflatables</h2>
  <div class="grid3"><?php foreach ($featured as $p) product_card($p); ?></div>
  <p class="center"><a class="btn btn-ghost" href="/inflatable-hire">See the full range</a></p>
</section>

<section class="section alt">
  <div class="wrap split rev">
    <div><?= picture('human-table-football-inflatable-hire', 'Human table football inflatable at a corporate fun day', 'rounded') ?></div>
    <div class="prose"><?php foreach (array_slice($pg['sections'], 2) as $sec): ?><h2><?= e($sec['h2']) ?></h2><?= $sec['html'] ?><?php endforeach; ?>
      <h2>How hiring works</h2>
      <ol class="steps"><li><b>Check availability</b> for your date and pick your inflatables.</li><li><b>Get your quote</b> with the space and power each item needs.</li><li><b>Confirm your booking</b> – we send your booking form, risk assessment and insurance.</li><li><b>We deliver &amp; set up</b>, then collect after the event.</li></ol>
    </div>
  </div>
</section>

<section class="wrap section">
  <h2 class="sec-title">Inflatable hire near you</h2>
  <ul class="area-chips"><?php foreach (data()['locations'] as $l): ?><li><a href="/inflatable-hire/<?= e($l['slug']) ?>"><?= e($l['city']) ?></a></li><?php endforeach; ?></ul>
</section>
<?php faq_block($pg['faq']); page_end();
