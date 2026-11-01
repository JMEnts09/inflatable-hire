<?php
page_start(['title' => t('avail.title'), 'meta' => t('avail.meta'), 'path' => '/availability', 'crumbs' => [t('avail.h1') => '/availability']]);
?>
<section class="wrap page-pad split">
  <div><h1><?= e(t('avail.h1')) ?></h1>
  <?= t('avail.intro') ?></div>
  <aside><?php availability_widget(); ?></aside>
</section>
<?php foreach (category_keys() as $k): if ($k === 'inflatable-hire') continue; $items = products_in(data()['pages'][$k]['cat']); if (!$items) continue; ?>
<section class="wrap section"><h2 class="sec-title"><a href="/<?= e($k) ?>"><?= e(data()['pages'][$k]['h1']) ?></a></h2>
  <div class="grid3"><?php foreach ($items as $p) product_card($p); ?></div>
</section>
<?php endforeach; page_end();
