# mod_starrating — Star rating activity for Moodle

An activity that puts a row of stars **directly on the course page**, the way a
label puts its text there. Everyone with the right permission gives one rating
and can change or withdraw it later. How many stars there are is set per
activity, anything from 2 to 10.

**The name of the activity is what is being rated.** Add one activity per thing
you want feedback on — "Teaching quality", "Materials", "Pace of the course" —
and the name is what people see next to the stars. There is no token to write
and nothing to configure per criterion.

Teachers get a report for their own course; a site wide report shows every
rating, filterable by course and activity, with averages per course and per
activity.

## Requirements

- Moodle 4.3 to 5.3. Tested on Moodle 4.5 LTS (PHP 8.3) and Moodle 5.3
  (PHP 8.4, PHPUnit 11): the unit tests, the Behat features and the coding
  style check pass on both. Moodle 4.3, 4.4, 5.0, 5.1 and 5.2 are inside the
  supported range but have not been tested.
- Moodle app 4.x or later for the mobile behaviour described below.
- FontAwesome, which every Moodle theme based on Boost provides.

## Installation

- **From a release:** download `mod_starrating-<version>.zip` from the
  [GitHub releases](https://github.com/think-modular-digital-solutions-GmbH/moodle-mod_starrating/releases)
  and upload it under *Site administration → Plugins → Install plugins*. The ZIP
  has the `starrating` folder Moodle expects at its root; the "Source code"
  archives GitHub adds to every release do not, and do not install as they are.
- **From git:** clone this repository into `mod/starrating` (from Moodle 5.1 on,
  `public/mod/starrating`), naming the target directory, then visit
  *Site administration → Notifications*:

  ```bash
  git clone -b MOODLE_503_STABLE https://github.com/think-modular-digital-solutions-GmbH/moodle-mod_starrating.git mod/starrating
  ```

There are no steps after the installation. The activity is ready under *Add an
activity or resource → Star rating*.

The directory name has to be `starrating`. The module cannot be called `rating`,
because Moodle core already owns a `rating` table.

## Usage

*Add an activity or resource → Star rating*, and give it the name of the thing
being rated. The stars appear on the course page under that name. Nobody has to
open the activity to rate: one click on a star stores the rating and returns to
the course page.

The activity page itself shows the same stars and the description, and is what
the Moodle app opens.

| Setting | Default | Effect |
| :--- | :--- | :--- |
| Number of stars | 5 | How many stars people choose from, 2 to 10 |
| Show the average | on | Shows the average and the number of ratings next to the stars |
| Allow withdrawing a rating | on | Shows a link that removes the rating the user gave |
| Ask for text feedback | off | Shows a text area below the stars for a short comment |

All four defaults for new activities are set under *Site administration →
Plugins → Activity modules → Star rating*.

With *Ask for text feedback* on, people can type up to 255 characters, line
breaks included, before they click a star, and the text is sent together with
the stars — rating is still one click. A *Save feedback* button changes the text
later without touching the stars. Enter in the text area starts a new line, so
it never submits a rating by accident. The limit is the `maxlength` of the
field in the browser and in the app, and the server cuts anything longer to 255
characters, which is also the size of the database column. The text
belongs to the rating: it is only stored while there are stars, and withdrawing
the rating removes it as well. Turning the setting off hides the field but keeps
what was already given.

The scale of an activity that has already been rated can be lengthened at any
time. It can be shortened only down to the highest rating anybody has given: a
rating of eight has nowhere to go on a scale of five, so the form refuses it
rather than hide or truncate what people said.

Users need `mod/starrating:rate`, which students, teachers and managers have by
default. Guests and users without it see the stars as a read only display.

## The reports

### For teachers

*Star ratings* in the course menu, and in the menu of every star rating
activity, behind `mod/starrating:viewcoursereport` — teachers, editing teachers
and managers by default. It covers one course:

- the average per activity, including the activities nobody has rated yet, which
  is what tells a teacher that feedback is still outstanding rather than bad;
- every individual rating with the person who gave it and the text feedback
  that came with it, sortable, paged and downloadable.

Opened from an activity it arrives filtered to that activity.

### For administrators

Two pages under *Site administration → Reports*, both behind
`mod/starrating:viewreports` (managers by default), each carrying the filter
over to the other:

- **Star rating averages** — opens on the number of ratings and the average per
  course, and every course name leads to that course's own star rating report.
  Filter by course, by activity or both, and group the averages by course, by
  course and activity, or by activity name across courses.
- **Star ratings by user** — every individual rating with the person who gave
  it, sortable, paged and downloadable in the usual Moodle formats.

Both capabilities name users, which is why they carry `RISK_PERSONAL`.
`mod/starrating:viewreports` reaches every course on the site, which is why it
is not granted to teachers.

Because every activity has its own scale, an average taken over activities whose
scales differ in length is reported as a share of the scale — "Average 76% of the
scale from 12 ratings" — instead of as stars. Four of five and eight of ten are
the same judgement, and there is no row of stars that can honestly stand for
both.

## Moodle app

Being an activity is what makes the app work, and it is why this plugin is not
a text filter: **the app registers a content link handler for every site plugin
module**, so a link to `/mod/starrating/view.php` opens the activity natively
inside the app. No browser, and no second login.

- On the course page the stars are rendered inline through `coursepagemethod`,
  showing the current rating and the average. The app renders that through
  `core-format-text`, where no app directive can run, so `mobile/init.js` handles
  the tap instead: one tap saves the rating and repaints the stars in place,
  without opening anything.
- On the activity page the stars carry the app's own
  `core-site-plugins-call-ws` directive:

  ```html
  <a core-site-plugins-call-ws name="mod_starrating_set_rating"
     [params]="{cmid: 12, rating: 3}" refreshOnSuccess="true">
  ```

  Tapping a star calls the web service and refreshes the page. An activity that
  asks for text feedback shows the field here, inside a named form, and every
  star names that form (`form="starrating-feedback-12"`), so the directive sends
  the text along with the stars.
- The course page shows the field too, as plain markup like its stars:
  `mobile/init.js` sends its text with every tap on a star, and its *Save
  feedback* button saves the text with the stars already given. The script keeps
  taps and keys inside the field away from the course card, which would
  otherwise open the activity and swallow every space and line break typed.

So in the app rating is one tap on the course page, the same as one click on the
web, and the activity page is there for anyone who opens it.

### After installing

The app fetches site plugins **only when a user logs in** (`CoreEvents.LOGIN`):

1. On the site: *Site administration → Notifications*, then purge caches —
   `tool_mobile` caches the handler definitions.
2. In the app: **log out and log in again.** Clearing the app cache is neither
   necessary nor sufficient.

If the activity does not appear in the app at all after that, site plugins are
not loading on that site, which is a site level problem rather than a plugin
one.

## Web services

| Function | Type | Purpose |
| :--- | :--- | :--- |
| `mod_starrating_set_rating` | write | Store the rating of the current user for an activity, optionally with a text feedback |
| `mod_starrating_get_rating` | read | Read the current user rating, the average and the number of ratings |

Both are part of the official mobile service and flagged for AJAX.

## Security

The stars on the course page are `GET` links that write, and the credential
they carry is rendered into the course page where output caching can reach it.
Rather than a session key, each link is signed with an HMAC bound to the user,
the course module and that one number of stars
(`\mod_starrating\local\rating_token`). A leaked link allows nothing but
setting that one rating of that one user.

An activity that asks for text feedback renders its stars as the submit buttons
of one `POST` form, which can carry only one token for all of them. That token
is bound to the user and the course module but to no number of stars, so it
allows no more than all the star links of the same page taken together.

If the signature is missing or stale the endpoint does not fail, it asks for
confirmation, and that confirmation is a `POST` protected by the session key.
Return URLs are cleaned with `PARAM_LOCALURL`, so the endpoint cannot be used as
an open redirect.

## Data protection

Stored per rating: the user, the activity, the number of stars, the text
feedback if the activity asks for one, and the timestamps. Nothing leaves the
Moodle site. The feedback is free text, so people can write anything into it,
personal data included; it is kept out of URLs and of the event log.

A full Privacy API provider is implemented, so ratings are included in data
exports and are removed by deletion requests — per user, per activity, and for
a list of users. Deleting the activity or the course removes its ratings, and
the course reset form offers to delete them.

An activity with few raters can make an individual rating identifiable in the
report; keep that in mind when naming activities.

## Accessibility

Built against WCAG 2.2 AA:

- Every star is a real link, keyboard reachable and operable, with a visible
  focus outline.
- The stars form a labelled group, and each link has an accessible name saying
  what it will do ("Rate Teaching quality with 3 of 5 stars"), naming the scale
  of that activity.
- A rated star is a filled icon, an unrated one an outline, so the rating is
  carried by shape and not by colour. It is repeated as visually hidden text.
  The star colours reach at least 3:1 against a white background.
- In the app the stars have a touch target above the WCAG 2.2 minimum.
- The feedback text area has a visible label, and pressing Enter in it starts
  a new line, never a rating the user did not choose.

This has not been verified with assistive technology or with users. Before
rolling it out, have the widget checked by someone testing with a screen
reader, and check the star colours against your theme.

## Support

Report bugs and ask for features in the
[issue tracker](https://github.com/think-modular-digital-solutions-GmbH/moodle-mod_starrating/issues).
This README is the documentation.

## Development

```bash
# Code style, from the Moodle root, with moodlehq/moodle-cs installed
vendor/bin/phpcs --standard=moodle mod/starrating

# Unit tests, after php admin/tool/phpunit/cli/init.php
vendor/bin/phpunit --testsuite mod_starrating_testsuite

# Acceptance tests, after php admin/tool/behat/cli/init.php
vendor/bin/behat --config <behat_dataroot>/behatrun/behat/behat.yml --tags @mod_starrating
```

From Moodle 5.1 on the plugin lives in `public/mod/starrating` and the test
tool scripts in `public/admin/tool/`; `vendor/` stays in the root.

The unit tests carry both `@covers` and `#[CoversClass]`, because PHPUnit 9 on
Moodle 4.x reads the first and PHPUnit 11 on Moodle 5.x deprecates it in favour
of the second.

### Continuous integration

`.github/workflows/ci.yml` runs [moodle-plugin-ci](https://moodlehq.github.io/moodle-plugin-ci/)
on every push and pull request: PHP lint, the Moodle code checker and PHPDoc
checker with no warnings allowed, plugin validation, upgrade savepoints,
Mustache lint, Grunt, PHPUnit and Behat. It covers Moodle 4.3, 4.5 and 5.3 on
PostgreSQL and MariaDB, with the lowest and highest PHP each supports. The app
templates (`templates/mobile_*`) are left out of the HTML validation, through
`templates/.mustachelintignore`, because the app compiles them as Angular
templates.

### Branches

Following Moodle's convention, there is one `MOODLE_XY_STABLE` branch per range
of Moodle versions that share the same code, named after the highest of them:

| Branch | Moodle | Holds |
| :--- | :--- | :--- |
| `MOODLE_503_STABLE` | 4.3 to 5.3 | The latest release |
| `main` | the same, for now | Development; every release is made from here |

The release workflow moves the stable branch forward to every new release, and
creates the next one, `MOODLE_504_STABLE` for example, as soon as a release
raises the highest version in `$plugin->supported`. A stable branch never gets
commits of its own; a fix for an older range goes on `main` first, or, once
the code of the ranges has drifted apart, on that range's branch and is
released from there by hand.

### Releasing

Releases are automatic. To release, in one commit on `main`:

1. Set `$plugin->release` in `version.php` to the new SemVer version, and raise
   `$plugin->version` if it has not been raised since the last release.
2. Move the *Unreleased* entries of `CHANGES.md` under a heading
   `## [<release>] - <date>`.

Once CI has passed on that commit, `.github/workflows/release.yml` tags it
`v<release>`, creates the GitHub Release with that section of `CHANGES.md` as
its notes and the installable ZIP attached, and submits the version to the
Moodle Marketplace. It refuses, without tagging, a release that is not SemVer,
whose `$plugin->version` is not above the previous one, or that has no section
in `CHANGES.md`. A push that leaves `$plugin->release` alone releases nothing.
After the release it moves `MOODLE_<highest supported>_STABLE` to it (see
*Branches*); if it cannot, the release stands and the run shows a warning with
the command to move the branch by hand.

The Marketplace step runs only while the repository variable
`MARKETPLACE_RELEASE` is `true`, and it needs the secret
`MOODLE_MARKETPLACE_TOKEN`, an API token from
<https://marketplace.moodle.com/account/security>. To submit an existing tag
again, run the *Release* workflow by hand with that tag.

The ZIP is built with `git archive`, so whatever `.gitattributes` marks
`export-ignore` stays out of it: the workflows and `CLAUDE.md`.

The Behat features in `tests/behat/` cover rating on the course page and on the
activity page, changing and withdrawing, the scale, text feedback, both reports
and the read only display. Ratings can be seeded with
`the following "mod_starrating > ratings" exist:`, including a `feedback`
column.

## License

GNU GPL v3 or later, like Moodle itself.
