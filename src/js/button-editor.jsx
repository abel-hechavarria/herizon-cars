import metadata from '../../blocks/button/block.json';
import schema from '../../assets/button-schema.json';
import '../scss/button.scss';

const { registerBlockType } = wp.blocks;
const { useState, useEffect, Fragment } = wp.element;
const { InspectorControls, useBlockProps, MediaUpload, MediaUploadCheck } = wp.blockEditor;
const { PanelBody, TextControl, SelectControl, ToggleControl, RangeControl, Button, ColorPalette, GradientPicker, SearchControl, Spinner, Notice, __experimentalUnitControl: UnitControl, __experimentalNumberControl: NumberControl } = wp.components;
const { __ } = wp.i18n;
const { useSelect } = wp.data;
const t = value => __(value, 'herizon-cars');
const kebab = key => key.replace(/[A-Z]/g, letter => '-' + letter.toLowerCase());
const breakpoints = ['desktop', 'tablet', 'mobile'];
const safeHref = value => {
  const input = typeof value === 'string' ? value.trim() : '';
  if (!input || input === '#') return undefined;
  try { const parsed = new URL(input, 'https://herizon.invalid/'); return ['http:', 'https:', 'mailto:', 'tel:'].includes(parsed.protocol) ? input : undefined; } catch { return undefined; }
};
const emptyStyles = () => ({ normal: { desktop: {}, tablet: {}, mobile: {} }, hover: { desktop: {}, tablet: {}, mobile: {} } });
const palette = [ ['Blue', '#81B3F5'], ['Dark', '#101010'], ['White', '#FFFFFF'], ['Light', '#D4D7DC'], ['Accent', '#1C89F6'] ].map(([name, color]) => ({ name: t(name), color }));
const lengths = ['px', 'rem', 'em', '%', 'vw', 'vh'].map(value => ({ value, label: value }));

