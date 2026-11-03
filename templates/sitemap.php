<?php
header('Content-Type: application/xml; charset=utf-8');
$urls = [['/', '1.0'], ['/availability', '0.8'], ['/areas', '0.7'], ['/gallery', '0.6'], ['/faq', '0.6'], ['/contact', '0.7']];
foreach (category_keys() as $c) $urls[] = ["/$c", '0.9'];
foreach (event_keys() as $c) $urls[] = ["/$c", '0.8'];
foreach (crm_products() as $p) $urls[] = [product_url($p), '0.8'];
foreach (all_locations() as $l) foreach (local_keys() as $c) $urls[] = ["/$c/{$l['slug']}", '0.6'];
$mod = date('Y-m-d', filemtime(DATA_FILE));
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
foreach ($urls as [$u, $pr]) echo '<url><loc>' . e(url($u)) . "</loc><lastmod>$mod</lastmod><priority>$pr</priority></url>\n";
echo '</urlset>';
