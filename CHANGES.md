# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- **The plugin is now an activity, not a text filter.** Star ratings are added
  through *Add an activity or resource → Star rating*, and the name of the
  activity is what is being rated. The `{{ rating:criterion }}` token is gone.
  This is what finally makes rating work in the Moodle app: the app opens an
  activity natively, which it could never do for a link inside filtered text.
- The stars appear directly on the course page, the way a label shows its text,
  so rating is still a single click without opening anything.

### Fixed

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

### Added

- Star rating activity with five stars on the course page, one rating per
  person, changeable and withdrawable.
- Moodle app support with no JavaScript: the stars on the course page link into
  the activity, which the app opens natively, and there the stars submit through
  the app's own `core-site-plugins-call-ws` directive.
- Rating endpoint that stores the rating and returns to the page it was given
  on.
- Web services `mod_starrating_set_rating` and `mod_starrating_get_rating`, both
  part of the official mobile service and available over AJAX.
- Site report under *Site administration → Reports → Star ratings*: averages
  grouped by course, by activity or by both, filterable by course and activity,
  plus a sortable, paged and downloadable list of the individual ratings.
- Per activity settings for showing the average and for allowing withdrawal,
  with site wide defaults.
- Overview page listing every star rating of a course with its average.
- Backup and restore, course reset support, and a Privacy API provider covering
  export and all three deletion paths.
- Capabilities `mod/starrating:addinstance`, `mod/starrating:rate` and
  `mod/starrating:viewreports`.
- German translation.

### Removed

- The `filter_rating` text filter, its token parsing and the criterion registry
  it needed. The filter is not upgraded to the activity; it has to be
  uninstalled, and its ratings do not carry over.

### Security

- Rating links are signed with an HMAC bound to user, course module and number
  of stars, so no session key is rendered into the course page. A missing or
  stale signature falls back to a confirmation protected by the session key.
- Return URLs are restricted to local URLs, so the endpoint cannot be used as an
  open redirect.
