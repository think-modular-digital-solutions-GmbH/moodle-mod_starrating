// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Makes the stars on the course page tappable inside the Moodle app.
 *
 * The app renders course page content through core-format-text, so the app's
 * own call-ws directive cannot run there and the stars are plain markup. This
 * runs once when the app loads the site plugins and gives those stars their
 * behaviour: one tap saves the rating through mod_starrating_set_rating and
 * repaints the stars on the spot, without opening the activity.
 *
 * The app evaluates this with "this" bound to an object carrying its services,
 * so they are read from "this". No IIFE, and no top level return, which eval
 * does not allow.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

var sitesService = this.CoreSitesProvider;
var toastsService = this.CoreToastsService;
var alertsService = this.CoreAlertsService;
var pluginsService = this.CoreSitePluginsProvider;

// The app replaces the icons with its own SVG icons, so a star changes state by
// pointing at the other icon of the pair.
var STAR_FILLED = 'assets/fonts/font-awesome/solid/star.svg';
var STAR_OUTLINE = 'assets/fonts/font-awesome/regular/star.svg';

/**
 * Show a short confirmation.
 *
 * @param {String} message Text to show.
 */
var showToast = function(message) {
    if (toastsService && toastsService.show) {
        toastsService.show({message: message});
    }
};

/**
 * Show an error.
 *
 * @param {*} error Error returned by the web service.
 */
var showError = function(error) {
    if (alertsService && alertsService.showError) {
        alertsService.showError(error);
    }
};

/**
 * Paint one star as rated or unrated.
 *
 * @param {Element} star The star element.
 * @param {Boolean} filled Whether the star is covered by the rating.
 */
var setStarState = function(star, filled) {
    star.classList.toggle('filter-rating-on', filled);
    star.classList.toggle('filter-rating-off', !filled);

    var icon = star.querySelector('ion-icon, i');
    if (!icon) {
        return;
    }

    if (icon.tagName === 'ION-ICON') {
        icon.setAttribute('src', filled ? STAR_FILLED : STAR_OUTLINE);
    } else {
        icon.classList.toggle('fa-star', filled);
        icon.classList.toggle('fa-star-o', !filled);
    }
};

/**
 * Bring a whole widget up to date after the rating changed.
 *
 * @param {Element} widget The widget element.
 * @param {Object} result Result of mod_starrating_set_rating.
 */
var updateWidget = function(widget, result) {
    var stars = widget.querySelectorAll('.filter-rating-star');
    for (var i = 0; i < stars.length; i++) {
        setStarState(stars[i], parseInt(stars[i].getAttribute('data-value'), 10) <= result.userrating);
    }

    widget.setAttribute('data-userrating', result.userrating);

    var status = widget.querySelector('[data-region="status"]');
    if (status) {
        status.textContent = result.statustext;
    }

    var average = widget.querySelector('[data-region="average"]');
    if (average) {
        average.textContent = result.averagetext;
    }
};

/**
 * Drop the cached course page content, so a later reload shows the new rating.
 *
 * @param {Number} courseid Course the activity is in.
 * @param {Number} cmid Course module id.
 */
var invalidateCoursePage = function(courseid, cmid) {
    if (!pluginsService || !pluginsService.invalidateContent) {
        return;
    }

    try {
        pluginsService.invalidateContent('mod_starrating', 'mobile_course_page', {
            courseid: courseid,
            cmid: cmid
        });
    } catch (error) {
        // Not worth bothering the user about, the display is already correct.
    }
};

/**
 * Save the rating a tapped star stands for.
 *
 * @param {Element} widget The widget element.
 * @param {Number} rating Number of stars.
 */
var saveRating = function(widget, rating) {
    var site = sitesService && sitesService.getCurrentSite();
    if (!site) {
        return;
    }

    var cmid = parseInt(widget.getAttribute('data-cmid'), 10);
    var courseid = parseInt(widget.getAttribute('data-courseid'), 10);

    site.write('mod_starrating_set_rating', {cmid: cmid, rating: rating}).then(function(result) {
        updateWidget(widget, result);
        invalidateCoursePage(courseid, cmid);
        showToast(result.message);

        return result;
    }).catch(function(error) {
        showError(error);
    });
};

/**
 * Handle a tap or key press on a star.
 *
 * @param {Event} event The event.
 */
var handleActivation = function(event) {
    if (!event.target || !event.target.closest) {
        return;
    }

    var star = event.target.closest('.starrating-app-coursepage .filter-rating-star[data-value]');
    if (!star) {
        return;
    }

    var widget = star.closest('.starrating-activity');
    if (!widget || widget.getAttribute('data-canrate') !== '1') {
        return;
    }

    event.preventDefault();
    event.stopPropagation();
    if (event.stopImmediatePropagation) {
        event.stopImmediatePropagation();
    }

    saveRating(widget, parseInt(star.getAttribute('data-value'), 10));
};

// Capture phase, so the tap is handled before anything else in the course page
// gets a chance to treat it as opening the activity.
document.addEventListener('click', handleActivation, true);

document.addEventListener('keydown', function(event) {
    if (event.key !== 'Enter' && event.key !== ' ') {
        return;
    }

    handleActivation(event);
}, true);
