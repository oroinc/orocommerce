@regression
@ticket-BB-21248
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml

Feature: Content nodes with sub nodes restrictions
  Make sure that nodes that partially meet the selection criteria are also displayed in the menu.
  (Default criteria: default website and non-authenticated visitor)

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | User  | second_session |

  Scenario: Create default web catalog
    Given I proceed as the Admin
    And login as administrator
    And go to Marketing/ Web Catalogs
    When I click "Create Web Catalog"
    And fill form with:
      | Name | Default Web Catalog |
    And click "Save and Close"
    Then I should see "Web Catalog has been saved" flash message

  Scenario: Create root node
    Given I click "Edit Content Tree"
    When I fill "Content Node Form" with:
      | Titles | Root node |
    And click "Add System Page"
    And save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Create first node with specified restriction
    Given I click "Create Content Node"
    When I uncheck "Inherit Parent"
    And fill "Content Node" with:
      | Title                      | Node A                     |
      | Slug                       | node-a                     |
      | Restriction1 CustomerGroup | Non-Authenticated Visitors |
    And click "Add System Page"
    And save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Create second node with specified restriction
    Given I click "Root node"
    And click "Create Content Node"
    When I uncheck "Inherit Parent"
    And fill "Content Node" with:
      | Title                | Node B  |
      | Slug                 | node-b  |
      | Restriction1 Website | Default |
    And click "Add System Page"
    And save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Set root navigation in system config
    Given I go to System/ Configuration
    And follow "System Configuration/Websites/Routing" on configuration sidebar
    And uncheck "Use default" for "Web Catalog" field
    And fill "Routing Settings Form" with:
      | Web Catalog | Default Web Catalog |
    When I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check nodes on storefront
    Given I proceed as the User
    And I am on the homepage
    Then I should see "Node A" in main menu
    And should see "Node B" in main menu

  Scenario: Check nodes on storefront from Amanda user
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I am on the homepage
    Then I should not see "Node A" in main menu
    And should see "Node B" in main menu

  Scenario: Check web catalog cache
    Given I proceed as the Admin
    And go to Marketing/ Web Catalogs
    And click "Edit Content Tree" on row "Default Web Catalog" in grid

  Scenario: Change restrictions for Node A
    Given I click "Node A"
    And fill "Content Node" with:
      | Restriction1 CustomerGroup |         |
      | Restriction1 Website       | Default |
    And save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Change restrictions for Node B
    Given I click "Node B"
    And fill "Content Node" with:
      | Restriction1 Website       |                            |
      | Restriction1 CustomerGroup | Non-Authenticated Visitors |
    And save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Check nodes on storefront from Amanda user with changed node restrictions
    Given I proceed as the User
    And I am on the homepage
    Then I should see "Node A" in main menu
    And should not see "Node B" in main menu
