import '../scss/widgets.scss';
import { listen, queries } from './runtime.js';
const roots = new Map();
let observer, frame = 0, hooked = false;
let jqueryBound = false;

function initMarquee(root) {
  const button = root.querySelector('[data-hc-marquee-pause]');
  const groups = queries('.hc-marquee__group', root);
  const duplicates = groups.slice(1);
  duplicates.forEach(group => { group.setAttribute('data-hc-marquee-duplicate', ''); group.setAttribute('aria-hidden', 'true'); group.inert = true; queries('a,button,input,select,textarea,[tabindex]', group).forEach(el => el.setAttribute('tabindex', '-1')); });
  const reduced = matchMedia('(prefers-reduced-motion: reduce)');
  let paused = false;
  const sync = () => {
    root.classList.add('hc-is-enhanced');
    root.classList.toggle('is-paused', paused || reduced.matches);
    if (button) {
      button.hidden = reduced.matches || groups.length < 2;
      button.setAttribute('aria-pressed', String(paused));
      button.textContent = paused ? (button.dataset.labelPlay || 'Play') : (button.dataset.labelPause || 'Pause');
    }
  };
  const off = [listen(reduced, 'change', sync)];
  if (button) off.push(listen(button, 'click', () => { paused = !paused; sync(); }));
  // Width remains correct for short groups and for controls changing in Elementor.
  const resize = () => root.style.setProperty('--hc-marquee-min-width', root.clientWidth + 'px');
  const size = typeof ResizeObserver === 'undefined' ? null : new ResizeObserver(resize);
  size?.observe(root); resize(); sync();
  return () => { off.forEach(fn => fn()); size?.disconnect(); root.classList.remove('hc-is-enhanced', 'is-paused'); root.style.removeProperty('--hc-marquee-min-width'); };
}
function initGallery(root) {
  const items = queries('.hc-gallery__item', root);
  const buttons = items.map(item => item.querySelector('[data-hc-gallery-trigger]'));
  const tablet = matchMedia('(max-width: 1024px)');
  const mobile = matchMedia('(max-width: 767px)');
  let active = 0;
  const offs = [];
  const limit = () => {
    const raw = mobile.matches ? root.dataset.limitMobile : tablet.matches ? root.dataset.limitTablet : root.dataset.limitDesktop;
    return Math.min(items.length, Math.max(1, Number.parseInt(raw, 10) || items.length));
  };
  const sync = () => {
    const count = limit();
    if (active >= count) active = 0;
    items.forEach((item, index) => {
      const hidden = index >= count;
      if (hidden && item.contains(document.activeElement)) buttons[active]?.focus({ preventScroll: true });
      item.hidden = hidden;
      item.classList.toggle('is-active', index === active);
      buttons[index]?.setAttribute('aria-expanded', String(index === active));
    });
  };
  const activate = index => { active = index; sync(); };
  buttons.forEach((button, index) => {
    if (!button) return;
    offs.push(listen(button, 'click', () => activate(index)), listen(button, 'focus', () => activate(index)), listen(button, 'pointerenter', event => { if (event.pointerType === 'mouse') activate(index); }), listen(button, 'keydown', event => {
      if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
      event.preventDefault();
      const count = limit();
      const next = event.key === 'Home' ? 0 : event.key === 'End' ? count - 1 : (index + (event.key === 'ArrowRight' ? 1 : -1) + count) % count;
      buttons[next]?.focus();
    }));
  });
  root.classList.add('hc-is-enhanced');
  offs.push(listen(tablet, 'change', sync), listen(mobile, 'change', sync)); sync();
  return () => { offs.forEach(fn => fn()); root.classList.remove('hc-is-enhanced'); items.forEach(item => { item.hidden = false; item.classList.remove('is-active'); }); };
}
export function initWidgets(scope = document) {
  // Old Elementor widget roots are disposed before enhancing the new render.
  for (const [root, cleanup] of roots) { if (!root.isConnected) { cleanup(); roots.delete(root); } }
  const candidates = [...queries('[data-hc-logo-marquee], [data-hc-text-marquee], [data-hc-gallery]', scope)];
  if (scope.matches?.('[data-hc-logo-marquee], [data-hc-text-marquee], [data-hc-gallery]')) candidates.unshift(scope);
  candidates.forEach(root => { roots.get(root)?.(); roots.set(root, root.hasAttribute('data-hc-gallery') ? initGallery(root) : initMarquee(root)); });
}
const onElementorReady = scope => initWidgets(scope?.[0] || scope || document);
function hookElementor() {
  if (hooked || !window.elementorFrontend?.hooks) return;
  hooked = true;
  window.elementorFrontend.hooks.addAction('frontend/element_ready/global', onElementorReady);
  initWidgets();
}
export function destroyWidgets() {
  observer?.disconnect(); observer = null; cancelAnimationFrame(frame); frame = 0;
  roots.forEach(cleanup => cleanup()); roots.clear();
  if (hooked) window.elementorFrontend?.hooks?.removeAction('frontend/element_ready/global', onElementorReady);
  hooked = false;
  if (jqueryBound) window.jQuery(window).off('elementor/frontend/init.herizonCars', hookElementor);
  jqueryBound = false;
}
export function bootWidgets() {
  initWidgets(); hookElementor();
  if (window.jQuery && !jqueryBound) { window.jQuery(window).on('elementor/frontend/init.herizonCars', hookElementor); jqueryBound = true; }
  if (!observer) {
    observer = new MutationObserver(records => {
      if (!records.some(record => record.addedNodes.length || record.removedNodes.length) || frame) return;
      frame = requestAnimationFrame(() => {
        frame = 0;
        for (const [root, cleanup] of roots) if (!root.isConnected) { cleanup(); roots.delete(root); }
        queries('[data-hc-logo-marquee], [data-hc-text-marquee], [data-hc-gallery]').forEach(root => { if (!roots.has(root)) roots.set(root, root.hasAttribute('data-hc-gallery') ? initGallery(root) : initMarquee(root)); });
      });
    });
    observer.observe(document.body, { childList: true, subtree: true });
  }
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bootWidgets, { once: true }); else bootWidgets();
window.addEventListener('elementor/frontend/init', hookElementor);
window.addEventListener('pagehide', destroyWidgets);
window.addEventListener('pageshow', event => { if (event.persisted) bootWidgets(); });
