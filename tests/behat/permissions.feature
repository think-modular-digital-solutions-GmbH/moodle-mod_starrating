@mod @mod_starrating
Feature: Only people who may rate get clickable stars
  In order to keep a rating a statement of the person who made it
  As an administrator
  I need the stars to be read only for everybody who may not rate

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
    And the following "mod_starrating > ratings" exist:
      | activity         | user     | rating |
      | Teaching quality | student2 | 5      |

  Scenario: A student whose right to rate was taken away only reads the average
    Given the following "permission overrides" exist:
      | capability            | permission | role    | contextlevel | reference |
      | mod/starrating:rate   | Prevent    | student | Course       | C1        |
    When I am on the "Course 1" course page logged in as "student1"
    Then I should see "Average 5.0 of 5 from 1 ratings"
    And the star rating for "Teaching quality" should not be clickable
    And I should not see "Withdraw"

  Scenario: A guest only reads the average
    Given I am on the "Course 1" "enrolment methods" page logged in as "admin"
    And I click on "Edit" "link" in the "Guest access" "table_row"
    And I set the field "Allow guest access" to "Yes"
    And I press "Save changes"
    And I log out
    When I log in as "guest"
    And I am on "Course 1" course homepage
    Then I should see "Average 5.0 of 5 from 1 ratings"
    And the star rating for "Teaching quality" should not be clickable
