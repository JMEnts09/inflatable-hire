<?php
/** POST /api/enquiry → emails info@ and pushes the enquiry into the CRM funnel. */
start_session();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /contact'); exit; }
$back = fn(string $msg) => (print('<!doctype html><meta charset=utf-8><p style="font:16px sans-serif;padding:2em">' . e($msg) . ' <a href="/contact">Go back</a></p>')) && exit;
if (!empty($_POST['website'])) { header('Location: /thank-you'); exit; }          // honeypot
if (!csrf_ok()) $back('Your session expired – please try again.');
if (rate_limited('enq', 5, 600)) $back('Too many enquiries – please email us instead.');

$f = fn($k, $max = 200) => trim(mb_substr(str_replace(["\r", "\0"], '', (string)($_POST[$k] ?? '')), 0, $max));
$enq = ['name' => $f('name'), 'company' => $f('company'), 'email' => $f('email'), 'phone' => $f('phone', 40),
        'date' => $f('date', 10), 'venue' => $f('venue'), 'product' => $f('product'), 'qty' => (int)$f('qty', 3), 'days' => (int)$f('days', 3),
        'staff' => $f('staff', 3) === 'yes', 'message' => $f('message', 4000)];
if ($enq['name'] === '' || !filter_var($enq['email'], FILTER_VALIDATE_EMAIL)) $back('Please enter your name and a valid email address.');
$p = $enq['product'] ? find_product($enq['product']) : null;
$enq['product_name'] = $p['name'] ?? 'Not specified';
$enq['crm_product_id'] = $p['crm_id'] ?? '';

$crm = crm_send_enquiry($enq);
$ref = $crm['reference'] ?? '';

$body = "New inflatable hire enquiry" . ($ref ? " ($ref)" : '') . "\n\n";
foreach (['name' => 'Name', 'company' => 'Company', 'email' => 'Email', 'phone' => 'Phone', 'date' => 'Event date', 'venue' => 'Venue/postcode',
          'product_name' => 'Inflatable', 'qty' => 'Quantity', 'days' => 'Days'] as $k => $l) $body .= "$l: {$enq[$k]}\n";
$body .= 'Staff / operators: ' . ($enq['staff'] ? 'Yes' : 'No') . "\n\n{$enq['message']}\n\nSent from " . base() . "\nCRM: " . ($crm ? 'logged' : 'not logged (CRM unreachable or not configured)');
$host = parse_url(base(), PHP_URL_HOST) ?: 'localhost';
$headers = "From: JM Inflatable Hire Website <no-reply@$host>\r\nReply-To: {$enq['email']}\r\nContent-Type: text/plain; charset=UTF-8";
@mail((string)s('notify_email', s('email')), 'Inflatable hire enquiry – ' . $enq['name'], $body, $headers);
@file_put_contents(ROOT . '/data/enquiries.log', json_encode($enq + ['at' => date('c'), 'ref' => $ref]) . "\n", FILE_APPEND | LOCK_EX);
header('Location: /thank-you' . ($ref ? '?ref=' . urlencode($ref) : ''));
