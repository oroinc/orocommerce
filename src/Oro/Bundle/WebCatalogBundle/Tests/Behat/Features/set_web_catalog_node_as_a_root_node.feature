@ticket-BB-15579
@fixture-OroWebCatalogBundle:web_catalog.yml
@fixture-OroWebCatalogBundle:web_catalog_additional.yml
Feature: Set web catalog node as a root node
  In order to change a root node for main menu
  As site administrator
  I need to be able to choose Web Catalog node as a root node for main menu

  Scenario: Logged in as buyer and manager on different window sessions
    Given sessions active:
      | Admin  | first_session  |
      | Buyer  | second_session |

  Scenario: Prepare Web Catalog
    Given I proceed as the Admin
    And I login as administrator
    And I set "Default Web Catalog" as default web catalog
    And I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click on "Show Variants Dropdown"
    And I click "Add System Page"
    And I fill "Content Node Form" with:
      | System Page Route | Oro Frontend Root (Welcome - Home page) |
    When I save form
    Then I should see "Content Node has been saved" flash message
    And I click on "Products"
    And I click on "Show Variants Dropdown"
    And I click "Add System Page"
    And I fill "Content Node Form" with:
      | System Page Route | Oro Frontend Root (Welcome - Home page) |
    When I save form
    Then I should see "Content Node has been saved" flash message
    And I click on "Clearance"
    When I save form
    Then I should see "Content Node has been saved" flash message
    And I click on "By Brand"
    And I fill "Content Node Form" with:
      | System Page Route | Oro Frontend Root (Welcome - Home page) |
    When I save form
    Then I should see "Content Node has been saved" flash message
    And I click "Create Content Node"
    And I fill "Content Node" with:
      | Title      | Test |
      | Slug       | test |
    And I click on "Show Variants Dropdown"
    And I click "Add System Page"
    And I fill "Content Node Form" with:
      | System Page Route | Oro Frontend Root (Welcome - Home page) |
    When I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Check root navigation on front store by default
    Given I proceed as the Buyer
    When I am on the homepage
    Then I should see "Products" in main menu
    And I should see "Clearance" in main menu
    When I hover on "Clearance menu item"
    Then I should see "By Brand" in main menu
    And I should see "Test" in main menu

  Scenario: Change root navigation in system config
    Given I proceed as the Admin
    And I go to System/ Configuration
    And follow "System Configuration/Websites/Routing" on configuration sidebar
    And uncheck "Use default" for "Navigation Root" field
    And I click on "Clearance"
    When I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check changed root navigation on front store
    Given I proceed as the Buyer
    When I reload the page
    Then I should not see "Clearance" in main menu
    And I should see "By Brand" in main menu

  Scenario: Set "Products" as navigation root in system config
    Given I proceed as the Admin
    And I go to System/ Configuration
    And follow "System Configuration/Websites/Routing" on configuration sidebar
    And I click on "Products"
    When I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check changed navigation on front store
    Given I proceed as the Buyer
    When I reload the page
    Then I should not see "Clearance" in main menu

  Scenario: Reorder content nodes for Default Web Catalog
    Given I proceed as the Admin
    And I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click on "Clearance"
    And I click "Create Content Node"
    And I fill "Content Node" with:
      | Title | Headlamps |
      | Slug  | headlamps |
    And I click on "Show Variants Dropdown"
    And I click "Add System Page"
    And I fill "Content Node Form" with:
      | System Page Route | Oro Frontend Root (Welcome - Home page) |
    When I save form
    Then I should see "Content Node has been saved" flash message
    When I drag and drop "Headlamps" before "By Brand"
    Then I should see "By Brand" after "Headlamps" in tree

  Scenario: Check changed order of content nodes in system config
    Given I go to System/ Configuration
    When follow "System Configuration/Websites/Routing" on configuration sidebar
    Then I should see "By Brand" after "Headlamps" in tree

  Scenario: Set "Clearance" as navigation root in system config
    Given I click on "Clearance"
    When I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check changed order of content nodes on front store
    Given I proceed as the Buyer
    When I reload the page
    Then I should see "By Brand" in main menu
    And I should see "Headlamps" in main menu
    And I should see "Headlamps By Brand"
    And I should not see "By Brand Headlamps"

  Scenario: Remove current navigation root
    Given I proceed as the Admin
    And I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click on "Clearance"
    And I click "Delete"
    When I click "Yes, Delete" in confirmation dialogue
    Then I should see "Content Node deleted" flash message

  Scenario: Check root navigation on front store by default
    Given I proceed as the Buyer
    When I reload the page
    Then I should see "Products" in main menu
    And I should not see "Clearance" in main menu

  Scenario: Change web catalog
    Given I proceed as the Admin
    And I set "Additional Web Catalog" as default web catalog
    And I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Additional Web Catalog" in grid
    And I click on "Show Variants Dropdown"
    And I click "Add System Page"
    And I fill "Content Node Form" with:
      | System Page Route | Oro Frontend Root (Welcome - Home page) |
    When I save form
    Then I should see "Content Node has been saved" flash message
    And I click on "On Sale"
    And I click on "Show Variants Dropdown"
    And I click "Add System Page"
    And I fill "Content Node Form" with:
      | System Page Route | Oro Frontend Root (Welcome - Home page) |
    When I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Check additional web catalog on front store
    Given I proceed as the Buyer
    When I reload the page
    Then I should not see "By Brand" in main menu
    And I should see "On Sale" in main menu
