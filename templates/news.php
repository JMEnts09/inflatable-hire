<?php
$list = news_articles();
page_start(['title' => 'News & Guides | Inflatable Hire Safety, Tips & Updates | ' . s('site_name'), 'meta' => 'Inflatable hire news and guides from JM Inflatable Hire – PIPA safety, event planning tips, weather advice and new attractions.', 'path' => '/news', 'crumbs' => ['News' => '/news']]);
?>
<section class="wrap page-pad"><h1>News &amp; guides</h1><p class="lead">Safety, planning tips and updates from the JM Inflatable Hire team.</p>
<div class="grid3 news-grid">
<?php foreach ($list as $a): ?>
  <article class="card news-card">
    <a href="/news/<?= e($a['slug']) ?>"><?= !empty($a['image']) && img_exists($a['image']) ? picture($a['image'], $a['title'], '') : '' ?></a>
    <div class="card-body"><p class="muted"><time datetime="<?= e($a['date']) ?>"><?= e(date('j F Y', strtotime($a['date']))) ?></time></p>
    <h2><a href="/news/<?= e($a['slug']) ?>"><?= e($a['title']) ?></a></h2><p><?= e($a['summary']) ?></p><a class="btn btn-ghost" href="/news/<?= e($a['slug']) ?>">Read more</a></div>
  </article>
<?php endforeach; ?>
</div></section>
<?php page_end();
