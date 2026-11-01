/**
 * JM CRM public feed – read-only.
 * A separate Cloudflare Worker bound to the jm-crm D1 database (binding name: DB).
 * Gives the hire websites live prices, specs and availability. It only ever runs SELECT queries
 * and only returns public product facts (no customers, costs, notes or money totals).
 *
 *   GET /products?category=Arcade%20Basketball      products in a CRM category (or ?ids=90038,90045)
 *   GET /availability?product=90038&start=2026-12-05&days=2&qty=1
 *   GET /products?category=Rodeo%20Bull%20Hire|Assault%20Courses   several categories at once (separate with |)
 *   GET /version                                     latest product change time (cheap "has anything changed?" check)
 *   GET /img/123                                     a product photo from the CRM (only photos of kind "image" on visible products)
 *
 * Each product also lists "photos": full URLs of its CRM photos in the CRM's order. ("photos" rather than "images" so
 * sites built before photos were available keep their own pictures until they opt in.)
 */
const COLS = `p.id, p.name, p.hidden, p.online, p.qty, p.price, p.categories, p.desk_id, p.updated_at,
  d.size, d.weight, d.users, d.price_2d, d.price_3d, d.price_4d, d.price_7d, d.brandable, d.brand_desc,
  s.power, s.setup_time, s.description, d.updated_at AS d_updated, s.updated_at AS s_updated`;
const FROM = `FROM products p LEFT JOIN product_details d ON d.product_id = p.id LEFT JOIN product_specs s ON s.product_id = p.id`;

const json = (body, status = 200, maxAge = 30) => new Response(JSON.stringify(body), {
  status,
  headers: {
    'content-type': 'application/json; charset=utf-8',
    'cache-control': `public, max-age=${maxAge}`,
    'access-control-allow-origin': '*',
    'x-content-type-options': 'nosniff',
  },
});

const shape = (r) => ({
  id: String(r.id),
  name: r.name,
  online: !!r.online,
  categories: r.categories || '',
  price: r.price || 0,
  prices: { 1: r.price || null, 2: r.price_2d, 3: r.price_3d, 4: r.price_4d, 7: r.price_7d },
  qty: r.qty || 0,
  size: r.size || '',
  weight: r.weight || '',
  power: r.power || '',
  users: r.users || '',
  setup_time: r.setup_time || '',
  description: r.description || '',
  brandable: !!r.brandable,
  updated_at: [r.updated_at, r.d_updated, r.s_updated].filter(Boolean).sort().pop() || '',
});

