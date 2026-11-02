<?php
/**
 * Every piece of wording on the site that isn't a page/product record lives here,
 * editable from Admin → Site text. Values saved in admin go to data()['text'][key];
 * anything not saved falls back to the default below.
 *
 * Types: line (one line), text (paragraph), html (rich text), list (one item per line;
 * "a|b" items split into columns), image (picked from the media library).
 * Placeholders like {city} are filled in by the page that uses the text.
 */

function text_fields(): array {
    static $f = null;
    if ($f !== null) return $f;
    $f = [
        // ---------------- Header, footer & branding
        'brand.logo_image' => ['Header & footer', 'Logo image (leave empty to use the built-in castle logo)', 'image', ''],
        'brand.logo_text1' => ['Header & footer', 'Logo text – first part', 'line', 'JM Inflatable'],
        'brand.logo_text2' => ['Header & footer', 'Logo text – second part (red)', 'line', 'Hire'],
        'nav.items' => ['Header & footer', 'Menu links (one per line: /link|Label)', 'list',
            "/bouncy-castle-hire|Bouncy Castles\n/assault-course-hire|Assault Courses\n/inflatable-games-hire|Games\n/inflatable-challenge-hire|Wipeout\n/rodeo-bull-hire|Rodeo Bulls\n/availability|Availability"],
        'nav.cta' => ['Header & footer', 'Menu button', 'line', 'Get a quote'],
        'cta.heading' => ['Header & footer', 'Bottom banner – heading', 'line', 'Ready to book your inflatables?'],
        'cta.text' => ['Header & footer', 'Bottom banner – text', 'text', 'Check live availability for your date, then get a fixed quote within the hour (office hours).'],
        'cta.btn1' => ['Header & footer', 'Bottom banner – first button', 'line', 'Check availability'],
        'cta.btn2' => ['Header & footer', 'Bottom banner – second button', 'line', 'Request a quote'],
        'footer.about' => ['Header & footer', 'Footer – about text', 'text', 'Bouncy castles, assault courses, inflatable games and rodeo bulls across the UK. A JM Entertainment service.'],
        'footer.col2' => ['Header & footer', 'Footer – column 2 heading', 'line', 'Inflatable hire'],
        'footer.col3' => ['Header & footer', 'Footer – column 3 heading', 'line', 'Areas we cover'],
        'footer.area_link' => ['Header & footer', 'Footer – area link wording ({city})', 'line', 'Inflatable hire {city}'],
        'footer.legal' => ['Header & footer', 'Footer – bottom line', 'line', 'All prices exclude VAT.'],

        // ---------------- Home page (title, meta, H1, intro and sections are under Pages → Home)
        'home.subhead' => ['Home page', 'Line under the main heading', 'line', 'Castles · Slides · Assault Courses · Rodeo Bulls'],
        'home.btn1' => ['Home page', 'Main button ({count} = number of inflatables)', 'line', 'Browse all {count} inflatables'],
        'home.btn2' => ['Home page', 'Second button', 'line', 'Check availability'],
        'home.trust' => ['Home page', 'Trust points under the buttons (one per line)', 'list',
            "Delivered, set up & collected\nPIPA tested & fully insured\nOperators available\nLive stock from our booking system"],
        'home.cats_heading' => ['Home page', 'Categories heading', 'line', 'Choose your inflatables'],
        'home.cats_more' => ['Home page', 'Category tile link ({count})', 'line', '{count} to choose from →'],
        'home.popular_heading' => ['Home page', 'Popular inflatables heading', 'line', 'Popular inflatables'],
        'home.popular' => ['Home page', 'Popular inflatables (one product URL slug per line, e.g. rodeo-bull)', 'list',
            "rodeo-bull\narmy-theme-assault-course-100ft\ngladiators-duel\nadult-bouncy-castle-red-and-black-with-roof\ninflatable-football-darts\nwipeout-challenge-inflatable"],
        'home.popular_btn' => ['Home page', 'Button under popular inflatables', 'line', 'See the full range'],
        'home.side_image' => ['Home page', 'Photo beside "How hiring works"', 'image', 'human-table-football-inflatable-hire'],
        'home.side_alt' => ['Home page', 'That photo’s description (alt text)', 'line', 'Human table football inflatable at a corporate fun day'],
        'home.steps_heading' => ['Home page', '"How hiring works" heading', 'line', 'How hiring works'],
        'home.steps' => ['Home page', 'Hiring steps (one per line: Bold part|rest of sentence)', 'list',
            "Check availability|for your date and pick your inflatables.\nGet your quote|with the space and power each item needs.\nConfirm your booking|– we send your booking form, risk assessment and insurance.\nWe deliver & set up|, then collect after the event."],
        'home.areas_heading' => ['Home page', 'Areas heading', 'line', 'Inflatable hire near you'],

        // ---------------- Category pages
        'cat.btn1' => ['Category pages', 'First button', 'line', 'View the range'],
        'cat.btn2' => ['Category pages', 'Second button', 'line', 'Get a quote'],
        'cat.from' => ['Category pages', 'Price line ({price})', 'line', 'From {price} + VAT per day · delivery, set-up & collection'],
        'cat.range_heading' => ['Category pages', 'Products heading ({h1}, {count})', 'line', '{h1} – {count} to choose from'],
        'cat.areas_heading' => ['Category pages', 'Areas heading ({label})', 'line', '{label} hire across the UK'],
        'cat.areas_intro' => ['Category pages', 'Areas intro ({label}, {count})', 'text', 'We deliver, set up and collect {label} hire in {count} towns and cities across England, Scotland, Wales and Northern Ireland. Find your area:'],

        // ---------------- Product pages
        'product.title' => ['Product pages', 'Default SEO title ({name}, {price}, {site}) – each product can override this', 'line', '{name} Hire | From {price} | {site}'],
        'product.meta' => ['Product pages', 'Default meta description ({short}, {price}) – each product can override this', 'text', '{short} Hire from {price} + VAT. UK delivery, set-up & collection. Check live availability.'],
        'product.prices_caption' => ['Product pages', 'Price table caption', 'line', 'Hire prices (+ VAT)'],
        'product.card_btn' => ['Product pages', 'Button on product cards', 'line', 'Check availability'],
        'product.about_heading' => ['Product pages', 'About heading ({name})', 'line', 'About the {name}'],
        'product.included' => ['Product pages', 'Sentence after the description', 'text', 'Every hire includes delivery, set-up, a safety check and collection by our own crew.'],
        'product.operator' => ['Product pages', 'Extra sentence for staffed / rodeo items', 'text', 'A trained operator runs it for the whole hire.'],
        'product.safety_heading' => ['Product pages', 'Safety heading', 'line', 'Space, power and safety'],
        'product.safety_text' => ['Product pages', 'Safety text', 'html', '<p>Most inflatables run from one standard 13 amp socket for the blower, and we bring stakes, sandbags and safety mats. If there\'s no power on site we can supply a petrol blower or generator. Exact dimensions, access needs and power come with your quote, and the risk assessment and insurance are sent once your booking is confirmed.</p>'],
        'product.related_heading' => ['Product pages', '"You might also like" heading', 'line', 'You might also like'],

        // ---------------- Town pages
        'town.title' => ['Town pages', 'SEO title ({label}, {city}, {price}, {region})', 'line', '{label} Hire {city} | From {price} + VAT | JM Inflatable Hire'],
        'town.meta' => ['Town pages', 'Meta description', 'text', '{label} hire in {city} from {price} + VAT – delivered, set up & collected across {city}, {near1}, {near2} and {region}. Live availability & fast quotes.'],
        'town.intro1' => ['Town pages', 'Intro version 1 (pages rotate 3 versions so they aren\'t identical)', 'text', 'Looking for {label} hire in {city}? We deliver {angle} to venues across {city} and {region}, with set-up, safety checks and collection included.'],
        'town.intro2' => ['Town pages', 'Intro version 2', 'text', 'Our {city} {label} hire service brings {angle} straight to your event – from gardens, schools and sports clubs to parks and event venues across {region}.'],
        'town.intro3' => ['Town pages', 'Intro version 3', 'text', 'Book {angle} anywhere in {city}. Our crew covers the whole of {region}, including {nearby}.'],
        'town.body' => ['Town pages', 'Main text ({label}, {city}, {region}, {nearby}, {venues})', 'html',
            '<h2>{label} hire for {city} events</h2><p>From children\'s birthday parties and school summer fairs to corporate fun days, team building, festivals and community events, our {label} hire is booked across {city} all year round. Everything is our own stock, delivered and installed by our own crew.</p><h2>Venues we supply in and around {city}</h2><p>We regularly deliver to venues such as {venues}, plus schools, sports clubs, parks and private gardens across {nearby}. We check access, space and power before the day, so everything is set up, staked down and safety-checked before your guests arrive.</p><h2>What\'s included</h2><ul><li>Delivery to {city}, set-up and collection</li><li>Electric blower, stakes or sandbags and safety mats</li><li>PIPA-tested inflatables and public liability insurance</li><li>Space and power requirements with your quote</li><li>Optional operators and staff for the day</li></ul>'],
        'town.faq' => ['Town pages', 'FAQs (one per line: Question|Answer)', 'list',
            "Do you deliver inflatables to {city}?|Yes. We deliver, install and collect inflatables throughout {city} and {region}, including {nearby}.\nHow much is {label} hire in {city}?|Prices start from {price} + VAT for a one-day hire. Multi-day hires are discounted, and delivery to {city} is quoted with your hire.\nWhich {city} venues do you supply?|We supply schools, sports clubs, parks, offices, hotels and private gardens across {city}, as well as event venues such as {venues}."],

        // ---------------- Contact page
        'contact.title' => ['Contact page', 'SEO title', 'line', 'Get a Quote | JM Inflatable Hire'],
        'contact.meta' => ['Contact page', 'Meta description', 'text', 'Request a quote for bouncy castle, assault course, inflatable game, Wipeout or rodeo bull hire. Fast replies from our events team.'],
        'contact.h1' => ['Contact page', 'Main heading', 'line', 'Get an inflatable hire quote'],
        'contact.intro' => ['Contact page', 'Intro', 'html', '<p>Tell us about your event and we\'ll come back with availability, a fixed price and the space and power needed for each inflatable.</p>'],
        'contact.details' => ['Contact page', 'Contact details (one per line: Label|Value). Use {email}, {phone}, {address}, {hours} for the values from Settings', 'list',
            "Email|{email}\nPhone|{phone}\nAddress|{address}\nOpening hours|{hours}\nCoverage|UK-wide delivery, set-up & collection"],
        'contact.image' => ['Contact page', 'Photo under the contact details (optional)', 'image', ''],
        'contact.l_name' => ['Contact page', 'Form label – name', 'line', 'Name*'],
        'contact.l_company' => ['Contact page', 'Form label – company', 'line', 'Company'],
        'contact.l_email' => ['Contact page', 'Form label – email', 'line', 'Email*'],
        'contact.l_phone' => ['Contact page', 'Form label – phone', 'line', 'Phone'],
        'contact.l_date' => ['Contact page', 'Form label – event date', 'line', 'Event date'],
        'contact.l_venue' => ['Contact page', 'Form label – venue', 'line', 'Event postcode / venue'],
        'contact.l_product' => ['Contact page', 'Form label – product', 'line', 'Inflatable'],
        'contact.l_notsure' => ['Contact page', 'Product list – first option', 'line', 'Not sure – please advise'],
        'contact.l_qty' => ['Contact page', 'Form label – quantity', 'line', 'Quantity'],
        'contact.l_days' => ['Contact page', 'Form label – days', 'line', 'Days'],
        'contact.l_staff' => ['Contact page', 'Tick box', 'line', 'I\'d like staff / operators for the day'],
        'contact.l_message' => ['Contact page', 'Form label – message', 'line', 'Message'],
        'contact.button' => ['Contact page', 'Send button', 'line', 'Send enquiry'],
        'contact.privacy' => ['Contact page', 'Small print under the button', 'text', 'We\'ll only use your details to reply to your enquiry.'],
        'thanks.h1' => ['Contact page', 'Thank-you page heading', 'line', 'Thanks – we\'ve got your enquiry'],
        'thanks.text' => ['Contact page', 'Thank-you page text', 'text', 'Our team will be in touch shortly with availability and a quote.'],
        'thanks.btn' => ['Contact page', 'Thank-you page button', 'line', 'Back to home'],

        // ---------------- Availability checker & page
        'avail.title' => ['Availability', 'Page SEO title', 'line', 'Inflatable Availability Checker | Live Stock | JM Inflatable Hire'],
        'avail.meta' => ['Availability', 'Page meta description', 'text', 'Check live availability of our bouncy castles, slides, assault courses, inflatable games and rodeo bulls for your event date. Real-time stock from our booking system.'],
        'avail.h1' => ['Availability', 'Page heading', 'line', 'Check inflatable availability'],
        'avail.intro' => ['Availability', 'Page intro', 'html', '<p class="lead">Pick an inflatable, your event date and how many days you need. Availability comes live from our booking system – the same stock our team books from.</p><p>Bookings are first come, first served, so if your date shows available we\'d recommend requesting your quote straight away.</p>'],
        'checker.heading' => ['Availability', 'Checker box heading', 'line', 'Check live availability'],
        'checker.sub' => ['Availability', 'Checker box sub-line', 'line', 'Live stock from our booking system.'],
        'checker.button' => ['Availability', 'Checker button', 'line', 'Check availability'],

        // ---------------- Other pages
        'gallery.title' => ['Gallery, FAQ & areas pages', 'Gallery – SEO title', 'line', 'Inflatable Hire Gallery | Bouncy Castles, Assault Courses & More'],
        'gallery.meta' => ['Gallery, FAQ & areas pages', 'Gallery – meta description', 'text', 'Photos of our bouncy castles, slides, assault courses, inflatable games and rodeo bulls at parties, fun days and corporate events.'],
        'gallery.h1' => ['Gallery, FAQ & areas pages', 'Gallery – heading', 'line', 'Inflatable hire gallery'],
        'gallery.intro' => ['Gallery, FAQ & areas pages', 'Gallery – intro', 'text', 'Our inflatables at parties, fun days, schools and corporate events.'],
        'faq.title' => ['Gallery, FAQ & areas pages', 'FAQ – SEO title', 'line', 'Inflatable Hire FAQs | Prices, Space, Power & Safety'],
        'faq.meta' => ['Gallery, FAQ & areas pages', 'FAQ – meta description', 'text', 'Answers to common questions about inflatable and bouncy castle hire: prices, space and power needed, delivery, weather, safety and insurance.'],
        'faq.h1' => ['Gallery, FAQ & areas pages', 'FAQ – heading', 'line', 'Inflatable hire FAQs'],
        'faq.sub' => ['Gallery, FAQ & areas pages', 'FAQ – sub-heading', 'line', 'Your questions answered'],
        'areas.title' => ['Gallery, FAQ & areas pages', 'Areas – SEO title', 'line', 'Inflatable Hire Near You | UK Areas Covered | JM'],
        'areas.meta' => ['Gallery, FAQ & areas pages', 'Areas – meta description', 'text', 'We deliver bouncy castles, assault courses, inflatable games and rodeo bulls across the UK – London, Manchester, Birmingham, Leeds, Glasgow & more.'],
        'areas.h1' => ['Gallery, FAQ & areas pages', 'Areas – heading', 'line', 'Inflatable hire areas'],
        'areas.intro' => ['Gallery, FAQ & areas pages', 'Areas – intro', 'text', 'Our crews deliver, set up and collect across England, Scotland, Wales and Northern Ireland. Choose your area:'],
        'notfound.text' => ['Gallery, FAQ & areas pages', 'Page-not-found message', 'html', '<p>Try our <a href="/inflatable-hire">full inflatable range</a> or <a href="/contact">get in touch</a>.</p>'],
    ];
    return $f;
}

