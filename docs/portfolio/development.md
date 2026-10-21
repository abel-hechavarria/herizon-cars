# Development and component guide

## Installation

Use PHP 8.2+ with DOM/XML and zlib, Node.js 22.12+ and pnpm 11.25.0. Install WordPress 6.5+ and Hello Elementor for runtime use; install Elementor for custom widgets. Commercial plugins and the client database are not included.

The repository root is the theme source. Follow the [root build commands](../../README.md). `pnpm dev` rebuilds on changes; it is not a separate frontend server. `pnpm package` creates an installation-only ZIP in `build/`.

Copy the theme folder into WordPress `wp-content/themes/` and activate it with Hello Elementor installed. Compiled `dist/` files are required. Missing manifest entries produce an admin notice instead of guessed asset URLs.

## Configuration

Open **Appearance → Herizon Cars**. Independent toggles control effects and technical modules. Saving uses the Settings API, nonces, `manage_options` and server validation. Contact channels start empty; configure phone, international WhatsApp and/or email to enable a useful floating CTA.

| Class | Purpose |
|---|---|
| `hc-hero` | Hero boundary controls when the CTA appears. |
| `hc-footer` | Observed by CTA, back-to-top and blur. |
| `hc-sticky-header` | Optional header height included in anchor offsets. |
| `hc-cursor-magnetic` | Opt-in magnetic interaction. |
| `hc-cursor-magnetic__target` | Dedicated movement wrapper; preserves editorial transforms. |
| `hc-image-rotate` | Decorative rotation with reduced-motion handling. |
| `hc-text-h1`, `hc-text-body`, `hc-text-label` | Examples of opt-in fluid type utilities. |

Selectors/anchor offset are configurable. Applicable styles follow the Elementor kit; explicit component values take precedence. Background fallback: `#101010`. No light-mode switch is included.

## HCars Button

Insert **HCars Button** in either editor. Gutenberg's `herizon-cars/button` is independent of the core Button block. Choose Pill, Outlined Circle or Filled Circle; configure text/link, new-tab behavior and optional accessible label. No destination creates a non-navigating preview.

Choose a local icon or validated SVG media attachment, place or hide the icon and remove its container independently. Normal and hover styles cover colors/gradients, borders/shadows, typography and spacing. Sizes, padding, width and alignment have device overrides. Changing variants preserves customizations; reset clears design/motion while keeping variant, text, link and icon.

CSS uses `hc-button*` and `--hc-button-*`. Examples: `--hc-button-background`, `--hc-button-hover-text-color`, `--hc-button-mobile-font-size`, `--hc-button-duration`, `--hc-button-hover-move`. Tablet ≤1024px and mobile ≤767px match this project's validated settings. A font-family name does not download a font; make it available through the site's styles.

## Other widgets

**Logo marquee:** manual images/links, direction, duration, gaps, height, fade/grayscale. No visible pause button by client choice; configured hover pause, link-focus pause and reduced motion remain.

**Text marquee:** text/separators, direction, typography, colors, inclined band and a visible pause control.

**Expandable gallery:** manual images/captions, device limits, dimensions, keyboard/touch activation and server-rendered fallback. No portfolio/ACF dependency. Duplicate marquee tracks are decorative and excluded from keyboard navigation.

## Demo regeneration

Serve the repository over HTTP and open `docs/portfolio/demo/`. Rebuild assets before regenerating the actual PHP-rendered fragments in a WordPress installation with this theme and Elementor active:

```sh
wp --path=/path/to/wordpress eval-file scripts/render-portfolio.php
```

Run from this checkout and point `--path` at your WordPress installation. The repository does not contain WordPress core. The active theme's compiled assets must match this checkout.

This writes demo files without creating or updating WordPress content. Hashed asset filenames can change after a build; regenerate the demo before publishing those files.

## Maintenance boundaries

Administrative SVG/SVGZ/JSON support rejects invalid input or missing dependencies. SVGZ is bounded and normalized to SVG. Image-size rules do not remove existing image files. Disabling native site-icon output does not supply a replacement favicon. Configure these options for the destination site instead of assuming they suit every project.
