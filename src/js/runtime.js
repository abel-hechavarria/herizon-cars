/** Shared controller. Adapted from Abel's global effects; independent of the CTA. */
export function isEditor() {
  return document.body.classList.contains('elementor-editor-active') || new URL(location.href).searchParams.has('elementor-preview') || Boolean(window.elementorFrontend?.isEditMode?.());
}
export function query(selector, scope = document) {
  try { return selector ? scope.querySelector(selector) : null; } catch { return null; }
}
export function queries(selector, scope = document) {
  try { return [...scope.querySelectorAll(selector)]; } catch { return []; }
}
export function listen(target, name, handler, options) {
  target.addEventListener(name, handler, options);
  return () => target.removeEventListener(name, handler, options);
}
export function media(queryString, callback) {
  const mq = matchMedia(queryString);
  return { mq, off: listen(mq, 'change', callback) };
}
export function focusTarget(target) {
  const hadTabindex = target.hasAttribute('tabindex');
  if (!hadTabindex) target.setAttribute('tabindex', '-1');
  target.focus({ preventScroll: true });
  if (!hadTabindex) target.addEventListener('blur', () => target.removeAttribute('tabindex'), { once: true });
}
export function samePageAnchor(href, current = location.href) {
  try {
    const url = new URL(href, current);
    const here = new URL(current);
    return url.origin === here.origin && url.pathname === here.pathname && url.search === here.search && url.hash.length > 1 ? url : null;
  } catch { return null; }
}
export function createRuntime(config) {
  const listeners = new Set();
  const cleanups = [];
  let raf = 0;
  let disposed = false;
  const runtime = {
    config, lenis: null, preloaderDone: !config.modules.preloader,
    footerVisible: false, passedHero: false,
    reduced: matchMedia('(prefers-reduced-motion: reduce)'),
    editor: isEditor,
    subscribe(fn) { listeners.add(fn); fn(runtime); return () => listeners.delete(fn); },
    publish() { if (!disposed) listeners.forEach(fn => fn(runtime)); },
    finishPreloader() {
      if (runtime.preloaderDone) return;
      runtime.preloaderDone = true;
      document.body.classList.remove('hc-is-loading');
      window.dispatchEvent(new CustomEvent('herizon:preloader:done'));
      runtime.publish();
    },
    scrollTo(target, options = {}) {
      if (runtime.lenis && !runtime.reduced.matches) runtime.lenis.scrollTo(target, options);
      else window.scrollTo({ top: typeof target === 'number' ? target : target.getBoundingClientRect().top + window.scrollY + (options.offset || 0), behavior: runtime.reduced.matches ? 'auto' : 'smooth' });
    },
    destroy() {
      disposed = true; cancelAnimationFrame(raf);
      cleanups.reverse().forEach(fn => fn()); listeners.clear();
      document.body.classList.remove('hc-footer-in-view', 'hc-is-loading');
    },
  };
  function update() {
    raf = 0;
    const footer = query(config.selectors.footer);
    const hero = query(config.selectors.hero);
    const bounds = footer?.getBoundingClientRect();
    runtime.footerVisible = Boolean(bounds && bounds.top < innerHeight && bounds.bottom > 0);
    runtime.passedHero = hero ? hero.getBoundingClientRect().bottom < innerHeight * .18 : scrollY > innerHeight * .72;
    document.body.classList.toggle('hc-footer-in-view', runtime.footerVisible);
    runtime.publish();
  }
  const schedule = () => { if (!raf && !disposed) raf = requestAnimationFrame(update); };
  cleanups.push(listen(window, 'scroll', schedule, { passive: true }), listen(window, 'resize', schedule), listen(runtime.reduced, 'change', () => runtime.publish()));
  if (typeof ResizeObserver !== 'undefined') { const observer = new ResizeObserver(schedule); observer.observe(document.body); cleanups.push(() => observer.disconnect()); }
  update();
  return runtime;
}
