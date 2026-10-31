<?php
$g = data()['gallery'];
page_start(['title' => 'Inflatable Hire Gallery | Bouncy Castles, Assault Courses & More', 'meta' => 'Photos of our bouncy castles, slides, assault courses, inflatable games and rodeo bulls at parties, fun days and corporate events.', 'path' => '/gallery', 'crumbs' => ['Gallery' => '/gallery'],
  'schema' => [['@type' => 'ImageGallery', 'name' => 'Inflatable hire gallery', 'image' => array_map(fn($i) => ['@type' => 'ImageObject', 'contentUrl' => url(img_src($i['img'])), 'caption' => $i['alt']], $g)]]]);
?>
<section class="wrap page-pad"><h1>Inflatable hire gallery</h1><p class="lead">Our inflatables at parties, fun days, schools and corporate events.</p>
<div class="gallery"><?php foreach ($g as $i): ?><figure><?= picture($i['img'], $i['alt'], '', false, '(max-width:760px) 100vw, 33vw') ?><figcaption><?= e($i['alt']) ?></figcaption></figure><?php endforeach; ?></div></section>
<?php page_end();
