# CLAUDE.md

Guidance for Claude Code when working in this repository.

## What this is

`mod_starrating` — a Moodle **activity module**. A row of stars rendered directly
on the course page, the way a label renders its text. The **name of the activity
is the criterion**, so there is no token and nothing to parse. How long the scale
is, 2 to 10 stars, is a per activity setting. Plus a course report for teachers
and a site wide report over all ratings.

**The repository root is the plugin root.** It is deployed to `mod/starrating/`
inside a Moodle installation, so `version.php` sits at the top level, not in a
subdirectory. It lives on GitHub as
`think-modular-digital-solutions-GmbH/moodle-mod_starrating` since 2026-10-20;
the old GitLab repository `development/moodle/rating` only says that it moved.

Target: Moodle 4.3 and later, developed against Moodle 4.5 LTS.

## Project conventions

- **The changelog is `CHANGES.md`**, not `CHANGELOG.md`. It follows
  [Keep a Changelog](https://keepachangelog.com) exactly — the filename is the
  only deviation from `tm-general:changelog`. Update the `Unreleased` section
  before every commit.
- **Commit directly on `main` and push, automatically, without being asked.**
  The user set this on 2026-08-18 and it overrides the feature branch and merge
  request rule of `tm-general:git-workflow`. The rest of that skill still
  applies: English Conventional Commits, no attribution trailers, a releasable
  `main`.
- Moodle coding style. Language strings live in `lang/en/` and `lang/de/`,
  never inline in code; both files are kept in sync.
- **The language file is `lang/<lang>/starrating.php`, not
  `mod_starrating.php`.** Activity modules are the exception to the frankenstyle
  filename rule: `upgrade_plugins_modules()` checks
  `lang/en/<directory name>.php` and throws `plugin_defective_exception` if it
  is missing. `get_string(..., 'mod_starrating')` still resolves to it.

## Decisions already made — do not re-litigate

- **The module cannot be called `rating`.** Moodle core owns the `rating` table
  (`lib/db/install.xml`, the core Ratings API), and an activity's main table
  must be named after the module. Hence `starrating`.
- **A rating is keyed on activity + user.** The criterion is the activity name,
  so the site report groups by `starrating.name`. The **course** report groups by
  activity instead, because one course can hold two activities with the same
  name, and it left joins the ratings so an unrated activity still gets a row.
- **The scale length lives in `starrating.maxstars`, never in a constant.**
  `rating_manager::MAX_STARS` is gone; there are `MIN_MAX_STARS`,
  `DEFAULT_MAX_STARS` and `ABSOLUTE_MAX_STARS`, and `get_max_stars($instanceid)`
  for the actual value. Anything coming from a form, a backup or a web service
  goes through `normalise_max_stars()` first.
- **Averages are computed as `AVG(rating * 1.0000 / maxstars)`, not
  `AVG(rating)`.** A report row can span activities on different scales, where
  four of five and eight of ten are the same judgement and adding them as stars
  is not. `report::read_average()` reports the shared scale, or 0 when the group
  mixes scales, and the renderer then falls back to a percentage. For a single
  scale the figure is identical to the plain average, so nothing was given up.
- **A scale can be lengthened freely and shortened only to the highest rating
  given** (`mod_form.php::validation()`). Shortening past it would leave stored
  ratings that no row of stars can show.
- **It was a text filter until 2026-08-18 and must not go back.** A link inside
  filtered text can only be claimed by a core content link handler, which a
  filter cannot register, or be handed to a browser — where `tool_mobile`'s six
  minute auto login lockout (`autologinkeygenerationlockout`) makes every second
  tap ask for a login. The in-app browser, an app side JavaScript hook and a
  course tab were all tried and all rejected by the user.
- **Being a module is what makes the app work.** The app auto-registers
  `CoreContentLinksModuleIndexHandler` for site plugin modules
  (`siteplugins-init.ts`), so `/mod/starrating/view.php?id=X` opens natively in
  the app. Do not replace that link with anything else.
- **The activity page is declarative, the course page needs JavaScript.** The
  activity page template uses the app's `core-site-plugins-call-ws` directive.
  The course page cannot: `coursepagemethod` output goes through
  `core-format-text` (`module.handlerData.title` in `core-course-module.html`),
  so no app directive runs there, `hascustomcmlistitem` has no consumer in app
  5.2, and `updateModuleCourseContent()` is exposed only to plugin JS
  (`plugin-content.ts`). Hence `mobile/init.js`, shipped by the `init` handler,
  which handles the tap in the capture phase and repaints in place. The user
  requires one tap on the course page, so **do not replace those stars with a
  link to the activity** — that was tried and rejected.
- **`mobile/init.js` and `templates/mobile_course_page.mustache` are a
  contract.** The script keys off `.starrating-app-coursepage`,
  `data-cmid`, `data-courseid`, `data-canrate`, `data-userrating` and
  `data-value`, plus `textarea[name="feedback"]`, `.starrating-feedback` and
  `[data-action="savefeedback"]`, and repaints by swapping the `ion-icon` `src`
  between the solid and regular star assets.
- **The app course card opens the activity on a tap and on Enter or Space, and
  cancels those keys** (`ariaButtonClick` → `CoreDom.initializeClickableElementA11y`
  on the title span). `mobile/init.js` therefore stops, in the capture phase and
  without cancelling, every click and key event inside `.starrating-feedback`,
  or nobody could type a space or a line break there, and stops `keyup` on a
  star, which would otherwise open the activity after rating.
- **Per user content goes in `starrating_cm_info_view()`, never in
  `starrating_get_coursemodule_info()`.** The latter feeds the course cache,
  which is shared between users.
- **A rated star is `fa fa-star`, an unrated one `fa fa-star-o`.** That pair
  renders everywhere: FontAwesome 4 knows `fa-star-o` natively, FontAwesome 6
  maps it through Moodle's v4 shims (`.fa.fa-star-o` → regular family, weight
  400, in `theme/boost/scss/fontawesome/_shims.scss`), and the app resolves any
  `-o` suffix to its `regular` SVG library (`CoreIcons.fixFontAwesomeIconName`).
  In the app's own templates use `fas-star` / `far-star` on `<ion-icon>`.
  Do not "fix" this back to two solid stars in different colours — that was an
  early attempt and it looked like five filled stars.
