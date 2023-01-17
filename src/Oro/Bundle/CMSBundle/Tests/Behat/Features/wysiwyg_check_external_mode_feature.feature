@ticket-BB-20965
Feature: Wysiwyg check "External Mode" feature
  In order to be able to edit landing page
  As an administrator
  I turn on External Mode for WYSIWYG

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |

  Scenario: Check confirmation when exit external mode
    Given I proceed as the Admin
    And I login as administrator
    And I go to Marketing/ Landing Page
    And I click edit "About" in grid
    And I click "SettingsTab"
    And I click "External Mode Button"
    And I click "External Mode Button"
    Then should see "If you exit external markup mode, the editor may change the source code and break the imported content markup and styles." in confirmation dialogue
    And I click "OK" in confirmation dialogue

  Scenario: Check content in external mode
    And I click "External Mode Button"
    And I import content "<div class=\"test\"></div><style>.test {background: -moz-linear-gradient(top, rgba(255, 255, 255, 1) 60%, rgba(255, 255, 255, 0) 100%);background: -webkit-linear-gradient(top, rgba(255, 255, 255, 1) 60%, rgba(255, 255, 255, 0) 100%);background: linear-gradient(to bottom, rgba(255, 255, 255, 1) 60%, rgba(255, 255, 255, 0) 100%);}</style>" to "CMS Page Content" WYSIWYG editor
    Then I should see imported "<div class=\"test\"></div><style>.test {  background: -moz-linear-gradient(top, rgba(255, 255, 255, 1) 60%, rgba(255, 255, 255, 0) 100%);  background: -webkit-linear-gradient(top, rgba(255, 255, 255, 1) 60%, rgba(255, 255, 255, 0) 100%);  background: linear-gradient(to bottom, rgba(255, 255, 255, 1) 60%, rgba(255, 255, 255, 0) 100%);  }</style>" content in "CMS Page Content" WYSIWYG editor
    And I save and close form
    And I go to Marketing/ Landing Page
    And I click edit "About" in grid
    Then I should see imported "<div class=\"test\"></div><style>.test{  background: -moz-linear-gradient(top, rgba(255, 255, 255, 1) 60%, rgba(255, 255, 255, 0) 100%);  background: -webkit-linear-gradient(top, rgba(255, 255, 255, 1) 60%, rgba(255, 255, 255, 0) 100%);  background: linear-gradient(to bottom, rgba(255, 255, 255, 1) 60%, rgba(255, 255, 255, 0) 100%);  }</style>" content in "CMS Page Content" WYSIWYG editor

  Scenario: Check content in normal mode
    And I go to Marketing/ Landing Page
    And I click edit "About" in grid
    And I click "SettingsTab"
    And I click "External Mode Button"
    Then should see "If you exit external markup mode, the editor may change the source code and break the imported content markup and styles." in confirmation dialogue
    And I click "OK" in confirmation dialogue
    And I import content "<div class=\"test\"></div><style>.test {background: -moz-linear-gradient(top, rgba(255, 255, 255, 1) 60%, rgba(255, 255, 255, 0) 100%);background: -webkit-linear-gradient(top, rgba(255, 255, 255, 1) 60%, rgba(255, 255, 255, 0) 100%);background: linear-gradient(to bottom, rgba(255, 255, 255, 1) 60%, rgba(255, 255, 255, 0) 100%);}</style>" to "CMS Page Content" WYSIWYG editor
    Then I should see imported "<div class=\"test\"></div><style>.test{  background:linear-gradient(to bottom, rgba(255, 255, 255, 1) 60%, rgba(255, 255, 255, 0) 100%);  }</style>" content in "CMS Page Content" WYSIWYG editor


