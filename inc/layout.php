<?php
/** Shared page chrome: <head> with SEO + schema, header nav, footer. */

function nav_items(): array {
    $out = [];
    foreach (tl('nav.items', [], true) as [$href, $label]) if ($href !== '') $out[$href] = $label !== '' ? $label : $href;
    return $out;
}

/** Only allow site-relative or http(s) links from editable text. */
function safe_href(string $h): string {
    $h = trim($h);
    return preg_match('#^(/|https?://|mailto:|tel:|\#)#i', $h) ? $h : '/' . ltrim($h, '/');
}

function org_schema(): array {
    return [
        '@type' => 'EntertainmentBusiness', '@id' => url('/#business'),
        'name' => s('site_name'), 'url' => url('/'), 'email' => s('email'),
        'telephone' => s('phone') ?: null, 'image' => url(img_src('inflatable-hire-hero')),
        'logo' => url('/assets/img/logo.svg'),
        'description' => s('tagline'), 'priceRange' => '££',
        'areaServed' => ['@type' => 'Country', 'name' => 'United Kingdom'],
        'parentOrganization' => ['@type' => 'Organization', 'name' => s('company'), 'url' => s('parent_site')],
    ];
}

/**
 * $m: title, meta, path, image, schema (array of extra nodes), crumbs ([label => path]), noindex
 */