export default {
  async fetch(request, env) {
    if (request.method === 'OPTIONS') return json({}, 204, 86400);
    if (request.method !== 'GET') return json({ error: 'Read only' }, 405, 0);
    const url = new URL(request.url);
    const q = (k) => (url.searchParams.get(k) || '').trim();
    try {
      if (url.pathname === '/' || url.pathname === '/health') return json({ ok: true, service: 'jm-crm-feed' }, 200, 0);

      if (url.pathname === '/version') {
        const r = await env.DB.prepare(`SELECT MAX(x) AS v FROM (SELECT MAX(updated_at) AS x FROM products UNION ALL SELECT MAX(updated_at) FROM product_details UNION ALL SELECT MAX(updated_at) FROM product_specs UNION ALL SELECT MAX(updated_at) FROM bookings)`).first();
        return json({ version: r?.v || '' }, 200, 5);
      }

      if (url.pathname === '/products') {
        const ids = q('ids').split(',').map((x) => x.trim()).filter((x) => /^\d{1,12}$/.test(x)).slice(0, 300);
        const cats = q('category').split('|').map((x) => x.trim().slice(0, 80)).filter(Boolean).slice(0, 12);
        if (!ids.length && !cats.length) return json({ error: 'Pass ?category= or ?ids=' }, 400, 0);
        const seen = new Map();
        // ids in chunks (D1 allows up to 100 bound values per query)
        for (let i = 0; i < ids.length; i += 90) {
          const chunk = ids.slice(i, i + 90);
          const r = (await env.DB.prepare(`SELECT ${COLS} ${FROM} WHERE p.hidden = 0 AND p.id IN (${chunk.map(() => '?').join(',')})`).bind(...chunk).all()).results;
          r.forEach((row) => seen.set(row.id, row));
        }
        for (const cat of cats) {
          const r = (await env.DB.prepare(`SELECT ${COLS} ${FROM} WHERE p.hidden = 0 AND (',' || p.categories || ',') LIKE ? ORDER BY p.name`).bind(`%${cat}%`).all()).results;
          r.forEach((row) => seen.set(row.id, row));
        }
        const rows = [...seen.values()].sort((a, b) => String(a.name).localeCompare(String(b.name)));
        // photos: ids of each product's CRM photos, in CRM order
        const photos = new Map();
        const pids = rows.map((r) => r.id);
        for (let i = 0; i < pids.length; i += 90) {
          const chunk = pids.slice(i, i + 90);
          const r = (await env.DB.prepare(`SELECT id, product_id FROM product_images WHERE kind = 'image' AND product_id IN (${chunk.map(() => '?').join(',')}) ORDER BY id`).bind(...chunk).all()).results;
          r.forEach((x) => { if (!photos.has(x.product_id)) photos.set(x.product_id, []); photos.get(x.product_id).push(`${url.origin}/img/${x.id}`); });
        }
        return json({ products: rows.map((r) => ({ ...shape(r), photos: photos.get(r.id) || [] })), generated_at: new Date().toISOString() }, 200, 30);
      }

      const img = url.pathname.match(/^\/img\/(\d{1,12})$/);
      if (img) {
        const r = await env.DB.prepare(`SELECT i.mime, i.data FROM product_images i JOIN products p ON p.id = i.product_id WHERE i.id = ? AND i.kind = 'image' AND p.hidden = 0`).bind(img[1]).first();
        if (!r || !/^image\/(jpeg|png|webp)$/.test(r.mime)) return json({ error: 'Not found' }, 404, 300);
        const bin = Uint8Array.from(atob(r.data), (c) => c.charCodeAt(0));
        return new Response(bin, { headers: { 'content-type': r.mime, 'cache-control': 'public, max-age=604800', 'access-control-allow-origin': '*', 'x-content-type-options': 'nosniff' } });
      }

      if (url.pathname === '/availability') {
        const id = q('product'), start = q('start');
        const days = Math.min(30, Math.max(1, parseInt(q('days') || '1', 10) || 1));
        const qty = Math.min(50, Math.max(1, parseInt(q('qty') || '1', 10) || 1));
        if (!/^\d{1,12}$/.test(id) || !/^\d{4}-\d{2}-\d{2}$/.test(start)) return json({ error: 'Pass ?product=ID&start=YYYY-MM-DD' }, 400, 0);
        const end = new Date(Date.parse(start + 'T00:00:00Z') + (days - 1) * 86400000).toISOString().slice(0, 10);
        const p = await env.DB.prepare(`SELECT id, name, qty, desk_id FROM products WHERE id = ? AND hidden = 0`).bind(id).first();
        if (!p) return json({ error: 'Unknown product' }, 404, 0);
        // Machines already out on any day of the hire (bookings that overlap the dates; cancelled ones don't count).
        const booked = await env.DB.prepare(`SELECT COALESCE(SUM(i.qty), 0) AS n FROM booking_items i JOIN bookings b ON b.id = i.booking_id
            WHERE lower(b.status) NOT IN ('cancelled', 'canceled', 'lost', 'declined')
              AND b.start_date <> '' AND b.start_date <= ?1 AND (CASE WHEN b.end_date = '' THEN b.start_date ELSE b.end_date END) >= ?2
              AND (i.product_id = ?3 OR (?4 <> '' AND i.product_id = ?4) OR (i.product_id IS NULL AND lower(i.product_name) = lower(?5)))`)
          .bind(end, start, String(p.id), p.desk_id || '', p.name).first();
        const total = p.qty || 0, out = booked?.n || 0;
        if (total <= 0) return json({ status: 'unknown', total_qty: 0, booked_qty: out, start, end }, 200, 15);   // stock not counted in the CRM
        const free = Math.max(0, total - out);
        return json({ status: free >= qty ? 'available' : free > 0 ? 'limited' : 'unavailable', available_qty: free, total_qty: total, start, end }, 200, 15);
      }

      return json({ error: 'Not found' }, 404, 60);
    } catch (err) {
      return json({ error: 'Feed error' }, 500, 0);
    }
  },
};