function resolveValue(attrs, key, state, breakpoint) {
  const index = breakpoints.indexOf(breakpoint);
  const own = phase => {
    for (let at = index; at >= 0; at--) { const value = attrs.styles?.[phase]?.[breakpoints[at]]?.[key]; if (value !== undefined && value !== '') return value; }
    return undefined;
  };
  const preset = schema.presets[attrs.variant] || schema.presets.pill;
  return state === 'hover' ? own('hover') ?? preset.hover[key] ?? own('normal') ?? preset.normal[key] : own('normal') ?? preset.normal[key];
}
function previewStyle(attrs) {
  const style = { '--hc-button-duration': (attrs.duration ?? 200) + 'ms', '--hc-button-hover-move': (attrs.hoverMove ?? 3) + 'px' };
  for (const state of ['normal', 'hover']) for (const breakpoint of breakpoints) for (const field of schema.fields) {
    const value = attrs.styles?.[state]?.[breakpoint]?.[field.key];
    if (value === undefined || value === '') continue;
    const prefix = (breakpoint === 'desktop' ? '' : breakpoint + '-') + (state === 'hover' ? 'hover-' : '');
    style['--hc-button-' + prefix + kebab(field.key)] = String(value);
  }
  return style;
}
function LengthControl({ label, value, onChange }) {
  return UnitControl ? <UnitControl label={label} value={value ?? ''} units={lengths} onChange={onChange} /> : <TextControl label={label} value={value ?? ''} onChange={onChange} help={t('Choose a CSS length, such as 16px or 1rem.')} />;
}
function ShadowControl({ label, value, onChange }) {
  const match = String(value || '').match(/^(-?[\d.]+)px\s+(-?[\d.]+)px\s+([\d.]+)px\s+(-?[\d.]+)px\s+(.+)$/);
  const parts = match ? [Number(match[1]), Number(match[2]), Number(match[3]), Number(match[4]), match[5]] : [0, 8, 24, 0, 'rgba(0,0,0,0.2)'];
  const update = (at, next) => { const copy = [...parts]; copy[at] = next; onChange(copy.slice(0, 4).map(number => number + 'px').join(' ') + ' ' + copy[4]); };
  return <div className="hc-button-editor-field"><SelectControl label={label} value={!value ? '' : value === 'none' ? 'none' : 'custom'} options={[{ label: t('Use inherited value'), value: '' }, { label: t('None'), value: 'none' }, { label: t('Custom shadow'), value: 'custom' }]} onChange={next => onChange(next === 'custom' ? '0px 8px 24px 0px rgba(0,0,0,0.2)' : next)} />
    {value && value !== 'none' && <Fragment>{[t('Horizontal offset'), t('Vertical offset'), t('Blur'), t('Spread')].map((part, index) => <RangeControl key={part} label={part} value={parts[index]} min={index === 2 ? 0 : -100} max={index === 2 ? 150 : 100} onChange={next => update(index, next ?? 0)} />)}<ColorPalette colors={palette} value={parts[4]} enableAlpha onChange={next => update(4, next || 'transparent')} /></Fragment>}
  </div>;
}
function FieldControl({ field, value, fallback, onChange }) {
  const label = t(field.label);
  const displayed = value ?? fallback ?? '';
  if (field.type === 'color' || field.type === 'background') return <div className="hc-button-editor-field"><p><strong>{label}</strong></p><ColorPalette colors={palette} value={String(displayed).includes('gradient') ? undefined : displayed} enableAlpha onChange={next => onChange(next || '')} />
    {field.type === 'background' && GradientPicker && <GradientPicker value={String(displayed).includes('gradient') ? displayed : undefined} onChange={next => onChange(next || '')} gradients={[{ name: t('Blue gradient'), gradient: 'linear-gradient(135deg,#81B3F5 0%,#1C89F6 100%)', slug: 'herizon-blue' }]} />}
    <Button variant="tertiary" onClick={() => onChange('transparent')}>{t('Transparent')}</Button><Button variant="tertiary" onClick={() => onChange('')}>{t('Use inherited value')}</Button></div>;
  if (field.type === 'select') return <SelectControl label={label} value={value ?? ''} help={t('Inherited:') + ' ' + displayed} options={[{ label: t('Use inherited value'), value: '' }, ...Object.entries(field.options || {}).map(([option, text]) => ({ value: option, label: t(text) }))]} onChange={onChange} />;
  if (field.type === 'shadow') return <ShadowControl label={label} value={value} onChange={onChange} />;
  if (field.type === 'number') {
    const Control = NumberControl || TextControl;
    return <Control label={label} type="number" value={value ?? ''} placeholder={String(fallback ?? '')} step={0.05} min={0.5} max={5} onChange={next => onChange(next === '' ? '' : String(Math.min(5, Math.max(0.5, Number(next) || 0.5))))} />;
  }
  if (field.type === 'font') return <TextControl label={label} value={value ?? ''} placeholder={t('Use the Elementor accent font')} onChange={onChange} help={t('Font family names, for example Saira, sans-serif. Empty uses the preset.')} />;
  if (field.key === 'width') return <div className="hc-button-editor-field"><SelectControl label={label} value={value === undefined || value === '' ? '' : value === 'auto' || value === '100%' ? value : 'custom'} options={[{ label: t('Use inherited value'), value: '' }, { label: t('Fit content'), value: 'auto' }, { label: t('Full width'), value: '100%' }, { label: t('Custom width'), value: 'custom' }]} onChange={next => onChange(next === 'custom' ? '240px' : next)} />{value && !['auto', '100%'].includes(value) && <LengthControl label={t('Custom width')} value={value} onChange={onChange} />}</div>;
  return <div className="hc-button-editor-field"><LengthControl label={label} value={displayed} onChange={onChange} />{value !== undefined && <Button variant="tertiary" onClick={() => onChange('')}>{t('Use inherited value')}</Button>}</div>;
}
function Icon({ attributes, icons, media }) {
  if (!attributes.iconEnabled) return null;
  const mediaUrl = attributes.iconSource === 'media' && media?.mime_type === 'image/svg+xml' ? safeHref(media.source_url) : undefined;
  if (attributes.iconSource === 'media' && !mediaUrl) return null;
  const definition = icons?.[attributes.icon || 'fas:play'];
  return <span className="hc-button__icon" aria-hidden="true">{attributes.iconSource === 'media' ? <span className="hc-button__media-icon" style={{ maskImage: 'url(' + JSON.stringify(mediaUrl) + ')', WebkitMaskImage: 'url(' + JSON.stringify(mediaUrl) + ')' }} /> : definition ? <svg viewBox={'0 0 ' + definition.width + ' ' + definition.height} focusable="false" aria-hidden="true">{definition.paths.map((path, index) => <path key={index} d={typeof path === 'string' ? path : path.d} />)}</svg> : <svg viewBox="0 0 384 512" focusable="false" aria-hidden="true"><path d="M0 0 384 256 0 512Z" /></svg>}</span>;
}
function ButtonEdit({ attributes, setAttributes }) {
  const [state, setState] = useState('normal');
  const [breakpoint, setBreakpoint] = useState('desktop');
  const [search, setSearch] = useState('');
  const [icons, setIcons] = useState(null);
  const [iconError, setIconError] = useState(false);
  const [showIcons, setShowIcons] = useState(false);
  const media = useSelect(select => attributes.iconSource === 'media' && attributes.iconId ? select('core').getMedia(attributes.iconId) : null, [attributes.iconSource, attributes.iconId]);
  useEffect(() => {
    let active = true;
    if (attributes.iconSource !== 'media' || showIcons) import('../../assets/button-icons.json').then(data => { if (active) setIcons(data.default); }).catch(() => { if (active) setIconError(true); });
    return () => { active = false; };
  }, [showIcons, attributes.iconSource]);
  const updateField = (field, value) => {
    const styles = { ...emptyStyles(), ...attributes.styles };
    const group = { ...styles[state], [breakpoint]: { ...styles[state]?.[breakpoint] } };
    if (value === undefined || value === '') delete group[breakpoint][field]; else group[breakpoint][field] = String(value);
    setAttributes({ styles: { ...styles, [state]: group } });
  };
  const cls = 'hc-button hc-button--' + (attributes.variant || 'pill') + (attributes.iconPosition === 'right' ? ' hc-button--icon-right' : '') + (attributes.iconContainer === false ? ' hc-button--no-icon-container' : '');
  const blockProps = useBlockProps({ className: 'hc-button-wrap hc-button-editor-preview hc-button-preview--' + breakpoint, style: { ...previewStyle(attributes), justifyContent: resolveValue(attributes, 'align', state, breakpoint) || 'flex-start' } });
  const href = safeHref(attributes.url);
  const LinkTag = href ? 'a' : 'span';
  const iconResults = icons && showIcons ? Object.entries(icons).filter(([id, icon]) => (id + ' ' + icon.label).toLowerCase().includes(search.toLowerCase())).slice(0, 60) : [];
  return <Fragment><InspectorControls>
    <PanelBody title={t('Content')} initialOpen><TextControl label={t('Text')} value={attributes.text || ''} onChange={text => setAttributes({ text })} /><TextControl label={t('Link URL')} value={attributes.url || ''} onChange={url => setAttributes({ url })} /><ToggleControl label={t('Open in a new tab')} checked={Boolean(attributes.newTab)} onChange={newTab => setAttributes({ newTab })} /><TextControl label={t('Link relationship (rel)')} value={attributes.rel || ''} onChange={rel => setAttributes({ rel })} /><TextControl label={t('Accessible label')} value={attributes.ariaLabel || ''} onChange={ariaLabel => setAttributes({ ariaLabel })} help={t('Optional. Use only when the visible text does not describe the destination.')} /></PanelBody>
    <PanelBody title={t('Design and icon')} initialOpen={false}><SelectControl label={t('Preset')} value={attributes.variant || 'pill'} options={Object.entries(schema.presets).map(([value, preset]) => ({ value, label: t(preset.label) }))} onChange={variant => setAttributes({ variant })} /><ToggleControl label={t('Show icon')} checked={attributes.iconEnabled !== false} onChange={iconEnabled => setAttributes({ iconEnabled })} />
      {attributes.iconEnabled !== false && <Fragment><SelectControl label={t('Icon source')} value={attributes.iconSource || 'library'} options={[{ value: 'library', label: t('Icon library') }, { value: 'media', label: t('Custom SVG') }]} onChange={iconSource => setAttributes({ iconSource })} />
        {attributes.iconSource === 'media' ? <MediaUploadCheck><MediaUpload allowedTypes={['image/svg+xml']} value={attributes.iconId} onSelect={attachment => setAttributes({ iconId: attachment.id })} render={({ open }) => <Button variant="secondary" onClick={open}>{attributes.iconId ? t('Replace SVG') : t('Choose SVG')}</Button>} />{attributes.iconId > 0 && <Button variant="tertiary" onClick={() => setAttributes({ iconId: 0 })}>{t('Remove SVG')}</Button>}</MediaUploadCheck> : <Fragment><Button variant="secondary" onClick={() => setShowIcons(value => !value)}>{showIcons ? t('Close icon picker') : t('Choose icon')}</Button>{showIcons && <div><SearchControl label={t('Search icons')} value={search} onChange={setSearch} />{!icons && !iconError && <Spinner />}{iconError && <Notice status="warning" isDismissible={false}>{t('The icon library could not be loaded. Your selected icon is preserved.')}</Notice>}<div style={{ display: 'grid', gridTemplateColumns: 'repeat(5,minmax(0,1fr))', gap: 4, maxHeight: 300, overflowY: 'auto' }}>{iconResults.map(([id, icon]) => <Button key={id} label={icon.label} title={icon.label} isPressed={attributes.icon === id} variant={attributes.icon === id ? 'primary' : 'secondary'} style={{ display: 'grid', padding: 10, minWidth: 0 }} onClick={() => setAttributes({ icon: id })}><svg viewBox={'0 0 ' + icon.width + ' ' + icon.height} width="20" height="20" fill="currentColor" aria-hidden="true">{icon.paths.map((path, index) => <path key={index} d={typeof path === 'string' ? path : path.d} />)}</svg></Button>)}</div><p>{t('Showing up to 60 matches. Refine the search for more icons.')}</p></div>}</Fragment>}
        <SelectControl label={t('Icon position')} value={attributes.iconPosition || 'left'} options={[{ value: 'left', label: t('Left') }, { value: 'right', label: t('Right') }]} onChange={iconPosition => setAttributes({ iconPosition })} /><ToggleControl label={t('Icon container')} checked={attributes.iconContainer !== false} onChange={iconContainer => setAttributes({ iconContainer })} /></Fragment>}
      <RangeControl label={t('Transition duration (ms)')} value={attributes.duration ?? 200} min={0} max={2000} step={10} onChange={duration => setAttributes({ duration: duration ?? 200 })} /><RangeControl label={t('Icon hover movement (px)')} value={attributes.hoverMove ?? 3} min={-30} max={30} onChange={hoverMove => setAttributes({ hoverMove: hoverMove ?? 3 })} /><Button variant="secondary" onClick={() => setAttributes({ styles: emptyStyles(), duration: 200, hoverMove: 3 })}>{t('Reset design overrides')}</Button></PanelBody>
    <PanelBody title={t('Normal and hover styles')} initialOpen={false}><SelectControl label={t('State')} value={state} options={[{ value: 'normal', label: t('Normal') }, { value: 'hover', label: t('Hover / keyboard focus') }]} onChange={setState} /><SelectControl label={t('Device')} value={breakpoint} options={[{ value: 'desktop', label: t('Desktop') }, { value: 'tablet', label: t('Tablet — up to 1024 px') }, { value: 'mobile', label: t('Mobile — up to 767 px') }]} onChange={setBreakpoint} /><p>{t('Empty overrides inherit from larger devices and the selected preset. Preview follows the device and state selected here.')}</p>{schema.fields.map(field => <FieldControl key={field.key} field={field} value={attributes.styles?.[state]?.[breakpoint]?.[field.key]} fallback={resolveValue(attributes, field.key, state, breakpoint)} onChange={value => updateField(field.key, value)} />)}</PanelBody>
    </InspectorControls><div {...blockProps}><LinkTag href={href} className={cls} data-hc-preview-state={state} onClick={event => event.preventDefault()} aria-label={attributes.ariaLabel || undefined}><Icon attributes={attributes} icons={icons} media={media} /><span className="hc-button__label">{attributes.text || t('Button text')}</span></LinkTag></div></Fragment>;
}
function ButtonSave({ attributes }) {
  const rel = [...new Set((attributes.rel || '').split(/\s+/).filter(Boolean).concat(attributes.newTab ? ['noopener', 'noreferrer'] : []))].join(' ');
  const blockProps = useBlockProps.save({ className: 'hc-button-wrap' });
  const href = safeHref(attributes.url);
  const LinkTag = href ? 'a' : 'span';
  return <div {...blockProps}><LinkTag href={href} className={'hc-button hc-button--' + (attributes.variant || 'pill')} target={href && attributes.newTab ? '_blank' : undefined} rel={href ? rel || undefined : undefined} aria-label={attributes.ariaLabel || undefined}>{attributes.text || t('Button text')}</LinkTag></div>;
}
registerBlockType(metadata.name, { ...metadata, title: t('HCars Button'), edit: ButtonEdit, save: ButtonSave });