function page_start(array $m): void {
    $title = $m['title'] ?? s('site_name');
    $desc = $m['meta'] ?? s('tagline');
    if (s('noindex_site')) $m['noindex'] = true;
    $canon = url($m['path'] ?? '/');
    $og = url(img_src(($m['image'] ?? '') ?: (s('default_share_image') ?: 'inflatable-hire-hero')));
    $graph = [org_schema(), ['@type' => 'WebSite', '@id' => url('/#site'), 'url' => url('/'), 'name' => s('site_name'), 'publisher' => ['@id' => url('/#business')]]];
    if (!empty($m['crumbs'])) {
        $items = []; $i = 1;
        foreach (['Home' => '/'] + $m['crumbs'] as $label => $p) $items[] = ['@type' => 'ListItem', 'position' => $i++, 'name' => $label, 'item' => url($p)];
        $graph[] = ['@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }
    foreach ($m['schema'] ?? [] as $node) $graph[] = $node;
    $accent = preg_match('/^#[0-9a-f]{6}$/i', (string)s('accent')) ? s('accent') : '#D7141A';
    ?><!doctype html>
<html lang="en-GB">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<link rel="canonical" href="<?= e($canon) ?>">
<?php if (!empty($m['noindex'])): ?><meta name="robots" content="noindex,follow"><?php endif; ?>
<meta property="og:type" content="website"><meta property="og:site_name" content="<?= e(s('site_name')) ?>">
<meta property="og:title" content="<?= e($title) ?>"><meta property="og:description" content="<?= e($desc) ?>">
<meta property="og:url" content="<?= e($canon) ?>"><meta property="og:image" content="<?= e($og) ?>"><meta property="og:locale" content="en_GB">
<meta name="twitter:card" content="summary_large_image">
<?php if (s('gsc_verification')): ?><meta name="google-site-verification" content="<?= e(s('gsc_verification')) ?>"><?php endif; ?>
<link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Anton&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/site.css?v=7">
<style>:root{--red:<?= e($accent) ?>}</style>
<script type="application/ld+json"><?= json_encode(['@context' => 'https://schema.org', '@graph' => strip_nulls($graph)], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<?php if (s('ga4_id')): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e(s('ga4_id')) ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config','<?= e(s('ga4_id')) ?>');</script>
<?php endif; ?>
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<header class="site-header">
  <div class="wrap hdr">
    <a class="logo" href="/" aria-label="<?= e(s('site_name')) ?> home">
      <?php if (t('brand.logo_image') !== ''): ?><img class="logo-img" src="<?= e(img_src(t('brand.logo_image'), 480)) ?>" alt=""><?php else: ?><svg viewBox="0 0 40 40" aria-hidden="true"><rect x="4" y="15" width="32" height="20" rx="5" fill="var(--red)"/><rect x="2" y="9" width="9" height="26" rx="4.5" fill="var(--red)"/><rect x="29" y="9" width="9" height="26" rx="4.5" fill="var(--red)"/><path d="M1.5 10Q6.5 1 11.5 10ZM28.5 10Q33.5 1 38.5 10Z" fill="#FFC93C"/><path d="M14 35V26a6 6 0 0 1 12 0v9Z" fill="#0b0b0f"/><rect x="11" y="15" width="3" height="11" rx="1.5" fill="#FFC93C"/><rect x="26" y="15" width="3" height="11" rx="1.5" fill="#FFC93C"/></svg><?php endif; ?>
      <span><?= e(t('brand.logo_text1')) ?><b><?= e(t('brand.logo_text2')) ?></b></span>
    </a>
    <button class="menu-btn" aria-expanded="false" aria-controls="nav">Menu</button>
    <nav id="nav" class="nav" aria-label="Main">
      <?php foreach (nav_items() as $p => $l): ?>
        <a href="<?= e(safe_href($p)) ?>"<?= ($m['path'] ?? '') === $p ? ' aria-current="page"' : '' ?>><?= e($l) ?></a>
      <?php endforeach; ?>
      <a class="btn btn-sm" href="/contact"><?= e(t('nav.cta')) ?></a>
    </nav>
  </div>
</header>
<main id="main">
<?php if (!empty($m['crumbs'])): ?>
<nav class="crumbs wrap" aria-label="Breadcrumb"><a href="/">Home</a><?php foreach ($m['crumbs'] as $l => $p): ?> <span>/</span> <a href="<?= e($p) ?>"><?= e($l) ?></a><?php endforeach; ?></nav>
<?php endif;
}

function strip_nulls(array $a): array {
    foreach ($a as $k => $v) {
        if ($v === null) unset($a[$k]);
        elseif (is_array($v)) $a[$k] = strip_nulls($v);
    }
    return $a;
}

function page_end(): void {
    $locs = array_slice(data()['locations'] ?? [], 0, 16);
    ?>
</main>
<section class="cta-band">
  <div class="wrap cta-inner">
    <div><h2><?= e(t('cta.heading')) ?></h2><p><?= e(t('cta.text')) ?></p></div>
    <div class="cta-btns"><a class="btn" href="/availability"><?= e(t('cta.btn1')) ?></a><a class="btn btn-ghost" href="/contact"><?= e(t('cta.btn2')) ?></a></div>
  </div>
</section>
<footer class="site-footer">
  <div class="wrap foot-grid">
    <div>
      <p class="foot-logo"><?= e(t('brand.logo_text1')) ?> <b><?= e(t('brand.logo_text2')) ?></b></p>
      <p><?= e(t('footer.about')) ?><?php if (s('parent_site')): ?> <a href="<?= e(s('parent_site')) ?>"><?= e(s('company')) ?></a><?php endif; ?></p>
      <p><a href="mailto:<?= e(s('email')) ?>"><?= e(s('email')) ?></a><?php if (s('phone')): ?><br><a href="tel:<?= e(preg_replace('/\s+/', '', (string)s('phone'))) ?>"><?= e(s('phone')) ?></a><?php endif; ?><?php if (s('address')): ?><br><?= e(s('address')) ?><?php endif; ?></p>
    </div>
    <div>
      <h3><?= e(t('footer.col2')) ?></h3>
      <ul>
        <?php foreach (category_keys() as $k): ?><li><a href="/<?= e($k) ?>"><?= e(data()['pages'][$k]['h1']) ?></a></li><?php endforeach; ?>
        <?php foreach (event_keys() as $k): ?><li><a href="/<?= e($k) ?>"><?= e(data()['pages'][$k]['h1']) ?></a></li><?php endforeach; ?>
        <li><a href="/gallery">Gallery</a> · <a href="/faq">FAQ</a> · <a href="/news">News</a> · <a href="/areas">Areas</a></li>
      </ul>
    </div>
    <div class="foot-areas">
      <h3><?= e(t('footer.col3')) ?></h3>
      <ul>
        <?php foreach ($locs as $l): ?><li><a href="/<?= e(local_keys()[0] ?? 'inflatable-hire') ?>/<?= e($l['slug']) ?>"><?= e(tf('footer.area_link', ['city' => $l['city']])) ?></a></li><?php endforeach; ?>
        <li><a href="/areas">All areas →</a></li>
      </ul>
    </div>
  </div>
  <div class="wrap foot-base"><p>© <?= date('Y') ?> <?= e(s('company')) ?>. <?= e(t('footer.legal')) ?></p></div>
</footer>
<script src="/assets/js/site.js?v=1" defer></script>
</body>
</html>
<?php
}

/** Reusable blocks */
function product_card(array $p): void {
    $img = $p['crm_image'] ?? null; ?>
  <article class="card">
    <a href="<?= e(product_url($p)) ?>" class="card-img">
      <?= $img ? '<img src="' . e($img) . '" alt="' . e($p['name']) . ' hire" loading="lazy" decoding="async" width="800" height="600">' : picture($p['image'], $p['name'] . ' hire', '', false, '(max-width:760px) 100vw, 33vw') ?>
      <?php if (!empty($p['stock_qty'])): ?><span class="stock"><?= (int)$p['stock_qty'] ?> in stock</span><?php endif; ?>
    </a>
    <div class="card-body">
      <h3><a href="<?= e(product_url($p)) ?>"><?= e($p['name']) ?></a></h3>
      <p><?= e($p['short'] ?? '') ?></p>
      <div class="card-meta"><span class="price">From <?= money($p['price_from'] ?? 0) ?> <small>+ VAT</small></span><?php if (!empty($p['players'])): ?><span class="players"><?= e($p['players']) ?></span><?php endif; ?></div>
      <a class="btn btn-sm btn-block" href="<?= e(product_url($p)) ?>#check"><?= e(t('product.card_btn')) ?></a>
    </div>
  </article>
<?php }

function faq_block(array $faqs, string $h = 'Frequently asked questions'): void {
    if (!$faqs) return; ?>
  <section class="faq wrap"><h2><?= e($h) ?></h2>
    <?php foreach ($faqs as $f): ?><details><summary><?= e($f['q']) ?></summary><div><p><?= e($f['a']) ?></p></div></details><?php endforeach; ?>
  </section>
<?php }

function faq_schema(array $faqs): array {
    return ['@type' => 'FAQPage', 'mainEntity' => array_map(fn($f) => ['@type' => 'Question', 'name' => $f['q'],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']]], $faqs)];
}

/** Availability checker widget; $p = a single product or null for "all products" dropdown. */
function availability_widget(?array $p = null): void {
    $all = crm_products(); ?>
  <form class="checker" data-checker id="check" novalidate>
    <h3><?= e(t('checker.heading')) ?></h3>
    <p class="muted"><?= e(t('checker.sub')) ?></p>
    <?php if ($p): ?>
      <input type="hidden" name="product" value="<?= e($p['slug']) ?>">
    <?php else: ?>
      <label>Inflatable<select name="product" required><?php foreach ($all as $x): ?><option value="<?= e($x['slug']) ?>"><?= e($x['name']) ?></option><?php endforeach; ?></select></label>
    <?php endif; ?>
    <div class="row3">
      <label>Event date<input type="date" name="start" required min="<?= date('Y-m-d') ?>"></label>
      <label>Days<select name="days"><?php foreach ([1, 2, 3, 4, 5, 7] as $d): ?><option value="<?= $d ?>"><?= $d ?></option><?php endforeach; ?></select></label>
      <label>Quantity<select name="qty"><?php for ($i = 1; $i <= 4; $i++): ?><option><?= $i ?></option><?php endfor; ?></select></label>
    </div>
    <button class="btn btn-block" type="submit"><?= e(t('checker.button')) ?></button>
    <div class="checker-result" aria-live="polite"></div>
  </form>
<?php }
