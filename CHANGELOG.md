# Changelog

## 1.3.0 — Unreleased

- Add HCars Process, a native Elementor section with editable copy, background/overlays, ordered diagonal image cards and shared HCars Button styles.
- Include per-card overrides, responsive layout controls and Hungarian interface translations; no new frontend JavaScript.
- Add scoped enhancements for a native two-step Elementor Pro vehicle-import form: optional vehicle criteria, model suggestions, a live summary and contact validation.
- Keep the import form in demo mode: client-side interception and server-side validation prevent email, submissions storage and other submit actions. No page template or delivery activation is included.

## 1.2.2 — 2026-10-21

- Create, upload, verify and publish releases by numeric API IDs, avoiding immediate draft rediscovery.
- Verify the remote tag commit and downloaded asset hashes before publication; preserve conflicting drafts.
- Normalize installation archive ordering and platform metadata.

## 1.2.1 — 2026-10-21

- Discover and resume unpublished release drafts through the authenticated release list; GitHub’s by-tag endpoint omits them.
- Declare the editor test transformer directly for clean CI installs.
- First completed installable GitHub release.

## 1.2.0 — 2026-10-21

- Publish the modular Hello Elementor child theme and synthetic component showcase.
- Include HCars Button for Elementor and Gutenberg, three additional Elementor widgets and configurable visual modules.
- Add native WordPress update discovery from stable GitHub Releases, with constrained metadata and cached checks.
- Build a runtime-only installation archive; publish releases only from explicit version tags.

This is the initial public release. Earlier local development history is not part of this repository.
