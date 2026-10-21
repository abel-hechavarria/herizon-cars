import Lenis from 'lenis';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { focusTarget, listen, query, samePageAnchor } from '../runtime.js';
gsap.registerPlugin(ScrollTrigger);
export function init(runtime) {
  const desktop = matchMedia('(min-width: 1025px)');
  let lenis, tick;
  const stop = () => { if (tick) gsap.ticker.remove(tick); tick = null; lenis?.destroy(); lenis = null; runtime.lenis = null; };
  const sync = () => {
    const allowed = desktop.matches && !runtime.reduced.matches && runtime.preloaderDone && !runtime.editor() && !query('.hc-horizontal-section, [data-hc-horizontal], .ahj-horizontal-projects');
    if (!allowed) { stop(); return; }
    if (lenis) return;
    lenis = new Lenis({ duration: 1.05, smoothWheel: true, syncTouch: false });
    runtime.lenis = lenis;
    lenis.on('scroll', ScrollTrigger.update);
    tick = time => lenis?.raf(time * 1000);
    gsap.ticker.add(tick);
    ScrollTrigger.refresh();
  };
  const anchor = event => {
    if (!lenis || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.shiftKey || event.altKey || event.metaKey) return;
    const link = event.target.closest?.('a[href]');
    if (!link || link.hasAttribute('download') || (link.target && link.target !== '_self')) return;
    const url = samePageAnchor(link.getAttribute('href'));
    if (!url) return;
    let target;
    try { target = document.getElementById(decodeURIComponent(url.hash.slice(1))); } catch { return; }
    if (!target) return;
    event.preventDefault();
    const header = query(runtime.config.selectors.header);
    const offset = -(runtime.config.anchorOffset + (header?.getBoundingClientRect().height || 0));
    if (location.hash !== url.hash) history.pushState(null, '', url.hash);
    lenis.scrollTo(target, { offset, onComplete: () => focusTarget(target) });
  };
  const unsubscribe = runtime.subscribe(sync);
  const offs = [listen(desktop, 'change', sync), listen(document, 'click', anchor), listen(window, 'load', () => ScrollTrigger.refresh())];
  return () => { unsubscribe(); offs.forEach(fn => fn()); stop(); };
}
