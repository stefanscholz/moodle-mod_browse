@mod @mod_browse @javascript
Feature: Students complete steps and the activity completion follows
  In order to finish a browse activity
  As a student
  I need to tick my steps off and have the completion condition update

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | One      | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | C1        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And the following "activities" exist:
      | activity | course | name               | externalurl                | display | completion | completionsteps |
      | browse   | C1     | Survey walkthrough | https://example.com/survey | 3       | 2          | 1               |
    And the following "mod_browse > steps" exist:
      | browse             | title          | type   |
      | Survey walkthrough | Read the intro | manual |
      | Survey walkthrough | Say it is done | manual |

  Scenario: Ticking all manual steps completes the activity
    Given I am on the "Survey walkthrough" "browse activity" page logged in as student1
    Then I should see "0 of 2 steps completed"
    And the "Complete all steps" completion condition of "Survey walkthrough" is displayed as "todo"
    When I click on "1. Read the intro" "checkbox"
    Then I should see "1 of 2 steps completed"
    And I should see "Done" in the "Read the intro" "list_item"
    When I click on "2. Say it is done" "checkbox"
    Then I should see "2 of 2 steps completed"
    And I reload the page
    And the "Complete all steps" completion condition of "Survey walkthrough" is displayed as "done"

  Scenario: A ticked step can be unticked again
    Given I am on the "Survey walkthrough" "browse activity" page logged in as student1
    When I click on "1. Read the intro" "checkbox"
    Then I should see "1 of 2 steps completed"
    When I click on "1. Read the intro" "checkbox"
    Then I should see "0 of 2 steps completed"
