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
 * German strings for the star rating activity.
 *
 * @package    mod_starrating
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['activityname'] = 'Name';
$string['activityname_help'] = 'Der Name dieser Aktivität ist das, was bewertet wird. Er wird auf der Kursseite neben den Sternen angezeigt. Formulieren Sie ihn daher als das zu Bewertende, zum Beispiel „Qualität der Lehre“.';
$string['allactivities'] = 'Alle Aktivitäten';
$string['allcourses'] = 'Alle Kurse';
$string['allowclear'] = 'Zurückziehen einer Bewertung erlauben';
$string['allowclear_desc'] = 'Voreinstellung für neue Aktivitäten: einen Link anzeigen, mit dem die eigene Bewertung entfernt werden kann.';
$string['allowclear_help'] = 'Zeigt neben den Sternen einen Link an, mit dem die eigene Bewertung wieder entfernt werden kann.';
$string['allowfeedback'] = 'Textfeedback erfragen';
$string['allowfeedback_desc'] = 'Voreinstellung für neue Aktivitäten: Neben den Sternen ein kurzes Textfeld anzeigen, in dem man begründen kann, warum man so bewertet hat.';
$string['allowfeedback_help'] = 'Zeigt neben den Sternen ein kurzes Textfeld, in dem man der Bewertung eine Zeile Feedback mitgeben kann, bis zu 255 Zeichen. Der Text wird zusammen mit den Sternen gesendet und nur gespeichert, solange es eine Bewertung gibt: Wird die Bewertung zurückgezogen, wird auch der Text entfernt.

Lehrende sehen das Feedback im Kursbericht neben jeder Bewertung.';
$string['applyfilter'] = 'Filter anwenden';
$string['averagemixed'] = 'Durchschnitt {$a->percent}% der Skala aus {$a->count} Bewertungen, bei unterschiedlich langen Skalen';
$string['averages'] = 'Durchschnittswerte';
$string['averagescaption'] = 'Durchschnittliche Bewertung je gewählter Gruppierung';
$string['averagetext'] = 'Durchschnitt {$a->average} von {$a->max} aus {$a->count} Bewertungen';
$string['clearrating'] = 'Zurückziehen';
$string['clearratingaria'] = 'Ihre Bewertung für {$a} zurückziehen';
$string['colaverage'] = 'Durchschnitt';
$string['colcourse'] = 'Kurs';
$string['colcriterion'] = 'Aktivität';
$string['colfeedback'] = 'Feedback';
$string['colrating'] = 'Bewertung';
$string['colratings'] = 'Bewertungen';
$string['colscale'] = 'Sterne';
$string['coltimemodified'] = 'Zuletzt geändert';
$string['coluser'] = 'Nutzer/in';
$string['confirmclearrating'] = 'Möchten Sie Ihre Bewertung für „{$a}“ zurückziehen?';
$string['confirmrating'] = 'Möchten Sie „{$a->criterion}“ mit {$a->stars} von {$a->max} Sternen bewerten?';
$string['coursereport'] = 'Sternebewertungen';
$string['coursesummarycaption'] = 'Durchschnittliche Bewertung je Sternebewertung dieses Kurses';
$string['details'] = 'Einzelne Bewertungen';
$string['displaysettings'] = 'Darstellung';
$string['eventratingsubmitted'] = 'Sternebewertung abgegeben';
$string['feedbacklabel'] = 'Ihr Feedback (optional)';
$string['filteractivity'] = 'Aktivität';
$string['filtercourse'] = 'Kurs';
$string['filtergroupby'] = 'Gruppieren nach';
$string['filterheading'] = 'Filter';
$string['groupbycourse'] = 'Kurs';
$string['groupbycoursecriterion'] = 'Kurs und Aktivität';
$string['groupbycriterion'] = 'Aktivität';
$string['guestcannotrate'] = 'Gäste können nicht bewerten. Bitte melden Sie sich an, um eine Bewertung abzugeben.';
$string['invalidrating'] = 'Die Bewertung muss eine Anzahl von 0 bis {$a} Sternen sein.';
$string['maxstars'] = 'Anzahl der Sterne';
$string['maxstars_desc'] = 'Voreinstellung für neue Aktivitäten: Anzahl der Sterne, aus denen ausgewählt wird.';
$string['maxstars_help'] = 'Anzahl der Sterne, aus denen ausgewählt wird, zwischen 2 und 10. Eine längere Skala erlaubt genauere Abstufungen, eine kürzere ist schneller zu beurteilen.

