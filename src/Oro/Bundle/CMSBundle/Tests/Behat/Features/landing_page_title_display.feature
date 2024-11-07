@ticket-BB-24177
@fixture-OroCMSBundle:web_catalog.yml
Feature: Landing Page Title Display

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create Landing Page
    Given I proceed as the Admin
    When I login as administrator
    And I go to Marketing/ Landing Pages
    And I click "Create Landing Page"
    And I fill "CMS Page Form" with:
      | Titles              | Test landing page |
      | Do Not Render Title | true              |
    And I save and close form
    Then I should see "Page has been saved" flash message

  Scenario: Create Content Node for Landing Page
    When I set "Default Web Catalog" as default web catalog
    And I go to Marketing/ Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I save form
    And I click "Create Content Node"
    And I fill "Content Node" with:
      | Title | Test landing page content variant |
    And I click on "Show Variants Dropdown"
    And I click "Add Landing Page"
    And I fill "Content Node Form" with:
      | Landing Page | Test landing page |
    And I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Add Landing page to a new menu item
    When go to System/Storefront Menus
    And I click "view" on row "commerce_main_menu" in grid
    And click "Create Menu Item"
    And I fill "Commerce Menu Form" with:
      | Title       | Test landing page menu item |
      | Target Type | URI                         |
      | URI         | test-landing-page           |
    And I save form
    Then I should see "Menu item saved successfully" flash message

  Scenario: Check that title is displayed when page opened as content variant
    Given I proceed as the Buyer
    When I am on the homepage
    And I click on "Main Menu Button"
    Then I should see "Test landing page content variant"
    When I click "Test landing page content variant"
    Then Page title equals to "Test landing page content variant"
    And I should see "Test landing page content variant" in the "Page Title" element

  Scenario: Check that title is not displayed when page opened as cms page
    When I click on "Main Menu Button"
    Then I should see "Test landing page menu item"
    When I click "Test landing page menu item"
    Then Page title equals to "Test landing page"
    And I should not see a "Page Title" element

  Scenario: Make title visible
    Given I proceed as the Admin
    When I go to Marketing/ Landing Pages
    And I click "edit" on row "Test landing page" in grid
    And I fill "CMS Page Form" with:
      | Do Not Render Title | false |
    And I save and close form
    Then I should see "Page has been saved" flash message

  Scenario: Check that title is displayed when page opened as cms page with disabled no render title option
    Given I proceed as the Buyer
    When I reload the page
    Then Page title equals to "Test landing page"
    And I should see "Test landing page" in the "Page Title" element
