import '../scss/main.scss';
import { createRuntime, isEditor } from './runtime.js';

let instance;
let cleanups = [];
let generation = 0;
export function destroy() {
  generation++;
  cleanups.reverse().forEach(fn => fn()); cleanups = [];
  instance?.destroy(); instance = null;
}
export function init(config = window.herizonCarsConfig || {}) {
  destroy();
  if (isEditor()) return;
  const modules = config.modules || {};
  const runtime = createRuntime({ ...config, modules, selectors: { hero: '.hc-hero', footer: '.hc-footer, footer.site-footer, .elementor-location-footer', header: '.hc-sticky-header', ...config.selectors }, anchorOffset: Number(config.anchorOffset) || 0 });
  instance = runtime;
  const token = generation;
  async function load(enabled, importer) {
    if (!enabled) return;
    try { const module = await importer(); if (token === generation) cleanups.push(module.init(runtime)); }
    catch (error) { console.warn('[Herizon Cars] Optional effect unavailable.', error); }
  }
  // Start watchdog synchronously; an unavailable dynamic chunk must never lock the page.
  if (modules.preloader) {
    const watchdog = setTimeout(() => { const overlay = document.querySelector('[data-hc-preloader]'); if (overlay) { overlay.hidden = true; overlay.inert = true; } runtime.finishPreloader(); }, 3400);
    cleanups.push(() => clearTimeout(watchdog));
  }
  load(modules.preloader, () => import('./modules/preloader.js'));
  load(modules.cursor, () => import('./modules/cursor.js'));
  load(modules.smooth_scroll, () => import('./modules/smooth-scroll.js'));
  load(modules.back_to_top || modules.sticky_cta, () => import('./modules/navigation.js'));
  return runtime;
}
window.herizonCars = { init, destroy, get runtime() { return instance; } };
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => init(), { once: true }); else init();
window.addEventListener('pagehide', destroy);
window.addEventListener('pageshow', event => { if (event.persisted) init({ ...window.herizonCarsConfig, modules: { ...window.herizonCarsConfig?.modules, preloader: false } }); });
