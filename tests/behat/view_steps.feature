@mod @mod_browse
Feature: Students see the steps of a browse activity
  In order to work through external content
  As a student
  I need to see the steps, my progress and which steps are available

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | One      | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |

  Scenario: The checklist is shown with the open in new window button
    Given the following "activities" exist:
      | activity | course | name               | externalurl                | display |
      | browse   | C1     | Survey walkthrough | https://example.com/survey | 3       |
    And the following "mod_browse > steps" exist:
      | browse             | title          | type   | url                              |
      | Survey walkthrough | Read the intro | manual |                                  |
      | Survey walkthrough | Open form      | link   | https://example.com/survey/start |
    When I am on the "Survey walkthrough" "browse activity" page logged in as student1
    Then I should see "0 of 2 steps completed"
    And I should see "Read the intro"
    And I should see "Open form"
    And "Open content in new window" "link" should exist
    And "Open step link" "link" should exist

  Scenario: In sequential mode later steps are locked
    Given the following "activities" exist:
      | activity | course | name          | externalurl                | display | sequential |
      | browse   | C1     | Guided survey | https://example.com/survey | 3       | 1          |
    And the following "mod_browse > steps" exist:
      | browse        | title          | type   |
      | Guided survey | First things   | manual |
      | Guided survey | Then this      | manual |
    When I am on the "Guided survey" "browse activity" page logged in as student1
    Then I should see "Locked" in the "Then this" "list_item"
