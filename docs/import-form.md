# Native import form demo

This module enhances a native Elementor Pro Form widget. It does not register
another widget, import a page, publish content or configure a delivery service.
The repository includes integration code and compiled assets; the client's
Elementor document and media remain outside this repository.

## Integration contract

- Native form ID: `hc_import_request`. The server also recognizes the form name
  `HERIZON egyedi autókeresés` as a demo form.
- Section wrapper: `.hc-import`; native Elementor steps remain responsible for
  step layout and navigation.
- Contact field IDs: `first_name`, `last_name`, `email`, `phone`, optional `city`
  and `message`. Both names and at least one of email/phone are required.
- Vehicle field IDs include `brand`, `model`, `year_from`, `year_to`, `budget`,
  `budget_scope`, `fuel`, `transmission`, `mileage`, `power`, `body`, `drive`,
  `timing`, `color` and `requirements`. Vehicle criteria are optional.
- `.hc-import__summary` holds optional `[data-summary]` text targets; the summary
  uses text content, without interpreting entered data as HTML.
- A `.hc-import__demo` notice explains that the demo does not send or save the
  request. Keep that notice visible in any page using this integration.

The script supplies model suggestions without restricting free text, numeric and
contact validation, summary updates, an advanced-fields disclosure and input
autocomplete hints. PHP validates recognized submissions independently. Assets
load when the native form with the expected ID renders.

## Demo boundary

The client intercepts submission for the recognized section. Independently, the
server adds a validation error on every recognized form submission, before
Elementor runs any submit action. It therefore blocks email, Elementor
Submissions and other configured actions even if JavaScript is disabled or
bypassed. No delivery switch is provided. Contact details are not written to
browser storage by this module.

Do not rename the form ID/name to bypass the demo boundary. Real delivery needs
a separately reviewed implementation, confirmed recipient, privacy information,
retention and antispam configuration, and verification of the intended actions.

## Source and checks

- `inc/integrations/import-form.php`: recognition, sanitization, validation and
  unconditional demo rejection.
- `src/js/import-form.js` and `src/scss/import-form.scss`: scoped presentation and
  progressive enhancement.
- `tests/php/import-form.php`: validation boundaries and demo action blocking.

Run the standard `pnpm lint`, `pnpm test` and `pnpm build` commands. Reproducing
the native editor and full interaction requires Elementor Pro and a page that
implements this contract. The synthetic component demo is a separate artifact.
