// Demo-only controls. The production button's hover/focus behavior is CSS.
const preview = document.querySelector('#live-preview .hc-button');
const variant = document.querySelector('#variant-select');
const icon = document.querySelector('#icon-select');
const position = document.querySelector('#position-select');
const icons = await fetch(new URL('./icons.json', import.meta.url)).then(response => {
  if (!response.ok) throw new Error('Demo icon data unavailable');
  return response.json();
});
function update() {
  preview.className = 'hc-button hc-button--' + variant.value;
  preview.classList.toggle('hc-button--icon-right', position.value === 'right');
  let holder = preview.querySelector('.hc-button__icon');
  if (icon.value === 'none') holder?.remove();
  else {
    if (!holder) { holder = document.createElement('span'); holder.className = 'hc-button__icon'; holder.setAttribute('aria-hidden', 'true'); preview.prepend(holder); }
    // This allowlisted SVG data is generated from the theme's local catalog.
    holder.innerHTML = icons[icon.value] || '';
  }
}
for (const control of [variant, icon, position]) control.addEventListener('change', update);
