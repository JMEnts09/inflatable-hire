# JM Inflatable Hire – website

PHP site (no database) built on the same theme and CMS as the darts, sim, crane and basketball hire sites. Content lives in `data/content.json` and is edited from `/admin`.

## Admin panel (/admin)
Everything on the site can be edited from your phone or computer without touching code:
- **Pages** – home & category pages: SEO title/description with Google preview, heading, intro, main photo, text sections (rich text editor), FAQs, town-page settings.
- **Products** – search, add, edit, hide or delete; name, web address, categories, 1/2/3/4/7-day prices, short & full description, main photo + extra photos (upload straight from your phone), spec, SEO title/description.
- **Site text** – every other word on the site: menu, logo, buttons, headings, hiring steps, footer, product/town page wording, availability, gallery, FAQ and areas pages.
- **Contact page** – contact details (email, phone, address, opening hours, where enquiries go), intro, form labels, button and thank-you page.
- **SEO** – every page and product title/description in one list with length counters, live site address, social share image, Google Analytics & Search Console, hide-from-Google switch.
- **Photos** – upload several at once (auto-resized to fast WebP), search, delete. Any photo field can pick from here or upload new.
- **Gallery, Towns, Settings, CRM & stock, Enquiries, Account** (password; 2-step sign-in with an authenticator app).

## Upload to IONOS
1. Upload everything in this folder to the domain's web root (keep the hidden `.htaccess` files).
2. Make `data/`, `data/cache/` and `assets/img/uploads/` writable (755 or 775).
3. Visit `/admin` straight away and set up your login: password + authenticator app (2-step).
4. Admin → Settings: set **Base URL** to the live domain, phone number, GA4 ID and Search Console code.
5. Admin → CRM & stock: paste the CRM API key, check the endpoint paths, press **Sync now**.
6. Submit `https://YOURDOMAIN/sitemap.xml` in Google Search Console.

Requires PHP 8.1+ with curl and GD (IONOS defaults are fine). Pushing to `main` on GitHub deploys over SFTP to the `SFTP_PATH` folder (default `/Inflatables`) – set `SFTP_HOST`, `SFTP_USER`, `SFTP_PASSWORD` and `SFTP_PATH` as repo secrets.

## Stock
All 79 inflatables come from the JM CRM (`jm-crm` products), matched by **CRM ID** on the Products tab, with 1/2/3/7-day prices from the CRM (4-day price follows the standard rule). Site categories:

| Category key | Page | CRM groups |
|---|---|---|
| `bouncy-castles` | /bouncy-castle-hire | Bouncy Castles & Slides, toddler inflatable |
| `slides` | /inflatable-slide-hire | Slides + bounce & slide combis |
| `assault-courses` | /assault-course-hire | Assault Courses |
| `games` | /inflatable-games-hire | Action Inflatables (games), sumo suits, Dash N Grab, curling, snow globe, 3 in 1 Sports |
| `challenges` | /inflatable-challenge-hire | Wipeout, Bungee Runs, Gladiators, wrecking ball, climbing wall, maze etc. |
| `rodeo` | /rodeo-bull-hire | Rodeo Bull Hire, surf & snowboard simulators (operator included) |

`/inflatable-hire` lists everything. New categories can be added as pages in `content.json` (any page with a `cat` becomes a category; `"local": true` also creates town pages).

## CRM connection (crm.jments.co.uk)
Same contract as the other hire sites (Bearer token auth):

| Purpose | Request | Response |
|---|---|---|
| Products | `GET /api/public/products?category=inflatables` | `{"products":[{"id","slug","name","price_from","prices":{"1":595,"2":935},"stock_qty","staffed","images":[url],"spec":{"dimensions","weight","power","players"},"spec_sheet_url","category"}]}` |
| Availability | `GET /api/public/availability?product=ID&start=YYYY-MM-DD&days=N&qty=N` | `{"available_qty":2,"total_qty":3,"status":"available|limited|unavailable"}` |
| Enquiries | `POST /api/public/enquiries` (JSON) | `{"ok":true,"reference":"ENQ-1234"}` |

Inflatable stock in the CRM that isn't on the site yet is added automatically and sorted into a category by name. If the CRM is down the site keeps working from the last good copy.

## Pages
- `/` home · `/inflatable-hire` · `/bouncy-castle-hire` · `/inflatable-slide-hire` · `/assault-course-hire` · `/inflatable-games-hire` · `/inflatable-challenge-hire` · `/rodeo-bull-hire`
- `/hire/{product}` – one page per inflatable with prices, specs and live checker
- `/{category}/{town}` – 40 towns × 5 categories = 200 local pages (edit in Admin → Locations)
- `/availability` · `/gallery` · `/faq` · `/contact` · `/sitemap.xml` · `/robots.txt`

## Images
76 of the 79 inflatables use their real product photo (first photo from the JM product photos folder in Google Drive), saved as responsive WebP files (`assets/img/{product}-hire.webp`, `-800`, `-480`). Category pages, the home page and the gallery use real photos too. The 3 Sports Inflatable Game, Rodeo Pig and Rodeo Football still use the branded illustrations – swap them in Admin → Products once photos are uploaded via Admin → Gallery. CRM product images, when added, are used automatically ("Prefer CRM images").
