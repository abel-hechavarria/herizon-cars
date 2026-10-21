import test from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import { readFileSync } from 'node:fs';

function editorFixture(settings) {
  const commands = [], listeners = [];
  const container = { model: { get: () => ({ attributes: settings }) } };
  const window = { elementor: { getPanelView: () => ({ getCurrentPageView: () => ({ getOption: () => ({ getContainer: () => container }) }) }) }, $e: { run(name, data) { commands.push({ name, settings: data.settings }); return Promise.resolve(); } } };
  const document = { readyState: 'complete', querySelector: () => null, addEventListener: (...args) => listeners.push(args) };
  const context = vm.createContext({ window, document, fetch: () => {}, console });
  vm.runInContext(readFileSync(new URL('./button-elementor.js', import.meta.url), 'utf8') + '\nglobalThis.helpers={resetStyles,safeIconMarkup};', context);
  return { ...context.helpers, commands, listeners };
}

test('Elementor reset preserves variant/content/icon and removes linked global style overrides', async () => {
  const fixture = editorFixture({ variant: 'outline', text: 'Contact', icon: 'fas:house', url: '#contact', normal_textcolor: '#ff0000', hover_fontsize_mobile: { size: 18, unit: 'px' }, layout_mobile: 'full', __globals__: { normal_textcolor: 'globals/colors?id=primary', hover_background_color: 'globals/colors?id=accent', other_field: 'keep' } });
  const button = { disabled: false }; fixture.resetStyles(button);
  await new Promise(resolve => setImmediate(resolve));
  assert.equal(fixture.commands.length, 1);
  const reset = fixture.commands[0].settings;
  for (const key of ['variant', 'text', 'icon', 'url']) assert.equal(Object.hasOwn(reset, key), false);
  assert.equal(reset.normal_textcolor, ''); assert.equal(reset.hover_fontsize_mobile, '');
  assert.equal(reset.layout_mobile, ''); assert.equal(reset.duration, 200); assert.equal(reset.hoverMove, 3);
  assert.deepEqual(JSON.parse(JSON.stringify(reset.__globals__)), { other_field: 'keep' });
  assert.equal(button.disabled, false);
  assert.equal(fixture.listeners.find(([event]) => event === 'click')[2], true);
});

test('local icon preview never accepts markup in SVG paths', () => {
  const fixture = editorFixture({});
  const valid = fixture.safeIconMarkup({ width: 12, height: 12, paths: ['M0 0L12 12Z'] });
  assert.ok(valid.includes('<svg')); assert.ok(valid.includes('aria-hidden="true"'));
  const invalid = fixture.safeIconMarkup({ width: 12, height: 12, paths: ['M0" onload="alert(1)'] });
  assert.equal(invalid.includes('<svg'), false); assert.equal(invalid.includes('onload'), false);
});
