@ticket-BB-20511
@fixture-OroProductBundle:ProductBrandAclFixture.yml
Feature: Brand cannot be selected on product form when edit permission is set to none
  In order to have the ability to manage view access to brands
  As an Administrator
  I want to manage view ACL by role for entity "Brand"
  I want to have the ability to manage products even in the case when I have no right to view "Brands"
  I want to have the ability to view the brands available for me
  I want to have the ability to search the brands available for me

  Scenario: Set brand ACL view permission to None and check menu item
    Given I login as administrator
    When I go to System/User Management/Roles
    And click edit "Administrator" in grid
    And select following permissions:
      | Brand | View:None |
    And I save and close form
    Then I should see "Role saved" flash message
    And I should not see Products/Brand in main menu

  Scenario: Check search when view brand permission is set to None
    Given I reload the page
    When I click "Search"
    And type "Acme" in "search"
    Then I should see 0 search suggestions
    When I click "Search Submit"
    Then I should see "No results were found to match your search."
    When I click "Search"
    And type "DefaultBrandLtd" in "search"
    Then I should see 0 search suggestions
    When I click "Search Submit"
    Then I should see "No results were found to match your search."

  Scenario: Check brand field is not shown on product form when view permission is set to None
    When I go to Products/ Products
    And click "Create Product"
    Then I click "Continue"
    And I should not see "Brand"
    When I fill "Create Product Form" with:
      | SKU    | TestProduct123   |
      | Name   | Test Product 123 |
      | Status | Enable           |
    And save and close form
    Then I should not see "Brand"
    When I click "Edit"
    Then I should not see "Brand"
    And I click "Cancel"

  Scenario: Set brand ACL view permission to Global
    When I go to System/User Management/Roles
    And click edit "Administrator" in grid
    And select following permissions:
      | Brand | View:Global |
    And I save and close form
    Then I should see "Role saved" flash message
    And I should see Products/Brand in main menu

  Scenario: Check search when view brand permission is set to Global
    Given I reload the page
    When I click "Search"
    And type "Acme" in "search"
    Then I should see 1 search suggestions
    When I click "Search Submit"
    Then I should see following search results:
      | Title | Type  |
      | ACME  | Brand |
    When I click "Search"
    And type "DefaultBrandLtd" in "search"
    Then I should see 1 search suggestions
    When I click "Search Submit"
    Then I should see following search results:
      | Title           | Type  |
      | DefaultBrandLtd | Brand |

  Scenario: Check brand field is shown on product form when view permission is set to Global
    When I go to Products/ Products
    And click "Create Product"
    Then I click "Continue"
    And I should see "Brand"
    And I fill "Create Product Form" with:
      | SKU    | TestProduct456   |
      | Name   | Test Product 456 |
      | Status | Enable           |
    And I click "Brand field"
    And I click "Choose a field.."
    And fill in "Brand" with "DefaultBrandLtd"
    When I save and close form
    Then I should see "Brand"
    And I should see "DefaultBrandLtd"

  Scenario: Set brand ACL edit permission to None
    When I go to System / User Management / Roles
    And I click Edit "Administrator" in grid
    When I select following permissions:
      | Brand | View:Global | Edit:None |
    And save and close form
    Then I should see "Role saved" flash message

  Scenario: Check product brand field items is shown when  edit permission is set to None
    Given I go to Products/ Products
    And I click edit "TestProduct123" in grid
    And click "Brand humburger button"
    And I should see "Total of 2 records"
    Then I should see following "Brand Select Grid" grid:
      | Brand           |
      | ACME            |
      | DefaultBrandLtd |
    And I close ui dialog
    And I click "Brand field"
    And I click "Choose a field.."
    When I fill in "Brand" with "ACME"
    Then I click "Save and Close"

  Scenario: Check search when view brand permission is set to Global and edit permission is set to None
    Given I reload the page
    When I click "Search"
    And type "Acme" in "search"
    Then I should see 1 search suggestions
    When I click "Search Submit"
    Then I should see following search results:
      | Title | Type  |
      | ACME  | Brand |
    When I click "Search"
    And type "DefaultBrandLtd" in "search"
    Then I should see 1 search suggestions
    When I click "Search Submit"
    Then I should see following search results:
      | Title            | Type  |
      | DefaultBrandLtd  | Brand |

  Scenario: Set brand ACL view permission to Business Unit
    When I go to System / User Management / Roles
    And I click Edit "Administrator" in grid
    When I select following permissions:
      | Brand | View:Business Unit | Edit:Global |
    And save and close form
    Then I should see "Role saved" flash message

  Scenario: Check search when view brand permission is set to Business Unit
    Given I reload the page
    When I click "Search"
    And type "Acme" in "search"
    Then I should see 1 search suggestions
    When I click "Search Submit"
    Then I should see following search results:
      | Title | Type  |
      | ACME  | Brand |
    When I click "Search"
    And type "DefaultBrandLtd" in "search"
    Then I should see 0 search suggestions
    When I click "Search Submit"
    Then I should see "No results were found to match your search."

  Scenario: Check is brand grid show only brand owned by business unit when view permission is set to Business Unit
    When I go to Products/Brand
    Then there is 1 records in grid
    And I should see following grid:
      | Name |
      | ACME |

  Scenario: Check product brand field grid items is not shown when view permission is set to Business Unit
    When I go to Products/ Products
    And I click view "TestProduct456" in grid
    And I should not see "Brand"
    Then I click "Edit"
    And I should not see "Brand"
    And I click "Cancel"
