@mod @mod_starrating
Feature: Add a short text feedback to a star rating
  In order to say why I rated the way I did
  As a user
  I need to send a short text together with my stars, if the activity asks for it

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Terry     | Teacher  | teacher1@example.com |
      | student1 | Sam       | Student  | student1@example.com |
      | student2 | Robin     | Reviewer | student2@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And the following "activities" exist:
      | activity   | course | name             | section | allowfeedback |
      | starrating | C1     | Teaching quality | 1       | 1             |
      | starrating | C1     | Course design    | 1       | 0             |

  Scenario: Only an activity that asks for feedback shows the field
    When I am on the "Course 1" course page logged in as "student1"
    Then my feedback for "Teaching quality" should be ""
    And there should be no feedback field for "Course design"

  Scenario: The feedback is sent with a single click on a star
    Given I am on the "Course 1" course page logged in as "student1"
    When I enter "Clear and well paced" as my feedback for "Teaching quality"
    And I rate "Teaching quality" with 4 stars
    Then I should see "Your rating was saved."
    And the star rating for "Teaching quality" should show 4 of 5 stars
    And my feedback for "Teaching quality" should be "Clear and well paced"

  @javascript
  Scenario: The feedback is sent with a click on a star in a real browser
    Given I am on the "Course 1" course page logged in as "student1"
    When I enter "Clear and well paced" as my feedback for "Teaching quality"
    And I rate "Teaching quality" with 3 stars
    Then I should see "Your rating was saved."
    And the star rating for "Teaching quality" should show 3 of 5 stars
    And my feedback for "Teaching quality" should be "Clear and well paced"

  Scenario: The feedback can be changed without touching the stars
    Given the following "mod_starrating > ratings" exist:
      | activity         | user     | rating | feedback |
      | Teaching quality | student1 | 4      | First go |
    And I am on the "Course 1" course page logged in as "student1"
    And my feedback for "Teaching quality" should be "First go"
    When I enter "Second thoughts" as my feedback for "Teaching quality"
    And I save my feedback for "Teaching quality"
    Then I should see "Your rating was saved."
    And the star rating for "Teaching quality" should show 4 of 5 stars
    And my feedback for "Teaching quality" should be "Second thoughts"

  Scenario: Changing the stars keeps the feedback
    Given the following "mod_starrating > ratings" exist:
      | activity         | user     | rating | feedback       |
      | Teaching quality | student1 | 2      | Hard to follow |
    And I am on the "Course 1" course page logged in as "student1"
    When I rate "Teaching quality" with 5 stars
    Then the star rating for "Teaching quality" should show 5 of 5 stars
    And my feedback for "Teaching quality" should be "Hard to follow"

  Scenario: Withdrawing the rating removes the feedback too
    Given the following "mod_starrating > ratings" exist:
      | activity         | user     | rating | feedback       |
      | Teaching quality | student1 | 3      | Hard to follow |
    And I am on the "Course 1" course page logged in as "student1"
    When I withdraw my rating for "Teaching quality"
    Then I should see "Your rating was withdrawn."
    And the star rating for "Teaching quality" should show 0 of 5 stars
    And my feedback for "Teaching quality" should be ""

  Scenario: Feedback alone cannot be saved before there are stars
    When I am on the "Course 1" course page logged in as "student1"
    Then I should not be able to save my feedback alone for "Teaching quality"

  @javascript
  Scenario: Enter starts a new line in the feedback and never rates by accident
    Given I am on the "Course 1" course page logged in as "student1"
    When I enter "Not sure yet" as my feedback for "Teaching quality"
    And I type a line break into my feedback for "Teaching quality"
    Then I should not see "Your rating was saved."
    And the star rating for "Teaching quality" should show 0 of 5 stars
    And I rate "Teaching quality" with 4 stars
    And I should see "Your rating was saved."
    And my feedback for "Teaching quality" should be "Not sure yet"

  Scenario: The activity page asks for feedback as well
    Given I am on the "Teaching quality" "starrating activity" page logged in as "student1"
    When I enter "Seen on its own page" as my feedback for "Teaching quality"
    And I rate "Teaching quality" with 2 stars
    Then I should see "Your rating was saved."
    And I am on the "Course 1" course page
    And the star rating for "Teaching quality" should show 2 of 5 stars
    And my feedback for "Teaching quality" should be "Seen on its own page"

  Scenario: Somebody who may not rate gets no feedback field
    Given the following "permission overrides" exist:
      | capability          | permission | role    | contextlevel | reference |
      | mod/starrating:rate | Prevent    | student | Course       | C1        |
    When I am on the "Course 1" course page logged in as "student1"
    Then there should be no feedback field for "Teaching quality"

  Scenario: A teacher turns feedback on for an activity
    Given I am on the "Course design" "starrating activity editing" page logged in as "teacher1"
    When I set the field "Ask for text feedback" to "1"
    And I press "Save and return to course"
    And I log out
    And I am on the "Course 1" course page logged in as "student1"
    Then my feedback for "Course design" should be ""

  Scenario: The teacher reads the feedback next to each rating in the course report
    Given the following "mod_starrating > ratings" exist:
      | activity         | user     | rating | feedback             |
      | Teaching quality | student1 | 4      | Clear and well paced |
      | Teaching quality | student2 | 2      |                      |
    When I am on the "Course 1" course page logged in as "teacher1"
    And I select "Star ratings" from secondary navigation
    Then I should see "Clear and well paced" in the "Sam Student" "table_row"
    And I should see "2 of 5" in the "Robin Reviewer" "table_row"

  Scenario: The site report lists the feedback as well
    Given the following "mod_starrating > ratings" exist:
      | activity         | user     | rating | feedback             |
      | Teaching quality | student1 | 4      | Clear and well paced |
    When I log in as "admin"
    And I navigate to "Reports > Star ratings by user" in site administration
    Then I should see "Clear and well paced" in the "Sam Student" "table_row"
