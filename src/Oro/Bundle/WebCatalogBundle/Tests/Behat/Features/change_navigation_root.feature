@ticket-BB-15579
@fixture-OroWebCatalogBundle:web_catalog.yml
@fixture-OroWebCatalogBundle:web_catalog_additional.yml
Feature: Change navigation root
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
    When I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click on "Show Variants Dropdown"
    And I click "Add System Page"
    And I fill "Content Node Form" with:
      | System Page Route | Oro Frontend Root (Welcome - Home page) |
    And I save form
    When I click on "Clearance"
    And I click on "Show Variants Dropdown"
    And I click "Add System Page"
    And I fill "Content Node Form" with:
      | System Page Route | Oro Frontend Root (Welcome - Home page) |
    And I save form
    Then I should see "Content Node has been saved" flash message
    When I click on "By Brand"
    And I click on "Show Variants Dropdown"
    And I click "Add System Page"
    And I fill "Content Node Form" with:
      | System Page Route | Oro Frontend Root (Welcome - Home page) |
    And I save form
    Then I should see "Content Node has been saved" flash message
    When I click "Create Content Node"
    And I fill "Content Node" with:
      | Title      | Test |
      | Slug       | test |
    And I click on "Show Variants Dropdown"
    And I click "Add System Page"
    And I fill "Content Node Form" with:
      | System Page Route | Oro Frontend Root (Welcome - Home page) |
    And I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Check root navigation on front store by default
    Given I proceed as the Buyer
    When I am on the homepage
    And I should see "Clearance" in main menu
    And I should see "Test" in main menu

  Scenario: Change root navigation in system config
    Given I proceed as the Admin
    And I go to System/ Configuration
    And follow "System Configuration/Websites/Routing" on configuration sidebar
    And uncheck "Use default" for "Navigation Root" field
    And I click on "Clearance"
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check changed root navigation on front store
    Given I proceed as the Buyer
    When I reload the page
    And I should not see "Clearance" in main menu
    And I should see "By Brand" in main menu

  Scenario: Change web catalog
    Given I proceed as the Admin
    And I set "Additional Web Catalog" as default web catalog
    When I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Additional Web Catalog" in grid
    And I click on "Show Variants Dropdown"
    And I click "Add System Page"
    And I fill "Content Node Form" with:
      | System Page Route | Oro Frontend Root (Welcome - Home page) |
    When I save form
    Then I should see "Content Node has been saved" flash message
    When I click on "On Sale"
    And I click on "Show Variants Dropdown"
    And I click "Add System Page"
    And I fill "Content Node Form" with:
      | System Page Route | Oro Frontend Root (Welcome - Home page) |
    And I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Check additional web catalog on front store
    Given I proceed as the Buyer
    When I reload the page
    And I should not see "By Brand" in main menu
    And I should see "On Sale" in main menu
