<?php
page_start(['title' => t('thanks.h1') . ' | ' . s('site_name'), 'path' => '/thank-you', 'noindex' => true]);
?>
<section class="wrap page-pad"><h1><?= e(t('thanks.h1')) ?></h1>
<p class="lead"><?= e(t('thanks.text')) ?><?= !empty($_GET['ref']) ? ' Your reference is ' . e($_GET['ref']) . '.' : '' ?></p>
<p><a class="btn" href="/"><?= e(t('thanks.btn')) ?></a></p></section>
<?php page_end();
