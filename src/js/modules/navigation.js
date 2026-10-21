import { focusTarget, listen, query } from '../runtime.js';
export function init(runtime) {
  const offs = [];
  const button = runtime.config.modules.back_to_top ? query('[data-hc-back-to-top]') : null;
  const progress = button?.querySelector('[data-hc-progress]');
  const cta = runtime.config.modules.sticky_cta ? query('[data-hc-sticky-cta]') : null;
  const toggle = cta?.querySelector('[data-hc-cta-toggle]');
  const panel = cta?.querySelector('[data-hc-cta-panel]');
  const desktop = matchMedia('(min-width: 768px)');
  let length = 0;
  try { length = progress?.getTotalLength() || 0; } catch { /* Invalid/empty SVG must not disable navigation. */ }
  if (progress) progress.style.strokeDasharray = length;
  const close = (restore = false) => {
    if (!panel || !toggle) return;
    const hadFocus = panel.contains(document.activeElement);
    cta.classList.remove('is-open'); toggle.setAttribute('aria-expanded', 'false');
    panel.inert = true; panel.hidden = true; panel.setAttribute('aria-hidden', 'true');
    if (restore || hadFocus) toggle.focus({ preventScroll: true });
  };
  function sync() {
    if (button) {
      const visible = desktop.matches && scrollY > 240 && !runtime.footerVisible && runtime.preloaderDone;
      button.hidden = !visible; button.disabled = !visible; button.classList.toggle('is-visible', visible);
      button.setAttribute('aria-hidden', String(!visible));
      if (progress) progress.style.strokeDashoffset = length * (1 - Math.min(1, Math.max(0, scrollY / Math.max(1, document.documentElement.scrollHeight - innerHeight))));
    }
    if (cta && toggle && panel) {
      const visible = runtime.passedHero && !runtime.footerVisible && runtime.preloaderDone;
      if (!visible) {
        const focused = cta.contains(document.activeElement);
        close();
        if (focused) focusTarget(query(runtime.config.selectors.footer) || document.querySelector('main') || document.body);
      }
      cta.hidden = !visible; cta.inert = !visible; cta.classList.toggle('is-visible', visible);
    }
  }
  if (button) offs.push(listen(button, 'click', () => {
    runtime.scrollTo(0, { duration: 1, onComplete: () => focusTarget(query('main') || document.body) });
    if (!runtime.lenis) focusTarget(query('main') || document.body);
  }));
  if (toggle && panel) {
    close();
    offs.push(listen(toggle, 'click', () => {
      const opening = toggle.getAttribute('aria-expanded') !== 'true';
      if (!opening) { close(); return; }
      panel.hidden = false; panel.inert = false; panel.setAttribute('aria-hidden', 'false');
      cta.classList.add('is-open'); toggle.setAttribute('aria-expanded', 'true');
      panel.querySelector('a,button')?.focus({ preventScroll: true });
    }), listen(document, 'click', event => { if (!cta.contains(event.target)) close(); }), listen(document, 'keydown', event => { if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') { event.preventDefault(); close(true); } }));
  }
  const unsubscribe = runtime.subscribe(sync);
  offs.push(listen(desktop, 'change', sync));
  return () => { unsubscribe(); offs.forEach(fn => fn()); close(); if (button) { button.hidden = true; button.disabled = true; } if (cta) { cta.hidden = true; cta.inert = true; } };
}
