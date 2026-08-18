<?php
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
 * English strings for the star rating activity.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['activityname'] = 'Name';
$string['activityname_help'] = 'The name of this activity is what people rate. It is shown next to the stars on the course page, so write it as the thing being judged, for example "Teaching quality".';
$string['allactivities'] = 'All activities';
$string['allcourses'] = 'All courses';
$string['allowclear'] = 'Allow withdrawing a rating';
$string['allowclear_desc'] = 'Default for new activities: show a link that lets people remove the rating they gave.';
$string['allowclear_help'] = 'Shows a link next to the stars with which people can remove the rating they gave.';
$string['applyfilter'] = 'Apply filter';
$string['averages'] = 'Averages';
$string['averagescaption'] = 'Average rating per selected grouping';
$string['averagetext'] = 'Average {$a->average} of {$a->max} from {$a->count} ratings';
$string['clearrating'] = 'Withdraw';
$string['clearratingaria'] = 'Withdraw your rating for {$a}';
$string['colaverage'] = 'Average';
$string['colcourse'] = 'Course';
$string['colcriterion'] = 'Activity';
$string['colrating'] = 'Rating';
$string['colratings'] = 'Ratings';
$string['coltimemodified'] = 'Last changed';
$string['coluser'] = 'User';
$string['confirmclearrating'] = 'Do you want to withdraw your rating for "{$a}"?';
$string['confirmrating'] = 'Do you want to rate "{$a->criterion}" with {$a->stars} of {$a->max} stars?';
$string['details'] = 'Individual ratings';
$string['displaysettings'] = 'Display';
$string['eventratingsubmitted'] = 'Star rating submitted';
$string['filteractivity'] = 'Activity';
$string['filtercourse'] = 'Course';
$string['filtergroupby'] = 'Group by';
$string['filterheading'] = 'Filter';
$string['groupbycourse'] = 'Course';
$string['groupbycoursecriterion'] = 'Course and activity';
$string['groupbycriterion'] = 'Activity';
$string['guestcannotrate'] = 'Guests cannot rate. Please log in to submit a rating.';
$string['invalidrating'] = 'The rating must be a number of stars between 0 and 5.';
$string['modulename'] = 'Star rating';
$string['modulename_help'] = 'The star rating activity puts five stars on the course page. Everyone with the right permission can give one rating, and change or withdraw it later.

The name of the activity is what is being rated, so add one activity per thing you want feedback on. The stars appear directly on the course page, like a label, and work in the Moodle app as well.

Site administration reports the averages per course and per activity.';
$string['modulenameplural'] = 'Star ratings';
$string['noratingsyet'] = 'No ratings yet';
$string['noresults'] = 'No ratings match the current filter.';
$string['notratedyet'] = 'You have not rated {$a} yet.';
$string['overallaverage'] = 'Overall';
$string['pluginadministration'] = 'Star rating administration';
$string['pluginname'] = 'Star rating';
$string['privacy:metadata:starrating_rating'] = 'The star ratings that users submitted.';
$string['privacy:metadata:starrating_rating:rating'] = 'The number of stars the user gave.';
$string['privacy:metadata:starrating_rating:starratingid'] = 'The activity that was rated.';
$string['privacy:metadata:starrating_rating:timecreated'] = 'The time the rating was first submitted.';
$string['privacy:metadata:starrating_rating:timemodified'] = 'The time the rating was last changed.';
$string['privacy:metadata:starrating_rating:userid'] = 'The user who submitted the rating.';
$string['rateinapp'] = 'Rate {$a}';
$string['ratestars'] = 'Rate {$a->criterion} with {$a->stars} of {$a->max} stars';
$string['ratingcleared'] = 'Your rating was withdrawn.';
$string['ratinggroup'] = 'Star rating for {$a}';
$string['ratingsaved'] = 'Your rating was saved.';
$string['report'] = 'Star ratings';
$string['resetratings'] = 'Delete all star ratings';
$string['showaverage'] = 'Show the average';
$string['showaverage_desc'] = 'Default for new activities: show the average rating and the number of ratings next to the stars.';
$string['showaverage_help'] = 'Shows the average rating and the number of ratings next to the stars.';
$string['starrating:addinstance'] = 'Add a new star rating';
$string['starrating:rate'] = 'Submit a star rating';
$string['starrating:viewreports'] = 'View the star rating report, including who rated what';
$string['starsofmax'] = '{$a->stars} of {$a->max}';
$string['yourrating'] = 'Your rating for {$a->criterion}: {$a->stars} of {$a->max} stars';
