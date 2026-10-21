# Architecture

Herizon Cars is a modular child theme of Hello Elementor. It keeps native Elementor editing while adding reusable presentation components, a configurable effects layer and a shared button component for Elementor and Gutenberg.

## Bootstrap and boundaries

[functions.php](../../functions.php) loads a small [bootstrap](../../inc/bootstrap.php). Explicit includes register setup, settings, assets, modules and editor integrations; files are not discovered by scanning folders. PHP names, CSS classes and browser events use the respective `herizon_cars_`, `hc-` and `herizon:` prefixes.

The [module registry](../../inc/modules.php) makes registration idempotent and checks module requirements before loading optional functionality. The Settings API provides administrator controls, validates saved values and uses WordPress capability and nonce checks. Contact fields start empty; the floating contact panel requires a configured destination.

Elementor remains the source of colors and fonts. The theme resolves the active kit's background, with `#101010` as fallback. Fluid typography and image rotation are opt-in classes rather than global replacements for editorial styles.

## Browser lifecycle and progressive enhancement

The [browser entry](../../src/js/main.js) creates one [runtime controller](../../src/js/runtime.js) and imports enabled effects. A generation token prevents a stale asynchronous import from initializing after teardown. Each module returns its cleanup function; teardown removes listeners and animation frames, destroys Lenis and clears transient state.

The controller owns preloader completion, reduced-motion preferences and hero/footer visibility. Footer state works independently of the floating CTA, so disabling contact actions does not break the bottom blur or back-to-top control. The preloader starts hidden, locks scrolling only after initialization and has bounded animation plus watchdog exits. History scroll restoration remains native.

Cursor and smooth scrolling have pointer, viewport and motion gates. Same-page anchors compare origin, path and query before interception, then account for the configured header offset and move keyboard focus. Widgets render content on the server; their [enhancement layer](../../src/js/widgets.js) disposes detached Elementor roots and avoids accumulating listeners across renders. Duplicate marquee groups are decorative and inert. The logo marquee uses hover/focus pausing without a separate pause button; the text marquee retains its pause control.

## One button render contract, two editors

[HCars Button](../../inc/components/button.php) has one PHP normalizer and server renderer. A [shared schema](../../assets/button-schema.json) defines three presets and 30 typed style fields. Normal/hover overrides map to responsive CSS variables; aliases resolve on the component itself so preset defaults do not mask inherited overrides.

The [Elementor widget](../../inc/widgets/class-widget-button.php) and [Gutenberg block](../../blocks/button/block.json) adapt native controls to that contract. Rendering reads the schema directly, including instances created after Elementor has cached its controls. Gutenberg saves a small usable text/link fallback; the active theme renders the full component dynamically. Empty or rejected destinations produce a non-navigating span.

Editor JavaScript handles selection, preview and reset. It is not required for button behavior on the public page. Library icons render only the selected SVG; custom SVG attachments are sanitized again before output. The complete local icon catalog is loaded by editor tooling, not by the button frontend.

## Build, media handling and tradeoffs

[Vite asset registration](../../inc/assets.php) reads the manifest, traverses imported CSS and verifies output files before registering URLs. Missing compiled entries trigger an administrator notice. Widget styles are declared as dependencies; button metadata uses separate editor and view style handles to avoid loading button CSS on pages without the component. The delivery includes compiled assets; Node.js is a development requirement.

[Extended uploads](../../inc/modules/uploads.php) require both upload and administrator capabilities. SVG validation rebuilds an allowed XML subset without network access; DTD/entities, active content and unsafe references are rejected or removed. SVGZ decompression is bounded and normalized to SVG. JSON is parsed before acceptance. Validation runs on temporary upload/sideload files and fails closed when dependencies, reads or writes fail.

The theme deliberately retains its parent-theme dependency. Presentation features belong here; future business data or workflows that must survive a theme switch should move to a plugin. Elementor shadow controls remain desktop-level, while the shared render contract can represent responsive overrides. The full editor icon catalog and generated responsive CSS increase development asset size; the selected SVG and conditional CSS keep that cost out of unrelated public pages. These are implementation choices, not performance benchmark claims.

## Versioned client delivery

[The native updater](../../inc/integrations/github-updates.php) handles only this theme’s exact Update URI. A bounded public request reads the latest stable release from the fixed repository, validates its requirements marker and exact uploaded installation asset, and caches success/failure. Core compares versions and preserves the administrator’s automatic-update choice. Tag-only release automation builds a deterministic runtime ZIP and publishes it after successful checks. [Release contract](../releasing.md).
