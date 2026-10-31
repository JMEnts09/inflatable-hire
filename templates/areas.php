<?php
$locs = data()['locations'];
usort($locs, fn($a, $b) => strcmp($a['city'], $b['city']));
page_start(['title' => 'Inflatable Hire Near You | UK Areas Covered | JM', 'meta' => 'We deliver bouncy castles, assault courses, inflatable games and rodeo bulls across the UK – London, Manchester, Birmingham, Leeds, Glasgow & more.', 'path' => '/areas', 'crumbs' => ['Areas' => '/areas']]);
?>
<section class="wrap page-pad"><h1>Inflatable hire areas</h1><p class="lead">Our crews deliver, set up and collect across England, Scotland, Wales and Northern Ireland. Choose your area:</p>
<div class="areas-table">
<?php foreach ($locs as $l): ?>
  <div class="area-row"><h2><?= e($l['city']) ?> <small><?= e($l['region']) ?></small></h2>
  <p><?php $links = []; foreach (local_keys() as $k) $links[] = '<a href="/' . e($k) . '/' . e($l['slug']) . '">' . e(page_label($k)) . '</a>'; echo implode(' · ', $links); ?></p></div>
<?php endforeach; ?>
</div></section>
<?php page_end();
