const STYLE_DEFAULTS = { duration: 200, hoverMove: 3 };
let bound = false;
let catalogPromise;

function safeIconMarkup(icon) {
  if (!icon || typeof icon !== 'object') return '<span aria-hidden="true">▶</span>';
  const width = Number.parseInt(icon.width, 10);
  const height = Number.parseInt(icon.height, 10);
  const rawPaths = Array.isArray(icon.paths) ? icon.paths : [icon.paths];
  const paths = rawPaths.filter(path => typeof path === 'string' && /^[MmLlHhVvCcSsQqTtAaZz0-9.,\s+-]+$/.test(path));
  if (!Number.isFinite(width) || !Number.isFinite(height) || !paths.length) return '<span aria-hidden="true">▶</span>';
  return `<svg viewBox="0 0 ${width} ${height}" aria-hidden="true" focusable="false">${paths.map(path => `<path d="${path}"></path>`).join('')}</svg>`;
}

async function updateIconPreview(select) {
  const preview = document.querySelector('[data-hc-button-icon-preview]');
  if (!preview) return;
  const url = preview.dataset.iconsUrl;
  if (!url) return;
  catalogPromise ||= fetch(url, { credentials: 'same-origin' })
    .then(response => response.ok ? response.json() : Promise.reject(new Error('Icon catalog unavailable')))
    .then(catalog => catalog.icons || catalog)
    .catch(() => ({}));
  const icons = await catalogPromise;
  if (preview.dataset.currentIcon === select.value) return;
  const entry = icons[select.value];
  const holder = preview.querySelector('.hc-button-icon-preview__icon');
  if (holder) {
    holder.innerHTML = safeIconMarkup(entry);
    preview.dataset.currentIcon = select.value;
  }
}

function getEditedContainer() {
  const page = window.elementor?.getPanelView?.()?.getCurrentPageView?.();
  const view = page?.getOption?.('editedElementView');
  return view?.getContainer?.() || view?.container || null;
}

function resetStyles(button) {
  const container = getEditedContainer();
  if (!container || !window.$e?.run) return;
  const model = container.model;
  const settings = model?.get?.('settings') || model?.attributes?.settings;
  const current = settings?.attributes || settings?.toJSON?.() || {};
  const reset = {};
  for (const key of Object.keys(current)) {
    if (key.startsWith('normal_') || key.startsWith('hover_') || key.startsWith('layout') || key.startsWith('customWidth')) reset[key] = '';
  }
  if (current.__globals__) {
    reset.__globals__ = Object.fromEntries(Object.entries(current.__globals__).filter(([key]) => !key.startsWith('normal_') && !key.startsWith('hover_')));
  }
  reset.layout = '';
  reset.duration = STYLE_DEFAULTS.duration;
  reset.hoverMove = STYLE_DEFAULTS.hoverMove;
  button.disabled = true;
  Promise.resolve(window.$e.run('document/elements/settings', { container, settings: reset }))
    .catch(() => {})
    .finally(() => { button.disabled = false; });
}

function bindEditorControls() {
  if (bound) return;
  bound = true;
  document.addEventListener('change', event => {
    const select = event.target.closest?.('[data-setting="icon"]');
    if (select) updateIconPreview(select);
  });
  document.addEventListener('click', event => {
    const button = event.target.closest?.('[data-hc-button-reset]');
    if (button) { event.preventDefault(); resetStyles(button); }
  }, true);
  const existing = document.querySelector('[data-setting="icon"]');
  if (existing) updateIconPreview(existing);
  const panel = document.querySelector('#elementor-panel');
  if (panel) {
    const observer = new MutationObserver(() => {
      const select = panel.querySelector('[data-setting="icon"]');
      if (select) updateIconPreview(select);
    });
    observer.observe(panel, { childList: true, subtree: true });
  }
}

if (window.elementor?.on) window.elementor.on('preview:loaded', bindEditorControls);
if (window.jQuery) window.jQuery(window).on('elementor:init', bindEditorControls);
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bindEditorControls, { once: true });
else bindEditorControls();
