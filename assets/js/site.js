(() => {
  const btn = document.querySelector('.menu-btn'), nav = document.getElementById('nav');
  btn?.addEventListener('click', () => { const o = nav.classList.toggle('open'); btn.setAttribute('aria-expanded', o); });

  const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  document.querySelectorAll('[data-checker]').forEach(form => {
    const out = form.querySelector('.checker-result');
    form.addEventListener('submit', async ev => {
      ev.preventDefault();
      const fd = new FormData(form);
      if (!fd.get('start')) { out.innerHTML = '<div class="res unk">Please choose your event date.</div>'; return; }
      const b = form.querySelector('button'); b.disabled = true; b.textContent = 'Checking…';
      try {
        const r = await fetch('/api/availability?' + new URLSearchParams(fd), {headers: {Accept: 'application/json'}});
        const j = await r.json();
        if (!r.ok) throw new Error(j.error || 'Something went wrong');
        const cls = {available: 'ok', limited: 'lim', unavailable: 'no'}[j.status] || 'unk';
        const head = {
          available: `Available – ${j.available_qty} free on your date`,
          limited: `Only ${j.available_qty} available on your date`,
          unavailable: 'Fully booked on your date',
        }[j.status] || "We'll confirm availability for you";
        const sub = j.status === 'unavailable'
          ? 'Try another date or inflatable – or ask us about alternatives.'
          : `Estimated hire: <strong>${esc(j.estimate_text)}</strong> incl. delivery, set-up &amp; collection on most UK bookings.`;
        out.innerHTML = `<div class="res ${cls}"><b>${esc(head)}</b>${sub}<a class="btn btn-sm btn-block" href="${esc(j.quote_url)}">${j.status === 'unavailable' ? 'Ask about alternatives' : 'Request this quote'}</a></div>`;
        if (window.gtag) gtag('event', 'availability_check', {item: j.product, status: j.status});
      } catch (e) {
        out.innerHTML = `<div class="res unk">${esc(e.message)}</div>`;
      } finally { b.disabled = false; b.textContent = 'Check availability'; }
    });
  });
})();
