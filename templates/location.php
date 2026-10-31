<?php
$pg = data()['pages'][$key];
$c = $loc['city'];
$label = page_label($key);                                         // e.g. "Bouncy Castle"
$h1 = "$label Hire $c";
$items = products_in($pg['cat']);
$prices = array_column($items, 'price_from');
$from = $prices ? money(min($prices)) : '';
$v = crc32($c . $key) % 3;                                         // stable variant per page
$angle = $pg['angle'] ?? strtolower($pg['h1']);

$intros = [
    "Looking for $label hire in $c? We deliver $angle to venues across $c and {$loc['region']}, with set-up, safety checks and collection included.",
    "Our $c $label hire service brings $angle straight to your event – from gardens, schools and sports clubs to parks and event venues across {$loc['region']}.",
    "Book $angle anywhere in $c. Our crew covers the whole of {$loc['region']}, including {$loc['nearby']}.",
];
$title = "$h1 | From $from + VAT | JM Inflatable Hire";
if (mb_strlen($title) > 65) $title = "$h1 | JM Inflatable Hire";
$meta = "$label hire in $c" . ($from ? " from $from + VAT" : '') . ". Delivered, set up & collected across {$loc['region']}. Check live availability & get a quote today.";
$faqs = [
    ['q' => "Do you deliver inflatables to $c?", 'a' => "Yes. We deliver, install and collect inflatables throughout $c and {$loc['region']}, including {$loc['nearby']}."],
    ['q' => "How much is $label hire in $c?", 'a' => ($from ? "Prices start from $from + VAT for a one-day hire. " : '') . "Multi-day hires are discounted, and delivery to $c is quoted with your hire."],
    ['q' => "Which $c venues do you supply?", 'a' => "We supply schools, sports clubs, parks, offices, hotels and private gardens across $c, as well as event venues such as {$loc['venues']}."],
];
page_start(['title' => $title, 'meta' => $meta, 'path' => "/$key/{$loc['slug']}", 'image' => $pg['image'],
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
      <p class="lead"><?= e($loc['intro'] ?: $intros[$v]) ?></p>
      <?php if ($from): ?><p class="from">From <b><?= $from ?></b> + VAT · delivered to <?= e($c) ?></p><?php endif; ?>
      <div class="hero-btns"><a class="btn" href="#check">Check availability</a><a class="btn btn-ghost" href="/contact?interest=<?= e(urlencode("$h1")) ?>">Get a quote</a></div>
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
      <h2><?= e($label) ?> hire for <?= e($c) ?> events</h2>
      <p>From children's birthday parties and school summer fairs to corporate fun days, team building, festivals and community events, our <?= e(strtolower($label)) ?> hire is booked across <?= e($c) ?> all year round. Everything is our own stock, delivered and installed by our own crew.</p>
      <h2>Venues we supply in and around <?= e($c) ?></h2>
      <p>We regularly deliver to venues such as <?= e($loc['venues']) ?>, plus schools, sports clubs, parks and private gardens across <?= e($loc['nearby']) ?>. We check access, space and power before the day, so everything is set up, staked down and safety-checked before your guests arrive.</p>
      <h2>What's included</h2>
      <ul><li>Delivery to <?= e($c) ?>, set-up and collection</li><li>Electric blower, stakes or sandbags and safety mats</li><li>PIPA-tested inflatables and public liability insurance</li><li>Space and power requirements with your quote</li><li>Optional operators and staff for the day</li></ul>
      <p>Also popular in <?= e($c) ?>: <?php $links = []; foreach (local_keys() as $k) if ($k !== $key) $links[] = '<a href="/' . e($k) . '/' . e($loc['slug']) . '">' . e(page_label($k)) . ' hire ' . e($c) . '</a>'; echo implode(', ', $links); ?>.</p>
    </div>
    <aside class="sticky"><?php availability_widget(); ?></aside>
  </div>
</section>
<?php faq_block($faqs, "$h1 – FAQs"); page_end();
