@mod @mod_starrating
Feature: See the star ratings of my own course
  In order to learn what my students think
  As a teacher
  I need a report of the ratings given in my course

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
      | activity   | course | name             | section | maxstars |
      | starrating | C1     | Teaching quality | 1       | 5        |
      | starrating | C1     | Course design    | 1       | 5        |
    And the following "mod_starrating > ratings" exist:
      | activity         | user     | rating |
      | Teaching quality | student1 | 4      |
      | Teaching quality | student2 | 3      |

  Scenario: A teacher reaches the report from the course and sees both halves of it
    Given I am on the "Course 1" course page logged in as "teacher1"
    When I select "Star ratings" from secondary navigation
    Then I should see "Averages"
    And the following should exist in the "Average rating per star rating activity of this course" table:
      | Activity         | Stars | Ratings | Average                         |
      | Course design    | 5     | 0       | No ratings yet                  |
      | Teaching quality | 5     | 2       | Average 3.5 of 5 from 2 ratings |
    And I should see "Individual ratings"
    And I should see "4 of 5" in the "Sam Student" "table_row"
    And I should see "3 of 5" in the "Robin Reviewer" "table_row"

  Scenario: The report can be narrowed down to one activity
    Given I am on the "Course 1" course page logged in as "teacher1"
    And I select "Star ratings" from secondary navigation
    When I set the field "Activity" to "Course design"
    And I press "Apply filter"
    Then I should see "No ratings yet"
    And I should not see "Sam Student"
    And I should not see "Robin Reviewer"

  Scenario: A student has no report to look at
    When I am on the "Course 1" course page logged in as "student1"
    Then "Star ratings" "link" should not exist
