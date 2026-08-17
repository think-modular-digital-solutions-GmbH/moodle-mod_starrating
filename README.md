# filter_rating — Star rating filter for Moodle

Drops a five star rating widget anywhere text is displayed in Moodle by writing
a token into the text:

```text
{{ rating:teaching quality }}
```

The token is replaced by five FontAwesome stars. Every star is a link to an
endpoint that stores the rating and sends the user back to the page they came
from. The links also work in the Moodle app. A site wide report shows all
ratings and the averages per course and per criterion.

## Requirements

- Moodle 4.3 or later. Developed and reviewed against Moodle 4.5 LTS.
  The filter ships both the current `classes/text_filter.php` (Moodle 4.5 and
  later) and the legacy `filter.php`, so it runs on either API without a
  deprecation notice.
- Moodle app 4.x or later for the mobile behaviour described below.
- FontAwesome, which every Moodle theme based on Boost provides.

## Installation

1. Copy this repository to `filter/rating` in your Moodle installation.
2. Visit *Site administration → Notifications* to install the database table.
3. Enable the filter under *Site administration → Plugins → Filters →
   Manage filters*, set it to **On**, and make sure it is applied to
   *Content* (and to *Content and headings* if you want tokens in headings).

## Usage

Write the token into any text Moodle passes through the filters: a course or
section summary, a label, a page, an activity description, a forum post, a
block, and so on.

```text
{{ rating:teaching quality }}
{{rating:materials}}
{{ Rating : pace of the course }}
```

- The keyword is case insensitive, spaces around it are optional.
- The criterion may contain spaces and is capped at 100 characters. It is
  trimmed, stripped of markup and collapsed to single spaces before use.
- A token without a usable criterion is left in the text untouched.

### What a rating belongs to

A rating is stored once per **course, criterion and user**. The same criterion
used in several places of one course is therefore one rating, which is what
makes the course and criterion averages in the report meaningful. Tokens
outside any course (front page, site level pages) count towards the site
course.

Users need the `filter/rating:rate` capability, which students, teachers and
managers have by default. Guests and users without the capability see the
stars as a read only display.

## Settings

*Site administration → Plugins → Filters → Star rating*

| Setting | Default | Effect |
| :--- | :--- | :--- |
| Show the average | on | Shows the average and the number of ratings next to the stars |
| Allow withdrawing a rating | on | Shows a link that removes the rating the user gave |

## The report

*Site administration → Reports → Star ratings*, capability
`filter/rating:viewreports` (managers by default).

- Filter by course, by criterion, or both.
- Group the averages by course and criterion, by course, or by criterion.
- Below the averages, every individual rating is listed with the user who gave
  it, sortable, paged and downloadable in the usual Moodle formats.

Because the individual list names users, the capability carries `RISK_PERSONAL`
and is not granted to teachers by default.

## Moodle app

The filter runs on the server, so the app receives the finished star markup
inside any formatted text. Each star therefore carries a `data-app-url` in
addition to its `href`:

- In a browser the `href` is followed: the rating is stored and the user is
  redirected back to the page they rated on.
- In the app the `href` is ignored and `data-app-url` is opened in the app
  browser with auto login. That variant of the endpoint reports the result and
  asks the user to close the window. Because the star also carries
  `data-app-url-resume-action="refresh"`, the app refreshes the content when
  the window closes, so the new rating appears.

The app cannot do better than this today: the Moodle app has no site plugin
delegate for text filters (`CoreFilterDelegate` is not in the list of delegates
site plugins may register, as of app 5.2), so a filter cannot ship JavaScript
that saves the rating inline. Should that change, the web services below are
already in place to do it without any server side change.

## Web services

Both functions are part of the official mobile service and are also flagged for
AJAX, so a custom app or a custom front end can use them directly.

| Function | Type | Purpose |
| :--- | :--- | :--- |
| `filter_rating_set_rating` | write | Store the rating of the current user for a criterion in a context |
| `filter_rating_get_rating` | read | Read the current user rating, the average and the number of ratings |

## Security

The star links are `GET` links that write data, and they must work in two
different sessions: the browser session, and the separate session the app
creates when it opens the link with auto login. A session key cannot cover
both, so each link is signed with an HMAC that is bound to the user, the
context, the criterion and that one number of stars
(`\filter_rating\local\rating_token`).

That is deliberately narrower than a session key: a leaked link allows nothing
but setting that one rating of that one user, and no session key ever ends up
inside filtered text, where it could be picked up by output caching. If the
signature is missing or stale the endpoint does not fail, it asks for
confirmation, and that confirmation is a `POST` protected by the session key.

Return URLs are cleaned with `PARAM_LOCALURL`, so the endpoint cannot be used
as an open redirect.

## Data protection

The plugin stores, per rating: the user, the course, the context, the
criterion, the number of stars and the timestamps. Nothing leaves the Moodle
site.

A full Privacy API provider is implemented, so ratings are included in data
exports and are removed by deletion requests — per user, per context, and for a
list of users. Deleting a course context removes the ratings stored in it.

Keep in mind that a criterion with few raters can make an individual rating
identifiable in the report; treat the criterion names accordingly.

## Accessibility

The widget was built against WCAG 2.2 AA:

- Every star is a real link, so it is reachable and operable by keyboard, with
  a visible focus outline.
- The five stars form a labelled group, and each link has an accessible name
  stating what it will do ("Rate teaching quality with 3 of 5 stars").
- The current rating is repeated as visually hidden text in a polite live
  region, so it is never conveyed by colour alone. The star colours reach at
  least 3:1 against a white background.
- The star icons themselves are `aria-hidden`, the report tables use header
  cells and a caption.

This has not been verified with assistive technology or with users. Before
rolling the plugin out, have the widget checked by a person testing with a
screen reader, and check the star colours against your theme's background if
you use a dark or coloured theme.

## Development

```bash
# Code style, from the Moodle root, with moodlehq/moodle-cs installed
vendor/bin/phpcs --standard=moodle filter/rating

# Unit tests, after php admin/tool/phpunit/cli/init.php
vendor/bin/phpunit --filter filter_rating
```

Neither could be run in the environment this plugin was written in, so both are
still to be executed against a real installation.

## License

GNU GPL v3 or later, like Moodle itself.
