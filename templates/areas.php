<?php
$groups = locations_by_region();
$main = local_keys()[0] ?? 'inflatable-hire';
page_start(['title' => t('areas.title'), 'meta' => t('areas.meta'), 'path' => '/areas', 'crumbs' => ['Areas' => '/areas']]);
?>
<section class="wrap page-pad"><h1><?= e(t('areas.h1')) ?></h1><p class="lead"><?= e(t('areas.intro')) ?></p>
<p class="area-jump"><?php foreach (array_keys($groups) as $r): ?><a href="#<?= e(slugify($r)) ?>"><?= e($r) ?></a> <?php endforeach; ?></p>
<?php foreach ($groups as $region => $ls): ?>
<h2 id="<?= e(slugify($region)) ?>">Inflatable hire in <?= e($region) ?></h2>
<div class="areas-table">
<?php foreach ($ls as $l): ?>
  <div class="area-row"><h3><a href="/<?= e($main) ?>/<?= e($l['slug']) ?>"><?= e($l['city']) ?></a></h3>
  <p><?php $links = []; foreach (local_keys() as $k) if ($k !== $main) $links[] = '<a href="/' . e($k) . '/' . e($l['slug']) . '">' . e(page_label($k)) . '</a>'; echo implode(' · ', $links); ?></p></div>
<?php endforeach; ?>
</div>
<?php endforeach; ?>
</section>
<?php page_end();