Die Skala einer bereits bewerteten Aktivität kann jederzeit verlängert werden. Verkürzen ist nur bis zur höchsten bereits abgegebenen Bewertung möglich, da Bewertungen über dem neuen Maximum nicht mehr darstellbar wären.';
$string['maxstarstoolow'] = 'Diese Aktivität wurde bereits mit {$a} Sternen bewertet. Die Skala kann daher nicht kürzer als das sein.';
$string['modulename'] = 'Sternebewertung';
$string['modulename_help'] = 'Die Aktivität „Sternebewertung“ stellt eine Reihe von Sternen auf die Kursseite. Alle mit der entsprechenden Berechtigung können eine Bewertung abgeben und diese später ändern oder zurückziehen.

Der Name der Aktivität ist das, was bewertet wird. Legen Sie daher eine Aktivität je Gegenstand an, zu dem Sie Rückmeldung möchten. Wie viele Sterne zur Auswahl stehen, wird je Aktivität eingestellt, zwischen 2 und 10. Die Sterne erscheinen direkt auf der Kursseite wie bei einem Textfeld und funktionieren auch in der Moodle App.

Lehrende sehen die Bewertungen ihres Kurses über „Sternebewertungen“ im Kursmenü, die Website-Administration weist die Durchschnittswerte über alle Kurse aus.';
$string['modulenameplural'] = 'Sternebewertungen';
$string['noratingsyet'] = 'Noch keine Bewertungen';
$string['noresults'] = 'Keine Bewertungen entsprechen dem aktuellen Filter.';
$string['notratedyet'] = 'Sie haben {$a} noch nicht bewertet.';
$string['overallaverage'] = 'Gesamt';
$string['pluginadministration'] = 'Sternebewertung-Administration';
$string['pluginname'] = 'Sternebewertung';
$string['privacy:metadata:starrating_rating'] = 'Die abgegebenen Sternebewertungen.';
$string['privacy:metadata:starrating_rating:feedback'] = 'Das Textfeedback, das die Person mit der Bewertung abgegeben hat.';
$string['privacy:metadata:starrating_rating:rating'] = 'Die Anzahl der vergebenen Sterne.';
$string['privacy:metadata:starrating_rating:starratingid'] = 'Die bewertete Aktivität.';
$string['privacy:metadata:starrating_rating:timecreated'] = 'Der Zeitpunkt der ersten Abgabe der Bewertung.';
$string['privacy:metadata:starrating_rating:timemodified'] = 'Der Zeitpunkt der letzten Änderung der Bewertung.';
$string['privacy:metadata:starrating_rating:userid'] = 'Die Person, die die Bewertung abgegeben hat.';
$string['ratestars'] = '{$a->criterion} mit {$a->stars} von {$a->max} Sternen bewerten';
$string['ratingcleared'] = 'Ihre Bewertung wurde zurückgezogen.';
$string['ratinggroup'] = 'Sternebewertung für {$a}';
$string['ratingsaved'] = 'Ihre Bewertung wurde gespeichert.';
$string['reportaverages'] = 'Durchschnittswerte der Sternebewertungen';
$string['reportdetails'] = 'Sternebewertungen nach Personen';
$string['resetratings'] = 'Alle Sternebewertungen löschen';
$string['savefeedback'] = 'Feedback speichern';
$string['showaverage'] = 'Durchschnitt anzeigen';
$string['showaverage_desc'] = 'Voreinstellung für neue Aktivitäten: Durchschnitt und Anzahl der Bewertungen neben den Sternen anzeigen.';
$string['showaverage_help'] = 'Zeigt neben den Sternen den Durchschnitt und die Anzahl der Bewertungen an.';
$string['starrating:addinstance'] = 'Neue Sternebewertung hinzufügen';
$string['starrating:rate'] = 'Sternebewertung abgeben';
$string['starrating:viewcoursereport'] = 'Sternebewertungen eines Kurses einschließlich der bewertenden Personen ansehen';
$string['starrating:viewreports'] = 'Bericht zu Sternebewertungen einschließlich der bewertenden Personen ansehen';
$string['starsofmax'] = '{$a->stars} von {$a->max}';
$string['viewaverages'] = 'Durchschnittswerte ansehen';
$string['viewdetails'] = 'Einzelne Bewertungen ansehen';
$string['yourrating'] = 'Ihre Bewertung für {$a->criterion}: {$a->stars} von {$a->max} Sternen';
