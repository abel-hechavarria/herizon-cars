import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { transformSync } from 'esbuild';

function editorFixture(attributes = {}) {
  const schema = JSON.parse(readFileSync(new URL('../../assets/button-schema.json', import.meta.url)));
  const metadata = JSON.parse(readFileSync(new URL('../../blocks/button/block.json', import.meta.url)));
  const changes = [];
  const useBlockProps = value => value; useBlockProps.save = value => value;
  const components = Object.fromEntries(['PanelBody','TextControl','SelectControl','ToggleControl','RangeControl','Button','ColorPalette','GradientPicker','SearchControl','Spinner','Notice','__experimentalUnitControl','__experimentalNumberControl'].map(name => [name, name]));
  const wp = { blocks: { registerBlockType() {} }, element: { createElement(type, props, ...children) { return { type, props: props || {}, children: children.flat(Infinity) }; }, Fragment: 'Fragment', useState(value) { return [value, () => {}]; }, useEffect() {} }, blockEditor: { InspectorControls: 'InspectorControls', useBlockProps, MediaUpload: 'MediaUpload', MediaUploadCheck: 'MediaUploadCheck' }, components, i18n: { __(text) { return text; } }, data: { useSelect(callback) { return callback(() => ({ getMedia() { return null; } })); } } };
  let source = readFileSync(new URL('./button-editor.jsx', import.meta.url), 'utf8').replace(/^import .*;\r?\n/gm, '');
  source += '\nglobalThis.helpers = { safeHref, resolveValue, previewStyle, ButtonSave, ButtonEdit };';
  const compiled = transformSync(source, { loader: 'jsx', jsxFactory: 'wp.element.createElement', jsxFragment: 'wp.element.Fragment' }).code;
  const context = vm.createContext({ wp, schema, metadata, URL }); vm.runInContext(compiled, context);
  return { ...context.helpers, attributes: { variant: 'pill', text: 'Discover', url: '/cars/', iconEnabled: true, icon: 'fas:car', iconPosition: 'right', ...attributes }, changes, change: patch => changes.push(patch) };
}
function nodes(tree) { return tree && typeof tree === 'object' ? [tree, ...(tree.children || []).flatMap(nodes)] : []; }

test('static fallback keeps text, destination, accessible label and safe new-tab relation', () => {
  const fixture = editorFixture({ newTab: true, rel: 'nofollow noopener', ariaLabel: 'Discover available cars' });
  const result = fixture.ButtonSave({ attributes: fixture.attributes });
  const link = nodes(result).find(node => node.type === 'a');
  assert.equal(link.props.href, '/cars/'); assert.equal(link.props.target, '_blank');
  assert.equal(link.props['aria-label'], 'Discover available cars');
  assert.deepEqual(new Set(link.props.rel.split(' ')), new Set(['nofollow', 'noopener', 'noreferrer']));
  assert.deepEqual(link.children, ['Discover']);
  assert.equal(fixture.safeHref('javascript:alert(1)'), undefined);
  assert.equal(fixture.safeHref('data:text/html,danger'), undefined);
  assert.equal(fixture.safeHref(''), undefined);
  assert.equal(fixture.safeHref('#'), undefined);
  assert.equal(fixture.safeHref('tel:+36123456'), 'tel:+36123456');
});

test('responsive overrides compile to shared PHP variable contract and inherit deliberately', () => {
  const fixture = editorFixture({ styles: { normal: { desktop: { fontSize: '16px' }, tablet: { fontSize: '15px' }, mobile: { paddingLeft: '12px' } }, hover: { tablet: { background: '#ff0000' } } } });
  const style = fixture.previewStyle(fixture.attributes);
  assert.equal(style['--hc-button-font-size'], '16px');
  assert.equal(style['--hc-button-tablet-font-size'], '15px');
  assert.equal(style['--hc-button-mobile-padding-left'], '12px');
  assert.equal(style['--hc-button-tablet-hover-background'], '#ff0000');
  assert.equal(fixture.resolveValue(fixture.attributes, 'fontSize', 'normal', 'mobile'), '15px');
  assert.equal(fixture.resolveValue(fixture.attributes, 'background', 'hover', 'mobile'), '#ff0000');
});

test('design reset preserves content, link and icon selection', () => {
  const fixture = editorFixture();
  const tree = fixture.ButtonEdit({ attributes: fixture.attributes, setAttributes: fixture.change });
  const reset = nodes(tree).find(node => node.type === 'Button' && node.children.includes('Reset design overrides'));
  assert.ok(reset); reset.props.onClick();
  const change = fixture.changes[0];
  assert.deepEqual(Object.keys(change).sort(), ['duration', 'hoverMove', 'styles']);
  assert.equal(change.duration, 200); assert.equal(change.hoverMove, 3);
  for (const key of ['text', 'url', 'icon', 'iconPosition', 'variant']) assert.equal(Object.hasOwn(change, key), false);
});

test('buttons without a valid destination remain non-navigating spans in saved fallback', () => {
  for (const url of ['', '#', 'javascript:alert(1)']) {
    const fixture = editorFixture({ url, newTab: true, rel: 'nofollow' });
    const tree = fixture.ButtonSave({ attributes: fixture.attributes });
    assert.equal(nodes(tree).some(node => node.type === 'a'), false);
    const button = nodes(tree).find(node => node.type === 'span');
    assert.ok(button); assert.equal(button.props.href, undefined);
    assert.equal(button.props.target, undefined); assert.equal(button.props.rel, undefined);
  }
});
