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
 * Steps for the star rating activity.
 *
 * @package    mod_starrating
 * @category   test
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;

/**
 * Steps for the star rating activity.
 *
 * The stars are the whole interface of this plugin, so the steps talk about the
 * criterion, which is the name of the activity, rather than about markup.
 *
 * @package    mod_starrating
 * @category   test
 * @copyright  2026 Think Modular
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_starrating extends behat_base {
    /**
     * Give a rating by clicking the star of that value.
     *
     * @When I rate :criterion with :stars stars
     * @param string $criterion Name of the activity being rated.
     * @param int $stars Number of stars to click.
     */
    public function i_rate_with_stars(string $criterion, int $stars): void {
        $star = $this->find(
            'css',
            'a.filter-rating-star[data-value="' . $stars . '"]',
            new ExpectationException(
                "There is no clickable star {$stars} for \"{$criterion}\".",
                $this->getSession()
            ),
            $this->get_widget($criterion)
        );

        $this->execute('behat_general::i_click_on', [$star, 'NodeElement']);
    }

    /**
     * Withdraw the rating given for a criterion.
     *
     * @When I withdraw my rating for :criterion
     * @param string $criterion Name of the activity being rated.
     */
    public function i_withdraw_my_rating_for(string $criterion): void {
        $link = $this->find(
            'css',
            'a.filter-rating-clear',
            new ExpectationException(
                "There is no way to withdraw the rating for \"{$criterion}\".",
                $this->getSession()
            ),
            $this->get_widget($criterion)
        );

        $this->execute('behat_general::i_click_on', [$link, 'NodeElement']);
    }

    /**
     * Check how many of how many stars are filled in.
     *
     * A rated star is fa-star and an unrated one fa-star-o, which is the pair
     * that renders on every FontAwesome version Moodle ships, so the check is
     * on the icons rather than on a colour or a count of links.
     *
     * @Then the star rating for :criterion should show :on of :max stars
     * @param string $criterion Name of the activity being rated.
     * @param int $on Expected number of filled stars.
     * @param int $max Expected length of the scale.
     * @throws ExpectationException
     */
    public function the_star_rating_should_show(string $criterion, int $on, int $max): void {
        $widget = $this->get_widget($criterion);

        $filled = count($widget->findAll('css', '.filter-rating-star i.fa-star'));
        $empty = count($widget->findAll('css', '.filter-rating-star i.fa-star-o'));

        if ($filled !== $on || $filled + $empty !== $max) {
            throw new ExpectationException(
                "Expected {$on} of {$max} stars for \"{$criterion}\", "
                    . "found {$filled} of " . ($filled + $empty) . '.',
                $this->getSession()
            );
        }
    }

    /**
     * Check that the stars of a criterion cannot be clicked.
     *
     * @Then the star rating for :criterion should not be clickable
     * @param string $criterion Name of the activity being rated.
     * @throws ExpectationException
     */
    public function the_star_rating_should_not_be_clickable(string $criterion): void {
        $widget = $this->get_widget($criterion);

        if (count($widget->findAll('css', 'a.filter-rating-star'))) {
            throw new ExpectationException(
                "The stars for \"{$criterion}\" can be clicked, which they should not.",
                $this->getSession()
            );
        }
    }

    /**
     * The stars of one criterion, found by the accessible name of the group.
     *
     * @param string $criterion Name of the activity being rated.
     * @return NodeElement The element holding the stars.
     */
    protected function get_widget(string $criterion): NodeElement {
        $label = behat_context_helper::escape(
            get_string('ratinggroup', 'mod_starrating', $criterion)
        );

        return $this->find('xpath', "//*[@role='group'][@aria-label={$label}]");
    }
}
