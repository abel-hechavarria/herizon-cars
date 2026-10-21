import test from 'node:test';
import assert from 'node:assert/strict';
import { createRuntime, samePageAnchor } from './runtime.js';
import { init as initCursor } from './modules/cursor.js';
import { init as initPreloader } from './modules/preloader.js';

test('anchor routing preserves page, origin and search; malformed URLs fall back', () => {
  const current = 'https://herizon.test/cars/?language=hu';
  assert.equal(samePageAnchor('#details', current)?.hash, '#details');
  assert.equal(samePageAnchor('/cars/?language=hu#details', current)?.hash, '#details');
  for (const href of ['/cars/?language=en#details', '/other/#details', 'https://other.test/cars/?language=hu#details', 'mailto:a@example.test', '#', 'http://[invalid']) assert.equal(samePageAnchor(href, current), null, href);
});

function fixture() {
  class Classes { values = new Set(); add(...names) { names.forEach(x => this.values.add(x)); } remove(...names) { names.forEach(x => this.values.delete(x)); } contains(name) { return this.values.has(name); } toggle(name, force) { if (force ?? !this.contains(name)) this.add(name); else this.remove(name); } }
  const body = { classList: new Classes(), getBoundingClientRect: () => ({ top: 0, bottom: 10000 }) };
  const cursor = { hidden: true, classList: new Classes(), style: {} };
  const overlay = { hidden: true, inert: true, style: { removeProperty() {} } };
  let footer = { top: 1200, bottom: 1400 };
  const win = new EventTarget();
  globalThis.window = win;
  globalThis.location = { href: 'https://herizon.test/' };
  globalThis.innerHeight = 900; globalThis.scrollY = 500;
  const reduced = new EventTarget(); reduced.matches = false;
  const fine = new EventTarget(); fine.matches = true;
  globalThis.matchMedia = query => query.includes('reduce') ? reduced : fine;
  globalThis.document = { body, documentElement: new EventTarget(), querySelector: selector => selector === '[data-hc-cursor]' ? cursor : selector === '[data-hc-preloader]' ? overlay : selector === '.footer' ? { getBoundingClientRect: () => footer } : null };
  const queue = new Map(); let id = 0;
  globalThis.requestAnimationFrame = fn => { queue.set(++id, fn); return id; };
  globalThis.cancelAnimationFrame = key => queue.delete(key);
  return { body, win, fine, reduced, cursor, overlay, setFooter: value => { footer = value; }, flush: () => { const pending = [...queue.values()]; queue.clear(); pending.forEach(fn => fn()); } };
}

test('footer state works without a CTA, preloader completes once, teardown removes updates', () => {
  const dom = fixture();
  const runtime = createRuntime({ modules: { preloader: true, sticky_cta: false }, selectors: { footer: '.footer' } });
  let completed = 0;
  dom.win.addEventListener('herizon:preloader:done', () => completed++);
  assert.equal(runtime.footerVisible, false);
  dom.setFooter({ top: 850, bottom: 1200 });
  dom.win.dispatchEvent(new Event('scroll')); dom.flush();
  assert.equal(runtime.footerVisible, true);
  assert.equal(dom.body.classList.contains('hc-footer-in-view'), true);
  runtime.finishPreloader(); runtime.finishPreloader(); assert.equal(completed, 1);
  runtime.destroy();
  assert.equal(dom.body.classList.contains('hc-footer-in-view'), false);
  dom.win.dispatchEvent(new Event('scroll')); dom.flush();
  assert.equal(dom.body.classList.contains('hc-footer-in-view'), false);
});

test('cursor initializes on a ready runtime and removes pending animation at breakpoint change', () => {
  const dom = fixture();
  const runtime = createRuntime({ modules: { preloader: false }, selectors: {} });
  const cleanup = initCursor(runtime);
  assert.equal(dom.cursor.hidden, false);
  const move = new Event('pointermove'); move.clientX = 50; move.clientY = 60;
  dom.win.dispatchEvent(move);
  dom.fine.matches = false; dom.fine.dispatchEvent(new Event('change')); dom.flush();
  assert.equal(dom.cursor.hidden, true);
  assert.equal(dom.cursor.classList.contains('is-visible'), false);
  cleanup(); runtime.destroy();
});

test('reduced-motion preloader never locks scrolling, hides overlay and emits completion', () => {
  const dom = fixture(); dom.reduced.matches = true;
  const runtime = createRuntime({ modules: { preloader: true }, selectors: {} });
  let completed = 0; dom.win.addEventListener('herizon:preloader:done', () => completed++);
  const cleanup = initPreloader(runtime);
  assert.equal(dom.overlay.hidden, true); assert.equal(dom.overlay.inert, true);
  assert.equal(dom.body.classList.contains('hc-is-loading'), false);
  assert.equal(completed, 1); cleanup(); assert.equal(completed, 1); runtime.destroy();
});
