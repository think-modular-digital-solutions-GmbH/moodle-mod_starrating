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
  (`classes/local/rating_token.php`). The credential is rendered into filtered
  text, where output caching can reach it, and a scoped HMAC leaks far less
  than a session key. A missing or stale token falls back to a confirmation,
  which is a `POST` protected by the session key.
- **In the app, rating happens on the course tab, never through a link.**
  `CoreFilterDelegate` is not a site plugin delegate, and content link handlers
  are auto-registered only for site plugin *modules*, so a link in filtered text
  can only reach a browser — where `tool_mobile`'s six minute auto login lockout
  (`autologinkeygenerationlockout`) means every second tap asks for a login.
  Both were tried and rejected. The stars in text are therefore read only in the
  app (`core_useragent::is_moodle_app()` in `rating_widget`).
- **The app tab ships no JavaScript.** `db/mobile.php` registers a
  `CoreCourseOptionsDelegate` handler and the template uses the app's
  `core-site-plugins-call-ws` directive. `[params]` is an Angular expression
  built in `mobile::build_params()`, escaped for a single quoted string there
  and HTML-escaped again by the template. `displaydata` is mandatory or the app
  ignores the handler, and `displaydata.title` is a lang key that must also
  appear in the `lang` list of `$addons`.
- **`filter_rating_criterion` exists because the app has no token to read.**
  The filter records each criterion as it renders it; the tab and the `init`
  restrict list read from there.
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
- **Any URL ever handed to the app must be absolute** (`$url->out(false)`),
  never `out_as_local_url()`: `CoreUrl.toAbsoluteURL()` drops the parent path
  for URLs starting with `/`, which 404s on a Moodle in a subdirectory.

## Plan B, agreed 2026-08-17 — hold in reserve

If in-app rating through the course tab does not work either, **rework the
plugin completely as an activity module instead of a text filter**, to the
user's spec:

- An activity, not a filter. `mod_rating` replaces `filter_rating`.
- It renders its stars **directly on the course page**, the way a label renders
  its text inline, rather than only on its own view page.
- **The criterion is the name of the activity** in the course, so there is no
  token and no criterion string to parse.

That shape also solves the app problem by itself: the app auto-registers
`CoreContentLinksModuleIndexHandler` for site plugin modules, so a link to
`/mod/rating/view.php?id=X` opens natively in the app, and the module's own app
page can rate through `core-site-plugins-call-ws`.

Do not start this while the course tab is still being tried. Do not lose it
either — it is the agreed fallback, not a suggestion.

## Verification

There is no Moodle installation with a `config.php` on this machine, and no
`phpcs`/`composer`, so style checks and unit tests cannot be run here. Say so
rather than implying they passed.

A Moodle 4.5 checkout is available at `~/Documents/gitlab/moodle` and the
Moodle app source at `~/Documents/moodleapp/app` — **read them to verify API
signatures instead of guessing.** That is how the
`core_user\fields::get_sql()` argument order and the missing app filter
delegate were caught.

If app side code is ever added back, note that the app fetches site plugins only
on `CoreEvents.LOGIN`, so a logged in app never sees a new or changed
`db/mobile.php` until the user logs out and in again.

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
