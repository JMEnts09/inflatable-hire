<?php
// FAQ hub: topic groups first, then every question from the service and event pages.
$topics = data()['faq_topics'] ?? [];
$all = []; $seen = [];
foreach ($topics as $tp) foreach ($tp['items'] as $f) if (!isset($seen[$f['q']])) { $seen[$f['q']] = 1; $all[] = $f; }
$more = [];
foreach (data()['pages'] as $pg) foreach ($pg['faq'] ?? [] as $f) if (!isset($seen[$f['q']])) { $seen[$f['q']] = 1; $all[] = $f; $more[] = $f; }
page_start(['title' => t('faq.title'), 'meta' => t('faq.meta'), 'path' => '/faq', 'crumbs' => ['FAQ' => '/faq'], 'schema' => [faq_schema($all)]]);
?>
<section class="wrap page-pad"><h1><?= e(t('faq.h1')) ?></h1><p class="lead"><?= e(t('faq.sub')) ?></p>
<?php if ($topics): ?><p class="area-jump"><?php foreach ($topics as $tp): ?><a href="#<?= e(slugify($tp['topic'])) ?>"><?= e($tp['topic']) ?></a> <?php endforeach; ?></p><?php endif; ?>
</section>
<?php foreach ($topics as $tp): ?><div id="<?= e(slugify($tp['topic'])) ?>"><?php faq_block($tp['items'], $tp['topic']); ?></div><?php endforeach; ?>
<?php if ($more) faq_block($more, $topics ? 'More questions' : t('faq.sub')); page_end();
