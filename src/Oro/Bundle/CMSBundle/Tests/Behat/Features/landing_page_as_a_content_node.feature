@ticket-BB-14586
@fixture-OroCMSBundle:web_catalog.yml
Feature: Landing Page as a Content Node
  In order to see landing page in main menu
  As an Administrator
  I need to be able to create a Content Node with Content Variant that is Landing Page

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create Landing Page
    Given I proceed as the Admin
    And I login as administrator
    And I go to Marketing/ Landing Pages
    And I click "Create Landing Page"
    And I fill in Landing Page Titles field with "Test page"
    Then I should see URL Slug field filled with "test-page"
    When I save and close form
    Then I should see "Page has been saved" flash message

  Scenario: Create Content Node for Landing Page
    Given I set "Default Web Catalog" as default web catalog
    When I go to Marketing/ Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I save form
    When I click "Create Content Node"
    And I fill "Content Node" with:
      | Title | Test page |
      | Slug  | test-page |
    And I click on "Show Variants Dropdown"
    And I click "Add Landing Page"
    And I fill "Content Node Form" with:
      | Landing Page | Test page |
    Then I save form
    And I should see "Content Node has been saved" flash message

  Scenario: Open Landing Page
    Given I proceed as the Buyer
    And I am on the homepage
    Then I should see "Test page"
    When I click "Test page"
    Then Page title equals to "Test page"

  Scenario: Delete Landing Page
    Given I proceed as the Admin
    And I go to Marketing/ Landing Pages
    When I click delete Test page in grid
    And I confirm deletion
    Then I should see "Landing Page deleted" flash message

  Scenario: Check main menu
    Given I proceed as the Buyer
    When I am on the homepage
    Then I should not see "Test page"
