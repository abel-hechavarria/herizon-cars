# Verification

## Source checks for the 1.3.0 development snapshot

On 2026-10-22, the source publication checks passed with a frozen pnpm install,
PHP/JavaScript syntax checks, the PHP suites (including 26 HCars Process and 26
import-form assertions), 10 JavaScript tests, 16 Python release tests and a Vite
build. Vite retains the existing large editor-icon chunk warning. These checks
do not publish a stable release or install an update on a customer site.

The following sections retain the scope and evidence of the earlier recorded
delivery. They are not a claim that every earlier browser scenario was rerun
for the source publication.

This summary covers the recorded local delivery checks for theme version 1.1.0, including HCars Button and the subsequent logo-marquee adjustment. It distinguishes source-level tests from checks made inside WordPress; it does not represent a production rollout.

## Automated checks and reproduction

PHP syntax and standalone tests were checked with PHP 8.2. WordPress integration was exercised separately on PHP 8.5.1, WordPress 7.1.3, Elementor 4.3.5 and Elementor Pro 4.3.2. Browser checks used Edge Chromium through Playwright.

| Recorded suite | Coverage | Result |
| --- | --- | --- |
| 48 button assertions | Typed attributes and CSS, destinations, escaping, presets, SVG sanitization and removed attachments | Passed |
| 86 technical assertions | SVG/XML, bounded gzip, JSON, permissions, exact-host HTTPS normalization and utilities | Passed |
| 18 settings checks | Validation and configuration behavior | Passed |
| 2 missing-dependency checks | Rejection without DOM support or gzip decoding | Passed on PHP 8.2; DOM-disable simulation is skipped when the runtime cannot disable that class |
| 10 JavaScript tests | Runtime lifecycle, anchor routing, button fallback/reset and editor preview boundaries | Passed |

The [test runner](../../scripts/test.mjs), [PHP tests](../../tests/php) and [JavaScript source/tests](../../src/js) are included for inspection. To repeat source checks, use PHP with DOM/libxml and zlib, Node.js 22.12 or newer, and the pnpm version pinned in [package.json](../../package.json):

```sh
pnpm install --frozen-lockfile --ignore-scripts
pnpm lint
pnpm test
pnpm build
```

These commands do not reproduce editor/database/browser scenarios automatically. Those require a running WordPress installation, Hello Elementor, the relevant editors and representative page content.

## Frontend behavior observed

- The base theme was checked around the 767/768 and 1024/1025 px boundaries. Fluid typography was checked at 375, 768 and 1440 px. Button checks additionally included a 390 px viewport.
- The three button presets, custom colors/gradient/type, right/hidden icons, full-width mobile layout, focus styling and 3 px hover movement were observed. Changing presets preserved explicit overrides.
- Reduced motion removed button transitions and icon movement. Preloader failure paths, cursor cleanup, footer visibility and native scrolling alternatives were exercised. Server-rendered content and buttons remained usable without JavaScript.
- The button test pages loaded their component stylesheet without asset 404s or JavaScript errors in the observed runs. The home page did not load button CSS and retained its dark background.
- Logo-marquee rendering retained two groups, hover/focus pausing and reduced-motion behavior after removal of its pause button. The text marquee retained its separate pause control.

## Native editor and WordPress integration

Gutenberg checks covered insertion, editing, saving, reload, preset changes, reset and duplication. The final reload contained five valid blocks. Reset kept content and the selected variant while clearing style overrides.

Elementor checks covered native editing/saving, preset and icon persistence, reset and duplication. An independent WordPress request recovered six saved widget instances, including equivalent duplicates. A subsequent full editor reopening showed the expected state and loaded controls script without JavaScript errors in that run. Fresh widget rendering was also checked after control caching to verify that custom colors and responsive values survived.

A real WordPress SVGZ sideload was sanitized and stored as SVG. Anonymous users did not receive SVG/JSON upload permission; an invalid settings nonce was rejected. Semantic comparisons of the five pre-existing documents and relevant settings found no differences attributable to this delivery. These checks validate the exercised paths, not every possible site configuration.

## Limits of the evidence

The development environment showed intermittent editor loading failures and upstream errors. Inconsistent HTTP/HTTPS asset configuration also affected CSS/font loading in some sessions. Successful save/reopen runs are recorded alongside those failures; universal reload stability or a resolved environment is not claimed.

The responsive checks used laboratory viewports, not physical devices. Safari and Firefox were not tested, and final client visual acceptance was outside the recorded checks. No Lighthouse or Core Web Vitals scores, production reliability, independent accessibility certification or commercial outcomes are claimed. Raw authenticated browser fixtures and installation-specific evidence are intentionally excluded from this public portfolio.

For the implementation boundaries behind these checks, see [Architecture](architecture.md).

## Portfolio snapshot checks

The public source snapshot was installed in a separate directory with `pnpm install --frozen-lockfile`; lint, tests, build and packaging passed there without changing its source or compiled outputs. Its allowlisted files, relative documentation links and asset manifest were checked separately from the development checkout.

The synthetic component demo was checked at 1440, 1024, 768, 767 and 390 px. Variant/icon/position controls, hidden icons, visible focus, text-marquee pause, gallery arrow-key activation and reduced motion passed. No-JavaScript output remained readable. The observed run had no page errors, failed assets, external requests or horizontal overflow. Screenshots are from that demo, not the client website. Current hosted results are linked from the README badge.

## GitHub update delivery (1.2.0)

The update integration adds 108 PHP assertions for stable releases, repository/asset boundaries, requirements, HTTP failures and caching. Sixteen Python delivery tests cover synchronized versions, tag mismatch, metadata, ZIP layout, determinism, missing/invalid compiled assets and draft-publication recovery boundaries. The update provider runs only while this theme is active. Customer production installation has not been exercised by this delivery. [Operating instructions](../releasing.md).

On the LocalWP runtime, `wp_update_themes()` accepted a controlled future-release fixture as a native `herizon-cars` update with version, package and requirements. The original update caches were restored afterwards; no theme files were installed by this test. Real release readback is checked separately from the fixture.