- **Rating links are signed with an HMAC, not a session key**
  (`classes/local/rating_token.php`). The credential is rendered into the course
  page, where output caching can reach it, and a scoped HMAC leaks far less. A
  missing or stale token falls back to a confirmation, a `POST` protected by the
  session key.
- **Any URL handed to the app must be absolute** (`$url->out(false)`), never
  `out_as_local_url()`: `CoreUrl.toAbsoluteURL()` drops the parent path for URLs
  starting with `/`, which 404s on a Moodle in a subdirectory.
- **Text feedback turns the stars into the submit buttons of one `POST`
  form** (`allowfeedback`), so the text travels with the click on a star and
  rating stays one click. A form carries one token for all its buttons, so it
  is signed with `rating_token::generate_for_form()`, bound to user and course
  module but not to a number of stars.
- **The feedback field is a `<textarea>`, and that is what keeps Enter from
  rating.** Enter in a single line `<input>` presses the first submit button of
  its form, which is star 1. Do not turn it back into an input without guarding
  against that. The limit is 255 characters (`MAX_FEEDBACK_LENGTH`, `maxlength`,
  the `char(255)` column); raising it needs a `text` column and an upgrade step.
- **Feedback belongs to the rating.** `set_rating()` takes `null` to keep the
  stored text (the app course page sends stars only), `''` to remove it, and
  ignores it when the activity does not ask for feedback. Withdrawing the
  rating removes the text.
- **In the app the feedback is bound from `CONTENT_OTHERDATA`, never written
  into the template.** The app compiles site plugin templates with Angular, so
  user text containing `{{ }}` would be evaluated. It is JSON wrapped
  (`{"text": ...}`) because the app parses any otherdata value starting with
  `{` or `[`. The stars read the field through call-ws `form="..."`.

## History worth knowing

Plan B — this rework — was executed on 2026-08-18. The filter version and every
app workaround built for it are gone; the conversation history has them if they
are ever needed again. The short version: nothing short of an activity makes the
app open plugin content natively.

## Verification

**Everything can be run and must be run.** `~/Documents/behat` holds a Docker
Moodle 4.5 that mounts this repository at `mod/starrating`, so tests and style
checks run against the working tree. Its `README.md` has the details; the short
version:

```bash
~/Documents/behat/up.sh          # once per boot, idempotent
~/Documents/behat/behat.sh       # 36 acceptance scenarios, ~90 s
~/Documents/behat/phpunit.sh     # 31 unit tests, ~7 s
~/Documents/behat/phpcs.sh       # Moodle coding style, silence means clean
```

Do not claim a check passed without having run it, and never fix style by eye
when `phpcs.sh` will say exactly what is wrong.

A Moodle 4.5 checkout is available at `~/Documents/gitlab/moodle` and the
Moodle app source at `~/Documents/moodleapp/app` — **read them to verify API
signatures instead of guessing.** That is how the
`core_user\fields::get_sql()` argument order, the missing app filter delegate,
the core `rating` table collision and the six minute auto login lockout were all
caught.

**Always tell the user what the app needs for a change to take effect**, and say
so even when the answer is nothing. Standing instruction from 2026-08-18.

| Changed | App action |
| :--- | :--- |
| `db/mobile.php`, `styles.css`, anything in `$addons` | Log out and log in — site plugins are only fetched on `CoreEvents.LOGIN` |
| `classes/output/mobile.php`, `mobile_*` templates | Pull to refresh, or clear the app cache — `tool_mobile_get_content` results are cached |
| Web service code only | Nothing; the rating calls run with `getFromCache: 0` |
| `version.php` bumped | Moodle *Notifications* and purge caches first, then whatever the app needs |

Inside a Moodle installation of your own the same two checks are:

```bash
vendor/bin/phpcs --standard=moodle mod/starrating
vendor/bin/phpunit --filter mod_starrating
```

## Data protection

Ratings are personal data. The Privacy API provider in
`classes/privacy/provider.php` must stay in sync with `db/install.xml` — any
new column needs a metadata entry and, if user linked, coverage in export and
in all three deletion paths.
