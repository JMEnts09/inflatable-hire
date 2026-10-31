<?php
$all = []; $seen = [];
foreach (data()['pages'] as $pg) foreach ($pg['faq'] ?? [] as $f) if (!isset($seen[$f['q']])) { $seen[$f['q']] = 1; $all[] = $f; }
page_start(['title' => 'Inflatable Hire FAQs | Prices, Space, Power & Safety', 'meta' => 'Answers to common questions about inflatable and bouncy castle hire: prices, space and power needed, delivery, weather, safety and insurance.', 'path' => '/faq', 'crumbs' => ['FAQ' => '/faq'], 'schema' => [faq_schema($all)]]);
echo '<section class="wrap page-pad"><h1>Inflatable hire FAQs</h1></section>';
faq_block($all, 'Your questions answered'); page_end();
