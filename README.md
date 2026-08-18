# mod_starrating — Star rating activity for Moodle

An activity that puts five stars **directly on the course page**, the way a
label puts its text there. Everyone with the right permission gives one rating
and can change or withdraw it later.

**The name of the activity is what is being rated.** Add one activity per thing
you want feedback on — "Teaching quality", "Materials", "Pace of the course" —
and the name is what people see next to the stars. There is no token to write
and nothing to configure per criterion.

A site wide report shows every rating, filterable by course and activity, with
averages per course and per activity.

## Requirements

- Moodle 4.3 or later. Developed and reviewed against Moodle 4.5 LTS.
- Moodle app 4.x or later for the mobile behaviour described below.
- FontAwesome, which every Moodle theme based on Boost provides.

## Installation

1. Copy this repository to `mod/starrating` in your Moodle installation.
2. Visit *Site administration → Notifications* to install the database tables.

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
| Show the average | on | Shows the average and the number of ratings next to the stars |
| Allow withdrawing a rating | on | Shows a link that removes the rating the user gave |

Both defaults for new activities are set under *Site administration → Plugins →
Activity modules → Star rating*.

Users need `mod/starrating:rate`, which students, teachers and managers have by
default. Guests and users without it see the stars as a read only display.

## The reports

Two pages under *Site administration → Reports*, both behind
`mod/starrating:viewreports` (managers by default), each carrying the filter
over to the other:

- **Star rating averages** — filter by course, by activity or both, and group
  the averages by course and activity, by course, or by activity name across
  courses.
- **Star ratings by user** — every individual rating with the person who gave
  it, sortable, paged and downloadable in the usual Moodle formats.

The second page names users, which is why the capability carries
`RISK_PERSONAL` and is not granted to teachers by default.

## Moodle app

Being an activity is what makes the app work, and it is why this plugin is not
a text filter: **the app registers a content link handler for every site plugin
module**, so a link to `/mod/starrating/view.php` opens the activity natively
inside the app. No browser, and no second login.

- On the course page the stars are rendered inline through `coursepagemethod`,
  showing the current rating and the average. The app renders that through
  `core-format-text`, which cannot run app directives, so the stars there are a
  link into the activity.
- On the activity page the stars carry the app's own
  `core-site-plugins-call-ws` directive:

  ```html
  <a core-site-plugins-call-ws name="mod_starrating_set_rating"
     [params]="{cmid: 12, rating: 3}" refreshOnSuccess="true">
  ```

  Tapping a star calls the web service and refreshes the page. **The plugin
  ships no JavaScript to the app.**

So the app is: tap the stars on the course page, the activity opens in the app,
rate with one tap. On the web it stays a single click on the course page.

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
| `mod_starrating_set_rating` | write | Store the rating of the current user for an activity |
| `mod_starrating_get_rating` | read | Read the current user rating, the average and the number of ratings |

Both are part of the official mobile service and flagged for AJAX.

## Security

The stars on the course page are `GET` links that write, and the credential
they carry is rendered into the course page where output caching can reach it.
Rather than a session key, each link is signed with an HMAC bound to the user,
the course module and that one number of stars
(`\mod_starrating\local\rating_token`). A leaked link allows nothing but
setting that one rating of that one user.

If the signature is missing or stale the endpoint does not fail, it asks for
confirmation, and that confirmation is a `POST` protected by the session key.
Return URLs are cleaned with `PARAM_LOCALURL`, so the endpoint cannot be used as
an open redirect.

## Data protection

Stored per rating: the user, the activity, the number of stars and the
timestamps. Nothing leaves the Moodle site.

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
- The five stars form a labelled group, and each link has an accessible name
  saying what it will do ("Rate Teaching quality with 3 of 5 stars").
- A rated star is a filled icon, an unrated one an outline, so the rating is
  carried by shape and not by colour. It is repeated as visually hidden text.
  The star colours reach at least 3:1 against a white background.
- In the app the stars have a touch target above the WCAG 2.2 minimum.

This has not been verified with assistive technology or with users. Before
rolling it out, have the widget checked by someone testing with a screen
reader, and check the star colours against your theme.

## Development

```bash
# Code style, from the Moodle root, with moodlehq/moodle-cs installed
vendor/bin/phpcs --standard=moodle mod/starrating

# Unit tests, after php admin/tool/phpunit/cli/init.php
vendor/bin/phpunit --filter mod_starrating
```

Neither could be run in the environment this plugin was written in.

## License

GNU GPL v3 or later, like Moodle itself.
