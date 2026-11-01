<?php
$g = data()['gallery'];
page_start(['title' => t('gallery.title'), 'meta' => t('gallery.meta'), 'path' => '/gallery', 'crumbs' => ['Gallery' => '/gallery'],
  'schema' => [['@type' => 'ImageGallery', 'name' => t('gallery.h1'), 'image' => array_map(fn($i) => ['@type' => 'ImageObject', 'contentUrl' => url(img_src($i['img'])), 'caption' => $i['alt']], $g)]]]);
?>
<section class="wrap page-pad"><h1><?= e(t('gallery.h1')) ?></h1><p class="lead"><?= e(t('gallery.intro')) ?></p>
<div class="gallery"><?php foreach ($g as $i): ?><figure><?= picture($i['img'], $i['alt'], '', false, '(max-width:760px) 100vw, 33vw') ?><figcaption><?= e($i['alt']) ?></figcaption></figure><?php endforeach; ?></div></section>
<?php page_end();
