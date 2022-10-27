@regression
@ticket-BB-15285
@fixture-OroWebCatalogBundle:web_catalog_with_customers.yml
Feature: Multiple restrictions for content nodes
  In order to manage content in web catalog tree
  As an admin
  I want to be able to set multiple restrictions for content node at backend

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | User  | second_session |

  Scenario: Add customer restrictions and test it on frontstore
    Given I proceed as the Admin
    And login as administrator
    And I set "Default Web Catalog" as default web catalog
    And I go to Marketing / Web Catalogs
    And I click view Default Web Catalog in grid
    And I click "Edit Content Tree"
    And I click "Add"
    When I fill "Content Node" with:
      | Restriction1 Customer | Customer 1 |
      | Restriction2 Customer | Customer 2 |
    And I click on "Show Variants Dropdown"
    And I click "Add System Page"
    And I fill "Content Node Form" with:
      | Titles            | Root Node                               |
      | System Page Route | Oro Frontend Root (Welcome - Home page) |
    And I save form
    Then I should see "Content Node has been saved" flash message

    # Re-save nodes to actualize scopes for ContentNode and ContentVariant that was not loaded by fixtures
    When I click "Clearance"
    And I save form
    Then I should see "Content Node has been saved" flash message
    When I click "By Brand"
    And I save form
    Then I should see "Content Node has been saved" flash message

    When I proceed as the User
    And I am on the homepage
    Then I should not see "Clearance" in main menu
    And I should not see "Clearance/By Brand" in main menu
    When I signed in as AmandaRCole@example.org on the store frontend
    Then I should see "Clearance" in main menu
    And I should see "Clearance/By Brand" in main menu

  Scenario: Change customer for logged user and test restrictions
    When I proceed as the Admin
    And I go to Customers / Customer Users
    And I click edit Amanda in grid
    And I fill form with:
      | Customer | Customer 2 |
    And I save form
    Then I should see "Customer User has been saved" flash message

    When I proceed as the User
    And I reload the page
    Then I should see "Clearance" in main menu
    And I should see "Clearance/By Brand" in main menu

  Scenario: Set Customer Group restriction for child node
    When I proceed as the Admin
    And I go to Marketing / Web Catalogs
    And I click view Default Web Catalog in grid
    And I click "Edit Content Tree"
    And I click "By Brand"
    And I uncheck "Inherit Parent"
    And I click "Add"
    And I fill "Content Node" with:
      | Restriction1 CustomerGroup | Customer Group 2 |
      | Restriction2 Customer      | Customer 1       |
    And I save form
    Then I should see "Content Node has been saved" flash message

    When I proceed as the User
    And I reload the page
    Then I should see "Clearance" in main menu
    And I should see "Clearance/By Brand" in main menu
