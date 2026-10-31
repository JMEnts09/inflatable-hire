<?php
page_start(['title' => 'Inflatable Availability Checker | Live Stock | JM Inflatable Hire', 'meta' => 'Check live availability of our bouncy castles, slides, assault courses, inflatable games and rodeo bulls for your event date. Real-time stock from our booking system.', 'path' => '/availability', 'crumbs' => ['Check availability' => '/availability']]);
?>
<section class="wrap page-pad split">
  <div><h1>Check inflatable availability</h1>
  <p class="lead">Pick an inflatable, your event date and how many days you need. Availability comes live from our booking system – the same stock our team books from.</p>
  <p>Bookings are first come, first served, so if your date shows available we'd recommend requesting your quote straight away.</p></div>
  <aside><?php availability_widget(); ?></aside>
</section>
<?php foreach (category_keys() as $k): if ($k === 'inflatable-hire') continue; $items = products_in(data()['pages'][$k]['cat']); if (!$items) continue; ?>
<section class="wrap section"><h2 class="sec-title"><a href="/<?= e($k) ?>"><?= e(data()['pages'][$k]['h1']) ?></a></h2>
  <div class="grid3"><?php foreach ($items as $p) product_card($p); ?></div>
</section>
<?php endforeach; page_end();
