# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Star rating filter: the token `{{ rating:criterion }}` is replaced by a five
  star FontAwesome rating anywhere text is filtered in Moodle.
- Rating endpoint that stores the rating and returns to the page it was given
  on, and that also serves the Moodle app through `data-app-url`, refreshing
  the content when the app window closes.
- Web services `filter_rating_set_rating` and `filter_rating_get_rating`, both
  part of the official mobile service and available over AJAX.
- Site report under *Site administration → Reports → Star ratings*: averages
  grouped by course, by criterion or by both, filterable by course and
  criterion, plus a sortable, paged and downloadable list of the individual
  ratings.
- Settings for showing the average next to the stars and for allowing users to
  withdraw their rating.
- Capabilities `filter/rating:rate` and `filter/rating:viewreports`.
- Event `\filter_rating\event\rating_submitted` for every submitted, changed or
  withdrawn rating.
- Privacy API provider covering export and all three deletion paths.
- German translation.
- Unit tests for the rating manager, the token replacement and the privacy
  provider.

### Security

- Rating links are signed with an HMAC bound to user, context, criterion and
  number of stars, so no session key is ever rendered into filtered text. A
  missing or stale signature falls back to a confirmation protected by the
  session key.
- Return URLs are restricted to local URLs, so the endpoint cannot be used as
  an open redirect.
