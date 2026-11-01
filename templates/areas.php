<?php
$locs = data()['locations'];
usort($locs, fn($a, $b) => strcmp($a['city'], $b['city']));
page_start(['title' => t('areas.title'), 'meta' => t('areas.meta'), 'path' => '/areas', 'crumbs' => ['Areas' => '/areas']]);
?>
<section class="wrap page-pad"><h1><?= e(t('areas.h1')) ?></h1><p class="lead"><?= e(t('areas.intro')) ?></p>
<div class="areas-table">
<?php foreach ($locs as $l): ?>
  <div class="area-row"><h2><?= e($l['city']) ?> <small><?= e($l['region']) ?></small></h2>
  <p><?php $links = []; foreach (local_keys() as $k) $links[] = '<a href="/' . e($k) . '/' . e($l['slug']) . '">' . e(page_label($k)) . '</a>'; echo implode(' · ', $links); ?></p></div>
<?php endforeach; ?>
</div></section>
<?php page_end();
