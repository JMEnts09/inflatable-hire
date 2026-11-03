<?php
declare(strict_types=1);
require __DIR__ . '/inc/functions.php';
require __DIR__ . '/inc/layout.php';

$path = '/' . trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/');
if ($path !== '/' && str_ends_with($_SERVER['REQUEST_URI'] ?? '', '/')) { header('Location: ' . $path, true, 301); exit; }

$categoryPages = category_keys();
$localPages = local_keys();
$parts = explode('/', trim($path, '/'));

switch (true) {
    case $path === '/':
        require __DIR__ . '/templates/home.php'; break;
    case count($parts) === 1 && (in_array($parts[0], $categoryPages, true) || in_array($parts[0], event_keys(), true)):
        $key = $parts[0]; require __DIR__ . '/templates/category.php'; break;
    case count($parts) === 2 && page_has_town($parts[0], $parts[1]):
        $key = $parts[0]; $loc = find_location($parts[1]); require __DIR__ . '/templates/location.php'; break;
    case count($parts) === 2 && $parts[0] === 'hire' && find_product($parts[1]) !== null:
        $p = find_product($parts[1]); require __DIR__ . '/templates/product.php'; break;
    case $path === '/areas': require __DIR__ . '/templates/areas.php'; break;
    case $path === '/availability': require __DIR__ . '/templates/availability.php'; break;
    case $path === '/gallery': require __DIR__ . '/templates/gallery.php'; break;
    case $path === '/faq': require __DIR__ . '/templates/faq.php'; break;
    case $path === '/contact': require __DIR__ . '/templates/contact.php'; break;
    case $path === '/thank-you': require __DIR__ . '/templates/thanks.php'; break;
    case $path === '/sitemap.xml': require __DIR__ . '/templates/sitemap.php'; break;
    case $path === '/robots.txt':
        header('Content-Type: text/plain'); echo "User-agent: *\nDisallow: /admin/\nDisallow: /api/\nDisallow: /thank-you\nSitemap: " . url('/sitemap.xml') . "\n"; break;
    case $path === '/api/availability': require __DIR__ . '/inc/api_availability.php'; break;
    case $path === '/api/enquiry': require __DIR__ . '/inc/api_enquiry.php'; break;
    default:
        http_response_code(404);
        page_start(['title' => 'Page not found | ' . s('site_name'), 'path' => $path, 'noindex' => true]);
        echo '<section class="wrap page-pad"><h1>Page not found</h1>' . t('notfound.text') . '</section>';
        page_end();
}
