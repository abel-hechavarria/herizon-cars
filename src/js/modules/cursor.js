import { listen, query } from '../runtime.js';
export function init(runtime) {
  const cursor = query('[data-hc-cursor]');
  if (!cursor) return () => {};
  const mq = matchMedia('(hover: hover) and (pointer: fine) and (min-width: 768px)');
  const moved = new Map();
  let off = [], raf = 0, x = 0, y = 0;
  const reset = () => {
    off.forEach(fn => fn()); off = []; cancelAnimationFrame(raf); raf = 0;
    cursor.classList.remove('is-visible', 'is-active'); cursor.hidden = true;
    moved.forEach((value, el) => { el.style.transform = value; }); moved.clear();
  };
  const move = event => {
    x = event.clientX; y = event.clientY;
    if (raf) return;
    raf = requestAnimationFrame(() => { raf = 0; cursor.style.translate = x + 'px ' + y + 'px'; cursor.classList.add('is-visible'); });
    const interactive = event.target.closest?.('a[href], button, input, select, textarea, summary, [role="button"], .hc-cursor-pointer');
    cursor.classList.toggle('is-active', Boolean(interactive));
    const magnet = event.target.closest?.('.hc-cursor-magnetic');
    const target = magnet?.querySelector('.hc-cursor-magnetic__target');
    moved.forEach((value, el) => { if (el !== target) { el.style.transform = value; moved.delete(el); } });
    if (!target) return;
    if (!moved.has(target)) moved.set(target, target.style.transform);
    const rect = magnet.getBoundingClientRect();
    if (rect.width && rect.height) target.style.transform = 'translate(' + ((x - rect.left) / rect.width * 50 - 25) + 'px,' + ((y - rect.top) / rect.height * 50 - 25) + 'px)';
  };
  const sync = () => {
    reset();
    if (!mq.matches || runtime.reduced.matches || runtime.editor() || !runtime.preloaderDone) return;
    cursor.hidden = false;
    off = [listen(window, 'pointermove', move, { passive: true }), listen(document.documentElement, 'pointerleave', () => { cursor.classList.remove('is-visible'); moved.forEach((value, el) => { el.style.transform = value; }); moved.clear(); }), listen(window, 'blur', reset)];
  };
  let last;
  const unsubscribe = runtime.subscribe(syncIfChanged);
  function syncIfChanged() {
    const state = [mq.matches, runtime.reduced.matches, runtime.preloaderDone, runtime.editor()].join(':');
    if (state !== last) { last = state; sync(); }
  }
  const unmedia = listen(mq, 'change', syncIfChanged);
  const unfocus = listen(window, 'focus', sync);
  return () => { unsubscribe(); unmedia(); unfocus(); reset(); };
}
