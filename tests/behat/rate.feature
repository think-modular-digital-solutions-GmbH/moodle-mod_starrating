@mod @mod_starrating
Feature: Give a star rating
  In order to say what I think of something
  As a user
  I need to give, change and withdraw a rating with one click on a star

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Sam       | Student  | student1@example.com |
      | student2 | Robin     | Reviewer | student2@example.com |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
      | student2 | C1     | student |
    And the following "activities" exist:
      | activity   | course | name             | section |
      | starrating | C1     | Teaching quality | 1       |

  Scenario: The stars sit on the course page and nobody has rated yet
    When I am on the "Course 1" course page logged in as "student1"
    Then I should see "Teaching quality"
    And the star rating for "Teaching quality" should show 0 of 5 stars
    And I should see "No ratings yet"

  Scenario: A student rates on the course page with a single click
    Given I am on the "Course 1" course page logged in as "student1"
    When I rate "Teaching quality" with 4 stars
    Then I should see "Your rating was saved."
    And I should see "Average 4.0 of 5 from 1 ratings"
    And the star rating for "Teaching quality" should show 4 of 5 stars

  @javascript
  Scenario: The stars work the same in a real browser
    Given I am on the "Course 1" course page logged in as "student1"
    When I rate "Teaching quality" with 2 stars
    Then I should see "Your rating was saved."
    And the star rating for "Teaching quality" should show 2 of 5 stars

  Scenario: A rating can be changed by clicking another star
    Given the following "mod_starrating > ratings" exist:
      | activity         | user     | rating |
      | Teaching quality | student1 | 2      |
    And I am on the "Course 1" course page logged in as "student1"
    And the star rating for "Teaching quality" should show 2 of 5 stars
    When I rate "Teaching quality" with 5 stars
    Then I should see "Average 5.0 of 5 from 1 ratings"
    And the star rating for "Teaching quality" should show 5 of 5 stars

  Scenario: A rating can be withdrawn
    Given the following "mod_starrating > ratings" exist:
      | activity         | user     | rating |
      | Teaching quality | student1 | 3      |
    And I am on the "Course 1" course page logged in as "student1"
    When I withdraw my rating for "Teaching quality"
    Then I should see "Your rating was withdrawn."
    And I should see "No ratings yet"
    And the star rating for "Teaching quality" should show 0 of 5 stars

  Scenario: The average is the average of everybody, the stars are my own rating
    Given the following "mod_starrating > ratings" exist:
      | activity         | user     | rating |
      | Teaching quality | student1 | 2      |
      | Teaching quality | student2 | 5      |
    When I am on the "Course 1" course page logged in as "student1"
    Then I should see "Average 3.5 of 5 from 2 ratings"
    And the star rating for "Teaching quality" should show 2 of 5 stars

  Scenario: The activity page rates the same activity as the course page
    Given I am on the "Teaching quality" "starrating activity" page logged in as "student1"
    When I rate "Teaching quality" with 3 stars
    Then I should see "Your rating was saved."
    And the star rating for "Teaching quality" should show 3 of 5 stars
    And I am on the "Course 1" course page
    And the star rating for "Teaching quality" should show 3 of 5 stars

  Scenario: Withdrawing can be turned off for an activity
    Given the following "activities" exist:
      | activity   | course | name          | section | allowclear |
      | starrating | C1     | Course design | 1       | 0          |
    And the following "mod_starrating > ratings" exist:
      | activity      | user     | rating |
      | Course design | student1 | 4      |
    When I am on the "Course 1" course page logged in as "student1"
    Then the star rating for "Course design" should show 4 of 5 stars
    And I should not see "Withdraw"

  Scenario: The average can be hidden for an activity
    Given the following "activities" exist:
      | activity   | course | name          | section | showaverage |
      | starrating | C1     | Course design | 1       | 0           |
    And the following "mod_starrating > ratings" exist:
      | activity      | user     | rating |
      | Course design | student2 | 4      |
    When I am on the "Course 1" course page logged in as "student1"
    Then I should not see "Average 4.0 of 5 from 1 ratings"
    And the star rating for "Course design" should show 0 of 5 stars
