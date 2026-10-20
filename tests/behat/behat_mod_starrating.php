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
     * A star is a link, or a submit button when the activity asks for text
     * feedback. Either way it is a single click, so the step does not care.
     *
     * @When I rate :criterion with :stars stars
     * @param string $criterion Name of the activity being rated.
     * @param int $stars Number of stars to click.
     */
    public function i_rate_with_stars(string $criterion, int $stars): void {
        $star = $this->find(
            'css',
            'a.filter-rating-star[data-value="' . $stars . '"], button.filter-rating-star[data-value="' . $stars . '"]',
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

        if (count($widget->findAll('css', 'a.filter-rating-star, button.filter-rating-star'))) {
            throw new ExpectationException(
                "The stars for \"{$criterion}\" can be clicked, which they should not.",
                $this->getSession()
            );
        }
    }

    /**
     * Type a text feedback into the field next to the stars, without sending it.
     *
     * @When I enter :text as my feedback for :criterion
     * @param string $text Feedback to type.
     * @param string $criterion Name of the activity being rated.
     */
    public function i_enter_as_my_feedback_for(string $text, string $criterion): void {
        $this->get_feedback_field($criterion)->setValue($text);
    }

    /**
     * Press Enter in the feedback field, which starts a new line there.
     *
     * @When I type a line break into my feedback for :criterion
     * @param string $criterion Name of the activity being rated.
     */
    public function i_type_a_line_break_into_my_feedback_for(string $criterion): void {
        $this->require_javascript();

        $field = $this->get_feedback_field($criterion);
        $field->focus();
        behat_base::type_keys($this->getSession(), [behat_keys::ENTER]);
        $this->wait_for_pending_js();
    }

    /**
     * Send the text feedback with the rating already given, without touching the stars.
     *
     * @When I save my feedback for :criterion
     * @param string $criterion Name of the activity being rated.
     */
    public function i_save_my_feedback_for(string $criterion): void {
        $button = $this->find_save_feedback_button($criterion);

        if (!$button) {
            throw new ExpectationException(
                "There is no button that saves the feedback for \"{$criterion}\".",
                $this->getSession()
            );
        }

        $this->execute('behat_general::i_click_on', [$button, 'NodeElement']);
    }

    /**
     * Check that the feedback cannot be saved on its own, which needs stars first.
     *
     * @Then I should not be able to save my feedback alone for :criterion
     * @param string $criterion Name of the activity being rated.
     * @throws ExpectationException
     */
    public function i_should_not_be_able_to_save_my_feedback_alone_for(string $criterion): void {
        if ($this->find_save_feedback_button($criterion)) {
            throw new ExpectationException(
                "The feedback for \"{$criterion}\" can be saved without stars, which it should not.",
                $this->getSession()
            );
        }
    }

    /**
     * Check what the feedback field holds.
     *
     * @Then my feedback for :criterion should be :text
     * @param string $criterion Name of the activity being rated.
     * @param string $text Expected feedback, empty for none.
     * @throws ExpectationException
     */
    public function my_feedback_should_be(string $criterion, string $text): void {
        $value = (string) $this->get_feedback_field($criterion)->getValue();

        if ($value !== $text) {
            throw new ExpectationException(
                "Expected the feedback for \"{$criterion}\" to be \"{$text}\", found \"{$value}\".",
                $this->getSession()
            );
        }
    }

    /**
     * Check that an activity does not ask for text feedback.
     *
     * @Then there should be no feedback field for :criterion
     * @param string $criterion Name of the activity being rated.
     * @throws ExpectationException
     */
    public function there_should_be_no_feedback_field_for(string $criterion): void {
        if ($this->get_widget($criterion)->find('css', 'textarea[name="feedback"]')) {
            throw new ExpectationException(
                "There is a feedback field for \"{$criterion}\", which there should not be.",
                $this->getSession()
            );
        }
    }

    /**
     * The visible button that saves the feedback with the current rating.
     *
     * @param string $criterion Name of the activity being rated.
     * @return NodeElement|null The button, null when there is none.
     */
    protected function find_save_feedback_button(string $criterion): ?NodeElement {
        $label = behat_context_helper::escape(get_string('savefeedback', 'mod_starrating'));

        return $this->get_widget($criterion)->find(
            'xpath',
            "//button[@type='submit'][normalize-space(.)={$label}]"
        );
    }

    /**
     * The feedback field next to the stars of one criterion.
     *
     * @param string $criterion Name of the activity being rated.
     * @return NodeElement The text field.
     */
    protected function get_feedback_field(string $criterion): NodeElement {
        return $this->find(
            'css',
            'textarea[name="feedback"]',
            new ExpectationException(
                "There is no feedback field for \"{$criterion}\".",
                $this->getSession()
            ),
            $this->get_widget($criterion)
        );
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
