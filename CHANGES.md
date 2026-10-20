# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.1.0] - 2026-10-20

### Added

- **Activities can ask for a short text feedback along with the stars.** A new
  per activity setting, *Ask for text feedback*, off by default and with a site
  wide default, shows a text area of up to 255 characters below the stars. The
  text is sent with the click on a star, so rating is still a single click, and
  a *Save feedback* button changes it later without touching the stars. Line
  breaks are kept and shown in the reports. Feedback is only stored together
  with a rating and is removed when the rating is withdrawn. Works on the
  course page and on the activity page, on the web and in the Moodle app.
- The course report and *Star ratings by user* show the feedback next to each
  rating, and include it in downloads.
- `mod_starrating_set_rating` takes an optional `feedback`, and both rating web
  services return `allowfeedback` and the `feedback` of the current user.
- The feedback is covered by backup and restore, by the data export and by
  every deletion path of the Privacy API.
- Behat tests for text feedback, and a `feedback` column for the
  `the following "mod_starrating > ratings" exist:` step.
- Support for Moodle 5.3, declared as `$plugin->supported = [403, 503]`. Unit
  tests, Behat features and the coding style check pass on Moodle 4.5 and 5.3.

### Fixed

- In the Moodle app, rating a star on the course page with Enter or Space also
  opened the activity when the key was released.
- Changes to the stylesheet never reached the Moodle app unless the plugin
  version changed, because the app downloads it again only when its version
  does. The stylesheet now carries a fingerprint of its own content.
- The feedback field and its button on the app course page take the app's own
  colours for inputs and buttons, instead of dark grey browser defaults.
- The unit tests ran into PHPUnit 11 deprecations on Moodle 5.x: they now
  declare what they cover as attributes as well, and delete an activity through
  the course format actions where Moodle 5.2 and later provide them.

## [2.0.0] - 2026-09-29

### Added

- Behat tests covering what the plugin promises: rating with a single click on
  the course page and on the activity page, changing and withdrawing a rating,
  the per activity scale and the refusal to shorten it below the highest rating
  given, the course report and the site wide report including the mixed scale
  average, and read only stars for everybody who may not rate. A data generator
  step seeds ratings, `the following "mod_starrating > ratings" exist:`.
- **A course report for teachers**, *Star ratings* in the course menu and in the
  menu of every star rating activity. It lists the average per activity of the
  course, including activities nobody has rated yet, and every individual rating
  with the person who gave it, sortable, paged and downloadable. Behind the new
  capability `mod/starrating:viewcoursereport`, which teachers, editing teachers
  and managers have by default.
- **A per activity setting for how many stars people choose from**, 2 to 10,
  with a site wide default under *Site administration → Plugins → Activity
  modules → Star rating*. The scale of an activity that has already been rated
  can be lengthened at any time and shortened down to the highest rating given,
  no further, because ratings above the maximum could no longer be shown.
- `mod_starrating_get_rating` and `mod_starrating_set_rating` return the scale
  of the activity as `maxstars`.
- Star rating activity with stars on the course page, one rating per
  person, changeable and withdrawable.
- Moodle app support with no JavaScript: the stars on the course page link into
  the activity, which the app opens natively, and there the stars submit through
  the app's own `core-site-plugins-call-ws` directive.
- Rating endpoint that stores the rating and returns to the page it was given
  on.
- Web services `mod_starrating_set_rating` and `mod_starrating_get_rating`, both
  part of the official mobile service and available over AJAX.
- Site reports under *Site administration → Reports*: averages grouped by
  course, by activity or by both, and a separate sortable, paged and
  downloadable list of the individual ratings, both filterable by course and
  activity.
- Per activity settings for showing the average and for allowing withdrawal,
  with site wide defaults.
- Overview page listing every star rating of a course with its average.
- Backup and restore, course reset support, and a Privacy API provider covering
  export and all three deletion paths.
- Capabilities `mod/starrating:addinstance`, `mod/starrating:rate` and
  `mod/starrating:viewreports`.
- German translation.

### Changed

- **The plugin is now an activity, not a text filter.** Star ratings are added
  through *Add an activity or resource → Star rating*, and the name of the
  activity is what is being rated. The `{{ rating:criterion }}` token is gone.
  This is what finally makes rating work in the Moodle app: the app opens an
  activity natively, which it could never do for a link inside filtered text.
- The stars appear directly on the course page, the way a label shows its text,
  so rating is still a single click without opening anything.
- The report is now two pages, *Star rating averages* and *Star ratings by
  user*, instead of one page holding both. The filter carries over between
  them.
- **The number of stars is no longer fixed at five.** It is a per activity
  setting between 2 and 10, with a site wide default. Existing activities keep
  their five stars.
- Averages over a selection that mixes activities with scales of different
  lengths are reported as a share of the scale instead of as stars, because four
  of five and eight of ten are the same judgement and adding them up as stars
  would not be. Rows on a single scale are unchanged.
- *Star rating averages* now opens grouped per course, with the number of
  ratings and the average of each, and every course name links to the star
  rating report of that course instead of to the course page. Viewers who may
  not see a course report still get the course page.
- The course overview page and the course report show the length of each scale,
  and the individual ratings say which scale each rating was given on.

### Removed

- The `filter_rating` text filter, its token parsing and the criterion registry
  it needed. The filter is not upgraded to the activity; it has to be
  uninstalled, and its ratings do not carry over.

### Fixed

- Every Moodle coding style violation the plugin had: 87 errors and 7 warnings
  reported by `phpcs --standard=moodle` are gone, among them unnecessary
  `MOODLE_INTERNAL` checks, blank lines after a class brace, multi-line calls
  and declarations, undocumented promoted constructor properties, the order of
  the privacy interfaces and of the language strings.
- In the Moodle app the course page showed no activity name, a module icon that
  said nothing, and the average crammed next to the stars. The name, the stars
  and the average are now stacked and centred, and the icon is gone.
- Rating in the Moodle app took two taps and went through the activity page,
  and the stars on the course page never updated afterwards. One tap on a star
  on the course page now saves the rating and repaints the stars on the spot,
  without opening the activity.
- The activity could not be opened at all, on the web or in the app, because it
  declared that it has no view link. That also stopped the Moodle app from
  rating: the app can only submit from the activity page, which it could never
  reach. The activity name on the course page is a link again.
- The rating web services validated the activity context only after building
  course module information for the user, which can fail with a login error
  instead of a clean permission error when called from the app.
- Installation failed with `plugin_defective_exception`. An activity module's
  language file must be named after the module directory, not after the
  frankenstyle component, so it is now `lang/en/starrating.php`.

### Security

- Rating links are signed with an HMAC bound to user, course module and number
  of stars, so no session key is rendered into the course page. A missing or
  stale signature falls back to a confirmation protected by the session key.
- Return URLs are restricted to local URLs, so the endpoint cannot be used as an
  open redirect.
