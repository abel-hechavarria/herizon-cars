// Enhances Elementor's native steps; never persists contact details in browser storage.
const models = {
  'Mercedes-Benz': ['A-osztály', 'C-osztály', 'E-osztály', 'GLC'], Mitsubishi: ['ASX', 'Eclipse Cross', 'Outlander'],
  BMW: ['1-es sorozat', '3-as sorozat', '5-ös sorozat', 'X3'], SEAT: ['Ibiza', 'Leon', 'Ateca'], Audi: ['A3', 'A4', 'A6', 'Q5'],
  Volkswagen: ['Golf', 'Passat', 'Tiguan'], Toyota: ['Corolla', 'RAV4', 'Yaris'], Kia: ['Ceed', 'Sportage', 'Niro'],
  Hyundai: ['i30', 'Tucson', 'Kona'], 'Škoda': ['Octavia', 'Superb', 'Kodiaq'], Honda: ['Civic', 'CR-V', 'Jazz'],
};
const number = new Intl.NumberFormat('hu-HU');
const advanceIds = ['body', 'drive', 'timing', 'color', 'requirements'];

function enhance(form) {
  if (form.dataset.hcEnhanced) return;
  form.dataset.hcEnhanced = 'true';
  const section = form.closest('.hc-import');
  if (!section) return;
  const field = id => form.elements.namedItem(`form_fields[${id}]`);
  const value = id => field(id)?.value.trim() || '';
  const group = id => form.querySelector(`.elementor-field-group-${id}`);
  const summary = section.querySelector('.hc-import__summary');
  // The site's existing fixed decorative blur must not obscure form controls.
  const visibility = new IntersectionObserver(entries => {
    document.body.classList.toggle('hc-import-in-view', entries[0].isIntersecting);
  });
  visibility.observe(section);
  const labels = id => {
    const input = field(id);
    return input?.tagName === 'SELECT' && input.value ? input.selectedOptions[0]?.textContent : value(id);
  };
  const write = (id, text) => { const node = summary?.querySelector(`[data-summary="${id}"]`); if (node && node.textContent !== text) node.textContent = text; };
  function update() {
    write('car', [labels('brand'), value('model')].filter(Boolean).join(' / ') || 'Még nincs megadva');
    write('year', value('year_from') || value('year_to') ? `${value('year_from') || 'Bármelyik'} – ${value('year_to') || 'Bármelyik'}` : 'Rugalmas');
    write('budget', value('budget') ? `${number.format(Number(value('budget')))} Ft` : 'Még nincs megadva');
    write('budget_scope', labels('budget_scope') || 'Még nem tudom');
    write('fuel', labels('fuel') || 'Mindegy');
    write('transmission', labels('transmission') || 'Mindegy');
    write('mileage', value('mileage') ? `${number.format(Number(value('mileage')))} km` : 'Rugalmas');
    const budgetHint = form.querySelector('[data-budget-hint]');
    if (budgetHint) budgetHint.textContent = value('budget') ? `A megadott keret: ${number.format(Number(value('budget')))} Ft. A költségek pontos tartalmát egyeztetni kell.` : 'Forintban add meg a keretet. A költségek pontos tartalmát egyeztetni kell.';
  }
  function error(id, message) {
    const input = field(id); const parent = group(id);
    if (!input || !parent) return;
    let node = parent.querySelector('.hc-import__field-error');
    if (message) {
      if (!node) { node = document.createElement('p'); node.className = 'hc-import__field-error'; node.id = `${form.id}-${id}-error`; parent.append(node); }
      node.textContent = message;
      input.setAttribute('aria-invalid', 'true'); input.setAttribute('aria-describedby', node.id);
    } else {
      node?.remove(); input.removeAttribute('aria-invalid'); input.removeAttribute('aria-describedby');
    }
    input.setCustomValidity(message);
  }
  function validateVehicle() {
    let valid = true;
    for (const id of ['year_from', 'year_to', 'budget', 'mileage', 'power']) {
      const input = field(id); if (!input) continue;
      input.setCustomValidity('');
      let message = '';
      if (input.validity.badInput || (value(id) && (!/^\d+$/.test(value(id)) || !input.validity.valid))) message = 'Adj meg érvényes egész számot a megadott tartományban.';
      if (id === 'year_to' && value('year_from') && value(id) && Number(value('year_from')) > Number(value(id))) message = 'A felső évhatár nem lehet kisebb az alsónál.';
      error(id, message); if (message) valid = false;
    }
    return valid;
  }
  function validateContact() {
    let valid = true;
    for (const id of ['first_name', 'last_name', 'email', 'phone']) {
      const input = field(id); input.setCustomValidity('');
      let message = '';
      if (['first_name', 'last_name'].includes(id) && !value(id).replace(/<[^>]*>/g, '').trim()) message = 'Kérjük, add meg a neved.';
      if (['email', 'phone'].includes(id) && !value('email') && !value('phone')) message = 'Adj meg legalább egy e-mail-címet vagy telefonszámot.';
      if (id === 'email' && value(id) && input.validity.typeMismatch) message = 'Kérjük, adj meg érvényes e-mail-címet.';
      if (id === 'phone' && value(id) && (!/^\+?[0-9 ().\/-]+$/.test(value(id)) || value(id).replace(/\D/g, '').length < 7 || value(id).replace(/\D/g, '').length > 15)) message = 'Adj meg érvényes telefonszámot, például +36 30 123 4567.';
      error(id, message); if (message) valid = false;
    }
    return valid;
  }
  function demoResult() {
    const note = form.querySelector('.hc-import__demo');
    if (!note) return;
    note.setAttribute('role', 'status');
    note.textContent = 'A bemutató ellenőrzése kész. A kérés nem lett elküldve és nem lett elmentve. A megadott adatok a lap bezárásáig maradnak a mezőkben.';
    note.tabIndex = -1; note.focus({ preventScroll: true });
  }
  const autocompletes = { first_name: 'given-name', last_name: 'family-name', email: 'email', phone: 'tel', city: 'address-level2' };
  Object.entries(autocompletes).forEach(([id, token]) => field(id)?.setAttribute('autocomplete', token));
  const lengths = { first_name:100,last_name:100,email:254,phone:40,model:120,city:120,color:120,requirements:3000,message:5000 };
  Object.entries(lengths).forEach(([id, max]) => field(id)?.setAttribute('maxlength', String(max)));
  field('phone')?.removeAttribute('pattern'); // International formats; server enforces conservative syntax.
  field('phone')?.setAttribute('inputmode', 'tel');
  const list = document.createElement('datalist'); list.id = `${form.id}-models`; form.append(list);
  field('model')?.setAttribute('list', list.id);
  function suggestions() { list.replaceChildren(...(models[value('brand')] || []).map(text => { const option = document.createElement('option'); option.value = text; return option; })); }
  field('brand')?.addEventListener('change', suggestions);
  form.addEventListener('input', () => { update(); validateVehicle(); if (form.dataset.hcContactChecked) validateContact(); });
  form.addEventListener('change', update);
  // Do not restructure fields until Elementor has constructed its own two steps.
  function advanced() {
    if (form.querySelector('.hc-import__advanced') || !form.querySelector('.e-form__step')) return;
    const first = group(advanceIds[0]);
    if (!first) return;
    const details = document.createElement('details'); details.className = 'hc-import__advanced';
    const toggle = document.createElement('summary'); toggle.textContent = 'További igények és felszereltség';
    const content = document.createElement('div'); content.className = 'hc-import__advanced-fields';
    first.before(details); details.append(toggle, content);
    advanceIds.forEach(id => { const node = group(id); if (node) content.append(node); });
    group('advanced_title')?.setAttribute('hidden', '');
  }
  function focusHeading() {
    requestAnimationFrame(() => {
      const heading = [...form.querySelectorAll('.hc-import__step-title')].find(node => node.getClientRects().length);
      if (heading) { heading.tabIndex = -1; heading.focus({ preventScroll: true }); heading.scrollIntoView({ block: 'nearest', behavior: 'instant' }); }
      const current = [...form.querySelectorAll('.e-form__step')].findIndex(node => node.getClientRects().length);
      form.querySelectorAll('.e-form__indicators__indicator').forEach((node, index) => { if (index === current) node.setAttribute('aria-current','step'); else node.removeAttribute('aria-current'); });
    });
  }
  form.addEventListener('click', event => {
    const button = event.target.closest('button'); if (!button) return;
    if (button.classList.contains('e-form__buttons__wrapper__button-next') && !validateVehicle()) {
      event.preventDefault(); event.stopImmediatePropagation(); form.querySelector('[aria-invalid="true"]')?.focus(); return;
    }
    if (button.type === 'submit') {
      form.dataset.hcContactChecked = 'true';
      if (!validateContact()) { event.preventDefault(); event.stopImmediatePropagation(); form.querySelector('[aria-invalid="true"]')?.focus(); return; }
      event.preventDefault(); event.stopImmediatePropagation(); demoResult(); return;
    }
    if (button.classList.contains('e-form__buttons__wrapper__button-next') || button.classList.contains('e-form__buttons__wrapper__button-previous')) focusHeading();
  }, true);
  form.addEventListener('submit', event => {
    event.preventDefault(); event.stopImmediatePropagation();
    form.dataset.hcContactChecked = 'true';
    if (!validateContact()) form.querySelector('[aria-invalid="true"]')?.focus(); else demoResult();
  }, true);
  const observer = new MutationObserver(advanced); observer.observe(form, { childList: true, subtree: true });
  advanced(); update(); suggestions();
  // Elementor redraws widgets in its editor; release the old observer on removal.
  const cleanup = new MutationObserver(() => { if (!form.isConnected) { observer.disconnect(); visibility.disconnect(); document.body.classList.remove('hc-import-in-view'); cleanup.disconnect(); } });
  cleanup.observe(document.body, { childList: true, subtree: true });
}
function scan(root = document) { root.querySelectorAll('form#hc_import_request').forEach(enhance); }
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => scan()); else scan();
if (window.jQuery) {
  const register = () => window.elementorFrontend?.hooks?.addAction('frontend/element_ready/form.default', scope => scan(scope[0]));
  window.jQuery(window).on('elementor/frontend/init', register); register();
}
