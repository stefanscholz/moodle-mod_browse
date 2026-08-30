@mod @mod_browse
Feature: Teachers manage the steps of a browse activity
  In order to guide students through external content
  As a teacher
  I need to create, edit, reorder and delete steps

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity | course | name               | externalurl                |
      | browse   | C1     | Survey walkthrough | https://example.com/survey |

  Scenario: Add a step
    Given I am on the "Survey walkthrough" "browse activity" page logged in as teacher1
    When I navigate to "Manage steps" in current page administration
    Then I should see "No steps have been created yet."
    When I press "Add step"
    And I set the following fields to these values:
      | Title                      | Open the survey                  |
      | How is the step completed? | By visiting a link               |
      | Step URL                   | https://example.com/survey/start |
    And I press "Save changes"
    Then I should see "Open the survey"
    And I should see "By visiting a link"
    And I should see "https://example.com/survey/start"

  Scenario: Edit, reorder and delete existing steps
    Given the following "mod_browse > steps" exist:
      | browse             | title           | type     |
      | Survey walkthrough | Read the intro  | manual   |
      | Survey walkthrough | Submit answers  | callback |
    And I am on the "Survey walkthrough" "browse activity" page logged in as teacher1
    When I navigate to "Manage steps" in current page administration
    Then "Read the intro" "text" should appear before "Submit answers" "text"
    And I should see "Return link" in the "Submit answers" "table_row"
    When I click on "Edit" "link" in the "Read the intro" "table_row"
    And I set the field "Title" to "Read the introduction"
    And I press "Save changes"
    Then I should see "Read the introduction"
    When I click on "Move up" "link" in the "Submit answers" "table_row"
    Then "Submit answers" "text" should appear before "Read the introduction" "text"
    When I click on "Delete" "link" in the "Read the introduction" "table_row"
    And I press "Continue"
    Then I should not see "Read the introduction"
    And I should see "Submit answers"
