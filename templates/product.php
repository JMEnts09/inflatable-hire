<?php
$cat = $p['cats'][0] ?? 'all';
$catKey = page_key_for_cat($cat);
$catPage = data()['pages'][$catKey];
$staffed = !empty($p['staffed']);
$operator = $staffed || in_array('rodeo', $p['cats'] ?? [], true);
$price = (float)($p['price_from'] ?? 0);
$multi = [];
foreach ([1, 2, 3, 4, 7] as $d) $multi[$d] = $p['prices'][(string)$d] ?? round($price * hire_multiplier($d, $staffed));
$spec = $p['spec'] ?? [];
$img = $p['crm_image'] ?? null;
$extra = array_values(array_filter($p['images'] ?? [], 'img_exists'));
$related = array_slice(array_values(array_filter(products_in($cat), fn($x) => $x['slug'] !== $p['slug'])), 0, 3);
$imgUrl = $img ?: url(img_src($p['image']));
$vars = ['name' => $p['name'], 'price' => money($price), 'short' => $p['short'] ?? ''];
$title = trim((string)($p['seo_title'] ?? '')) ?: tf('product.title', $vars);
$meta = trim((string)($p['seo_meta'] ?? '')) ?: mb_strimwidth(tf('product.meta', $vars), 0, 158, '…');
page_start(['title' => $title, 'meta' => $meta, 'path' => product_url($p), 'image' => $p['image'],
    'crumbs' => [$catPage['h1'] => '/' . $catKey, $p['name'] => product_url($p)],
    'schema' => [['@type' => 'Product', 'name' => $p['name'] . ' Hire', 'description' => $p['short'] ?? '', 'image' => $imgUrl,
        'brand' => ['@type' => 'Brand', 'name' => s('company')], 'category' => 'Inflatable hire',
        'offers' => ['@type' => 'Offer', 'priceCurrency' => 'GBP', 'price' => $price, 'url' => url(product_url($p)),
            'availability' => 'https://schema.org/InStock', 'businessFunction' => 'http://purl.org/goodrelations/v1#LeaseOut',
            'seller' => ['@id' => url('/#business')]]]]]);
?>
<section class="page-hero">
  <div class="wrap split">
    <div>
      <?= $img ? '<img class="rounded" src="' . e($img) . '" alt="' . e($p['name']) . ' hire">' : picture($p['image'], $p['image_alt'] ?? ($p['name'] . ' hire'), 'rounded', true) ?>
      <?php if ($extra): ?><div class="thumbs-row"><?php foreach ($extra as $x): ?><a href="<?= e(img_src($x)) ?>" target="_blank" rel="noopener"><?= picture($x, $p['name'] . ' hire', '', false, '120px') ?></a><?php endforeach; ?></div><?php endif; ?>
    </div>
    <div>
      <p class="kicker"><a href="/<?= e($catKey) ?>"><?= e($catPage['h1']) ?></a><?php if (!empty($p['crm_live'])): ?> · <span class="live">Live stock</span><?php endif; ?></p>
      <h1><?= e($p['h1'] ?? '') ?: e($p['name']) . ' Hire' ?></h1>
      <p class="lead"><?= e($p['short'] ?? '') ?></p>
      <table class="prices"><caption><?= e(t('product.prices_caption')) ?></caption>
        <tr><?php foreach ($multi as $d => $v): ?><th><?= $d ?> day<?= $d > 1 ? 's' : '' ?></th><?php endforeach; ?></tr>
        <tr><?php foreach ($multi as $v): ?><td><?= money($v) ?></td><?php endforeach; ?></tr>
      </table>
      <ul class="spec">
        <?php if (!empty($p['players'])): ?><li><b>Suitable for</b><?= e($p['players']) ?></li><?php endif; ?>
        <?php if (!empty($p['stock_qty'])): ?><li><b>Units in stock</b><?= (int)$p['stock_qty'] ?></li><?php endif; ?>
        <?php foreach (['dimensions' => 'Size', 'weight' => 'Weight', 'power' => 'Power', 'setup' => 'Set-up time'] as $k => $l): if (!empty($spec[$k])): ?><li><b><?= $l ?></b><?= e($spec[$k]) ?></li><?php endif; endforeach; ?>
        <?php if ($operator): ?><li><b>Operator</b>Included</li><?php endif; ?>
      </ul>
      <?php if (!empty($p['spec_sheet_url'])): ?><p><a class="btn btn-ghost btn-sm" href="<?= e($p['spec_sheet_url']) ?>" target="_blank" rel="noopener">Download spec sheet (PDF)</a></p><?php endif; ?>
    </div>
  </div>
</section>
<section class="section alt">
  <div class="wrap split">
    <div class="prose">
      <h2><?= e(tf('product.about_heading', ['name' => $p['name']])) ?></h2>
      <?php if (trim(strip_tags((string)($p['description'] ?? ''))) !== ''): ?>
        <?= $p['description'] ?>
      <?php else: ?>
        <p><?= e($p['short'] ?? '') ?></p>
      <?php endif; ?>
      <p><?= e(t('product.included')) ?><?= $operator ? ' ' . e(t('product.operator')) : '' ?></p>
      <?= empty($p['description']) ? ($catPage['sections'][0]['html'] ?? '') : '' ?>
      <h2><?= e(t('product.safety_heading')) ?></h2>
      <?= t('product.safety_text') ?>
    </div>
    <aside class="sticky"><?php availability_widget($p); ?></aside>
  </div>
</section>
<?php if ($related): ?>
<section class="wrap section"><h2 class="sec-title"><?= e(t('product.related_heading')) ?></h2><div class="grid3"><?php foreach ($related as $r) product_card($r); ?></div></section>
<?php endif; page_end();
