# CLAUDE.md

Guidance for Claude Code when working in this repository.

## What this is

`filter_rating` — a Moodle text filter plugin. The token `{{ rating:criterion }}`
is replaced by a five star rating that works in the browser and in the Moodle
app, plus a site wide report over all ratings.

**The repository root is the plugin root.** It is deployed to `filter/rating/`
inside a Moodle installation, so `version.php` sits at the top level, not in a
subdirectory.

Target: Moodle 4.3 and later, developed against Moodle 4.5 LTS.

## Project conventions

- **The changelog is `CHANGES.md`**, not `CHANGELOG.md`. It follows
  [Keep a Changelog](https://keepachangelog.com) exactly — the filename is the
  only deviation from `tm-general:changelog`. Update the `Unreleased` section
  before every commit.
- `tm-general:git-workflow` applies: feature branches, reviewed merge requests,
  no direct commits to `main`, English Conventional Commits, no attribution
  trailers.
- Moodle coding style. Language strings live in `lang/en/` and `lang/de/`,
  never inline in code; both files are kept in sync.

## Decisions already made — do not re-litigate

- **A rating is keyed on course + criterion + user**, not on context. The same
  criterion used in several places of one course is therefore one rating, which
  is what makes the report's course and criterion averages meaningful. The
  context is stored for the Privacy API and for reference only.
- **Rating links are signed with an HMAC, not with a session key**
  (`classes/local/rating_token.php`). The link has to work in the browser
  session *and* in the separate session the app creates with auto login, which
  no session key can cover. The token is also the narrower credential and keeps
  session keys out of filtered text. A missing or stale token falls back to a
  confirmation, which is a `POST` protected by the session key.
- **The Moodle app has no site plugin delegate for text filters.**
  `CoreFilterDelegate` is not in the delegate list in the app's
  `siteplugins-init.ts` (checked against app 5.2), so a filter cannot ship
  app-side JavaScript. The stars therefore use `data-app-url`,
  `data-app-open-in="inappbrowser"` and `data-app-url-resume-action="refresh"`.
  Do not reintroduce `db/mobile.php` unless the app gains that delegate.
- **Both `classes/text_filter.php` and `filter.php` are shipped.** Moodle 4.5+
  uses the first, older releases the second, and core only falls back to
  `filter.php` when the new class is absent, so neither triggers a deprecation
  notice. The shared logic lives in `classes/local/token_processor.php`.
- **A rated star is `fa fa-star`, an unrated one `fa fa-star-o`.** That pair
  renders everywhere: FontAwesome 4 knows `fa-star-o` natively, FontAwesome 6
  maps it through Moodle's v4 shims (`.fa.fa-star-o` → regular family, weight
  400, in `theme/boost/scss/fontawesome/_shims.scss`), and the app resolves any
  `-o` suffix to its `regular` SVG library (`CoreIcons.fixFontAwesomeIconName`).
  Do not "fix" this back to two solid stars in different colours — that was the
  first attempt and it looked like five filled stars.
- **`data-app-url` must be absolute** (`$url->out(false)`), never
  `out_as_local_url()`. The app's `CoreUrl.toAbsoluteURL()` drops the parent
  path for URLs starting with `/`, which 404s on any Moodle installed in a
  subdirectory. The `href` next to it stays as it is.
- **The app does not load the plugin's `styles.css`.** Anything that must be
  visible in the app has to come from the markup itself, which is why the
  filled/unfilled distinction is a different icon and not a colour.

## Verification

There is no Moodle installation with a `config.php` on this machine, and no
`phpcs`/`composer`, so style checks and unit tests cannot be run here. Say so
rather than implying they passed.

A Moodle 4.5 checkout is available at `~/Documents/gitlab/moodle` and the
Moodle app source at `~/Documents/moodleapp/app` — **read them to verify API
signatures instead of guessing.** That is how the
`core_user\fields::get_sql()` argument order and the missing app filter
delegate were caught.

Against a real installation:

```bash
vendor/bin/phpcs --standard=moodle filter/rating
vendor/bin/phpunit --filter filter_rating
```

## Data protection

Ratings are personal data. The Privacy API provider in
`classes/privacy/provider.php` must stay in sync with `db/install.xml` — any
new column needs a metadata entry and, if user linked, coverage in export and
in all three deletion paths.
