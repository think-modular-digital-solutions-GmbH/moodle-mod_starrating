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
 * Makes the star ratings tappable inside the Moodle app.
 *
 * The app runs this once, when it loads the site plugins of the site. It listens
 * for taps on the stars anywhere in the app and saves the rating through the
 * filter_rating_set_rating web service, so the user never leaves the app.
 *
 * The app evaluates this file with "this" bound to an object carrying its
 * services, so they are read from "this" and the file must not be wrapped in an
 * IIFE. There is no top level return either, which eval does not allow.
 *
 * @package    filter_rating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

var sitesService = this.CoreSitesProvider;
var toastsService = this.CoreToastsService;
var alertsService = this.CoreAlertsService;

// The app replaces the icons of the filter with its own SVG icons, so a star
// changes state by pointing at the other icon of the pair.
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
        // The app has not converted the icon yet, keep the classes in step.
        icon.classList.toggle('fa-star', filled);
        icon.classList.toggle('fa-star-o', !filled);
    }
};

/**
 * Bring a whole widget up to date after the rating changed.
 *
 * @param {Element} widget The widget element.
 * @param {Object} result Result of filter_rating_set_rating.
 */
var updateWidget = function(widget, result) {
    var stars = widget.querySelectorAll('.filter-rating-star');
    for (var i = 0; i < stars.length; i++) {
        setStarState(stars[i], parseInt(stars[i].getAttribute('data-value'), 10) <= result.userrating);
    }

    widget.setAttribute('data-userrating', result.userrating);
    widget.setAttribute('data-average', result.average);
    widget.setAttribute('data-count', result.count);

    var status = widget.querySelector('[data-region="status"]');
    if (status) {
        status.textContent = result.statustext;
    }

    var average = widget.querySelector('[data-region="average"]');
    if (average) {
        average.textContent = result.averagetext;
    }

    var clear = widget.querySelector('[data-region="clear"]');
    if (clear) {
        clear.classList.toggle('filter-rating-hidden', result.userrating === 0);
    }
};

/**
 * Save the rating a tapped link stands for.
 *
 * @param {Element} widget The widget element.
 * @param {Number} rating Number of stars, 0 to withdraw.
 */
var saveRating = function(widget, rating) {
    var site = sitesService && sitesService.getCurrentSite();
    if (!site) {
        return;
    }

    site.write('filter_rating_set_rating', {
        contextid: parseInt(widget.getAttribute('data-contextid'), 10),
        criterion: widget.getAttribute('data-criterion'),
        rating: rating
    }).then(function(result) {
        updateWidget(widget, result);
        showToast(result.message);

        return result;
    }).catch(function(error) {
        showError(error);
    });
};

// Listen in the capture phase, so the tap is handled here before the app's own
// link handling gets a chance to open the star's href in a browser.
document.addEventListener('click', function(event) {
    if (!event.target || !event.target.closest) {
        return;
    }

    var link = event.target.closest('a.filter-rating-star, a.filter-rating-clear');
    if (!link) {
        return;
    }

    var widget = link.closest('.filter-rating');
    if (!widget || widget.getAttribute('data-canrate') !== '1') {
        return;
    }

    event.preventDefault();
    event.stopPropagation();
    if (event.stopImmediatePropagation) {
        event.stopImmediatePropagation();
    }

    var rating = link.classList.contains('filter-rating-clear')
        ? 0
        : parseInt(link.getAttribute('data-value'), 10);

    saveRating(widget, rating);
}, true);