/** Raw text value (admin value, or the default). */
function t(string $key): string {
    $v = data()['text'][$key] ?? null;
    if ($v === null || $v === '') return (string)(text_fields()[$key][3] ?? '');
    return (string)$v;
}

/** Text with {placeholders} filled in. Values are inserted as-is; escape at output. */
function tf(string $key, array $vars = []): string {
    $s = t($key);
    $vars += ['site' => s('site_name'), 'email' => s('email'), 'phone' => s('phone'), 'address' => s('address'), 'hours' => s('opening_hours')];
    return strtr($s, array_combine(array_map(fn($k) => '{' . $k . '}', array_keys($vars)), array_map('strval', array_values($vars))));
}

/** List value as an array of lines; with $cols=true each line is split on "|". */
function tl(string $key, array $vars = [], bool $cols = false): array {
    $out = [];
    foreach (preg_split('/\R/', tf($key, $vars)) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $out[] = $cols ? array_map('trim', explode('|', $line, 2)) + [1 => ''] : $line;
    }
    return $out;
}

/** HTML text with placeholders, where placeholder values are escaped. */
function th(string $key, array $vars = []): string {
    $vars += ['site' => s('site_name'), 'email' => s('email'), 'phone' => s('phone'), 'address' => s('address'), 'hours' => s('opening_hours')];
    return tf($key, array_map(fn($v) => e((string)$v), $vars));
}
