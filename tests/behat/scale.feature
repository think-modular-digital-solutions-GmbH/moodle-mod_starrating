@mod @mod_starrating
Feature: Choose how long the rating scale is
  In order to ask for a judgement as precisely as it is worth
  As a teacher
  I need to set how many stars an activity is rated on

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Terry     | Teacher  | teacher1@example.com |
      | student1 | Sam       | Student  | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  Scenario: An activity is rated on the scale it was created with
    Given the following "activities" exist:
      | activity   | course | name             | section | maxstars |
      | starrating | C1     | Teaching quality | 1       | 3        |
      | starrating | C1     | Course design    | 1       | 10       |
    When I am on the "Course 1" course page logged in as "student1"
    Then the star rating for "Teaching quality" should show 0 of 3 stars
    And the star rating for "Course design" should show 0 of 10 stars
    When I rate "Course design" with 8 stars
    Then I should see "Average 8.0 of 10 from 1 ratings"
    And the star rating for "Course design" should show 8 of 10 stars

  Scenario: A teacher sets the scale when adding the activity
    Given I am on the "Course 1" course page logged in as "teacher1"
    When I add a "starrating" activity to course "Course 1" section "1" and I fill the form with:
      | Name            | Teaching quality |
      | Number of stars | 4                |
    Then the star rating for "Teaching quality" should show 0 of 4 stars

  Scenario: The scale of a rated activity can be made longer
    Given the following "activities" exist:
      | activity   | course | name             | section | maxstars |
      | starrating | C1     | Teaching quality | 1       | 5        |
    And the following "mod_starrating > ratings" exist:
      | activity         | user     | rating |
      | Teaching quality | student1 | 4      |
    And I am on the "Teaching quality" "starrating activity editing" page logged in as "teacher1"
    When I set the field "Number of stars" to "8"
    And I press "Save and return to course"
    Then the star rating for "Teaching quality" should show 0 of 8 stars
    And I am on the "Course 1" course page logged in as "student1"
    And the star rating for "Teaching quality" should show 4 of 8 stars

  Scenario: The scale cannot be made shorter than the highest rating given
    Given the following "activities" exist:
      | activity   | course | name             | section | maxstars |
      | starrating | C1     | Teaching quality | 1       | 5        |
    And the following "mod_starrating > ratings" exist:
      | activity         | user     | rating |
      | Teaching quality | student1 | 4      |
    And I am on the "Teaching quality" "starrating activity editing" page logged in as "teacher1"
    When I set the field "Number of stars" to "3"
    And I press "Save and return to course"
    Then I should see "This activity has already been rated with 4 stars, so the scale cannot be made shorter than that."
    And I set the field "Number of stars" to "4"
    And I press "Save and return to course"
    And I am on the "Course 1" course page logged in as "student1"
    And the star rating for "Teaching quality" should show 4 of 4 stars
