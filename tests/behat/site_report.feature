@mod @mod_starrating
Feature: See the star ratings of the whole site
  In order to compare what is rated well and what is not
  As an administrator
  I need the averages over all courses and the individual ratings behind them

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
      | Course 2 | C2        | topics |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Sam       | Student  | student1@example.com |
      | student2 | Robin     | Reviewer | student2@example.com |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
      | student2 | C1     | student |
      | student1 | C2     | student |
    # Two courses ask about the same thing on scales of different lengths, which is
    # what the site report is for. They share a name, so the ratings below name the
    # activities by their idnumber.
    And the following "activities" exist:
      | activity   | course | name             | section | maxstars | idnumber |
      | starrating | C1     | Teaching quality | 1       | 5        | tq5      |
      | starrating | C2     | Teaching quality | 1       | 10       | tq10     |
      | starrating | C1     | Course design    | 1       | 5        | cd5      |
    And the following "mod_starrating > ratings" exist:
      | activity | user     | rating |
      | tq5      | student1 | 4      |

  Scenario: The averages are reported per course and activity
    Given the following "mod_starrating > ratings" exist:
      | activity | user     | rating |
      | cd5      | student2 | 3      |
    And I log in as "admin"
    When I navigate to "Reports > Star rating averages" in site administration
    Then the following should exist in the "Average rating per selected grouping" table:
      | Course   | Activity         | Ratings | Average                         |
      | Course 1 | Course design    | 1       | Average 3.0 of 5 from 1 ratings |
      | Course 1 | Teaching quality | 1       | Average 4.0 of 5 from 1 ratings |
    # Course 2 has a star rating too, but nobody has rated it yet.
    And the following should not exist in the "Average rating per selected grouping" table:
      | Course   | Activity         |
      | Course 2 | Teaching quality |

  Scenario: Activities on different scales are averaged as a share of their scale
    Given the following "mod_starrating > ratings" exist:
      | activity | user     | rating |
      | tq10     | student1 | 8      |
    And I log in as "admin"
    And I navigate to "Reports > Star rating averages" in site administration
    And the following should exist in the "Average rating per selected grouping" table:
      | Course   | Activity         | Ratings | Average                           |
      | Course 2 | Teaching quality | 1       | Average 8.0 of 10 from 1 ratings  |
    When I set the field "Group by" to "Activity"
    And I press "Apply filter"
    Then I should see "Average 80.0% of the scale from 2 ratings, on scales of different lengths"

  Scenario: The report can be narrowed down to one course
    Given I log in as "admin"
    And I navigate to "Reports > Star rating averages" in site administration
    When I set the field "Course" to "Course 2"
    And I press "Apply filter"
    Then I should see "No ratings match the current filter."

  Scenario: The individual ratings name the people who gave them
    Given I log in as "admin"
    And I navigate to "Reports > Star rating averages" in site administration
    When I follow "View the individual ratings"
    Then I should see "Star ratings by user"
    And the following should exist in the "generaltable" table:
      | Course   | Activity         | User        | Rating |
      | Course 1 | Teaching quality | Sam Student | 4 of 5 |
    And I follow "View the averages"
    And I should see "Star rating averages"
