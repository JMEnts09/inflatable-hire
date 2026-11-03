<?php
$others = array_slice(array_values(array_filter(news_articles(), fn($x) => $x['slug'] !== $a['slug'])), 0, 3);
$img = !empty($a['image']) && img_exists($a['image']) ? $a['image'] : '';
page_start(['title' => $a['title'] . ' | ' . s('site_name'), 'meta' => $a['meta'] ?: $a['summary'], 'path' => '/news/' . $a['slug'], 'image' => $img ?: null,
    'crumbs' => ['News' => '/news', $a['title'] => '/news/' . $a['slug']],
    'schema' => [['@type' => 'Article', 'headline' => $a['title'], 'datePublished' => $a['date'], 'dateModified' => $a['date'],
        'description' => $a['meta'] ?: $a['summary'], 'image' => $img ? url(img_src($img)) : null,
        'author' => ['@type' => 'Organization', 'name' => s('company') ?: s('site_name')], 'publisher' => ['@id' => url('/#business')],
        'mainEntityOfPage' => url('/news/' . $a['slug'])]]]);
?>
<article class="wrap page-pad article">
  <p class="kicker"><a href="/news">News &amp; guides</a> · <time datetime="<?= e($a['date']) ?>"><?= e(date('j F Y', strtotime($a['date']))) ?></time></p>
  <h1><?= e($a['title']) ?></h1>
  <?php if ($a['summary']): ?><p class="lead"><?= e($a['summary']) ?></p><?php endif; ?>
  <?php if ($img): ?><div class="article-img"><?= picture($img, $a['title'], 'rounded', true) ?></div><?php endif; ?>
  <div class="prose"><?= $a['html'] ?></div>
  <p class="hero-btns"><a class="btn" href="/availability">Check availability</a><a class="btn btn-ghost" href="/contact">Get a quote</a></p>
</article>
<?php if ($others): ?><section class="wrap section"><h2 class="sec-title">More news &amp; guides</h2><div class="grid3">
<?php foreach ($others as $o): ?><article class="card news-card"><div class="card-body"><p class="muted"><?= e(date('j F Y', strtotime($o['date']))) ?></p><h3><a href="/news/<?= e($o['slug']) ?>"><?= e($o['title']) ?></a></h3><p><?= e($o['summary']) ?></p></div></article><?php endforeach; ?>
</div></section><?php endif; page_end();
