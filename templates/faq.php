<?php
$all = []; $seen = [];
foreach (data()['pages'] as $pg) foreach ($pg['faq'] ?? [] as $f) if (!isset($seen[$f['q']])) { $seen[$f['q']] = 1; $all[] = $f; }
page_start(['title' => t('faq.title'), 'meta' => t('faq.meta'), 'path' => '/faq', 'crumbs' => ['FAQ' => '/faq'], 'schema' => [faq_schema($all)]]);
echo '<section class="wrap page-pad"><h1>' . e(t('faq.h1')) . '</h1></section>';
faq_block($all, t('faq.sub')); page_end();
