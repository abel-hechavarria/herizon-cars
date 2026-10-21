# LocalWP → GitHub → WordPress updates

The repository root is the `herizon-cars` theme folder. Work on that folder inside LocalWP; WordPress core, uploads, databases, commercial plugins and local environment files stay outside Git. Clone into an empty `wp-content/themes/herizon-cars` directory on another development machine.

Commits record development. A **stable GitHub Release with the installation ZIP** makes a version available to the client. Publishing code does not modify production pages or install the update for the customer.

## Develop and publish source

1. Test changes in LocalWP with Hello Elementor and the required editors installed.
2. Run `pnpm install --frozen-lockfile`, `pnpm lint`, `pnpm test` and `pnpm build`.
3. If compiled filenames changed, regenerate the synthetic demo from this checkout: `wp --path=/path/to/wordpress eval-file scripts/render-portfolio.php`.
4. Review `git diff`, commit the intended source and compiled changes, then push `main`. The quality workflow checks PHP 8.2 and 8.5.

Use a topic branch and pull request when a review is useful. Source commits alone do not announce a customer update.

## Publish an installable version

1. Choose a new stable `X.Y.Z`. Update `package.json`, `style.css`, `inc/bootstrap.php` and the first line of `readme.txt` together. Add its entry in `CHANGELOG.md`; update WordPress/PHP requirement headers if necessary.
2. Build and test, run `pnpm release:check` and `pnpm package`, then commit and push the reviewed changes.
3. Explicitly tag that commit and push the tag:

```sh
git tag -a vX.Y.Z -m "Herizon Cars X.Y.Z"
git push origin vX.Y.Z
```

The tag workflow checks the version, tests and committed build, creates `herizon-cars-X.Y.Z.zip` plus `SHA256SUMS`, uploads them to a draft and then publishes the release. A failed workflow does not advertise a partially uploaded draft. Do not replace an existing release asset; fix a defect in a new version.

A rerun can resume an interrupted draft only when its commit, notes and existing artifact hashes match. Published releases, conflicting drafts or different asset bytes are preserved and require inspection. No force-push or asset replacement is part of the workflow.

Use the named ZIP from **Releases** for installation. GitHub's automatically generated source ZIP is a development checkout and may have the wrong theme directory name.

## Client update behavior

Install version 1.2.0 or newer once on the customer site to bootstrap this updater. Keep Hello Elementor installed and Herizon Cars active. The theme's `Update URI` connects the native WordPress update check to this exact public repository. No GitHub token is placed in WordPress.

WordPress's scheduled checks discover newer stable releases. The GitHub result is cached for one hour; failures for five minutes. An immediate check may therefore still use a cached result. Use **Dashboard → Updates → Check again** after the cache window. Drafts, prereleases, tags without releases and releases without the expected uploaded ZIP/requirements marker are ignored. Requirements are generated from the theme headers in an HTML comment in the release body; preserve it when editing release notes.

The administrator chooses **Update now**. The integration does not enable automatic installation; WordPress's own automatic-update preference remains under administrator control. Check a recoverable backup and test a release on staging before installing it on production. Update checks run only while this theme is active; switching themes also unloads its updater. Hello Elementor and Elementor retain their own update mechanisms.

The client needs outbound HTTPS access to GitHub's API and release downloads. An outage or public API rate limit delays discovery; it does not break frontend rendering. The generated checksum supports manual artifact verification; it is not a separate cryptographic signing service.

Reference: [WordPress native theme update filter](https://developer.wordpress.org/reference/hooks/update_themes_hostname/) and [GitHub Releases API](https://docs.github.com/en/rest/releases/releases#get-the-latest-release).
