<?php
page_start(['title' => 'Thank you | ' . s('site_name'), 'path' => '/thank-you', 'noindex' => true]);
?>
<section class="wrap page-pad"><h1>Thanks – we've got your enquiry</h1>
<p class="lead">Our team will be in touch shortly with availability and a quote<?= !empty($_GET['ref']) ? ' (reference ' . e($_GET['ref']) . ')' : '' ?>.</p>
<p><a class="btn" href="/">Back to home</a></p></section>
<?php page_end();
