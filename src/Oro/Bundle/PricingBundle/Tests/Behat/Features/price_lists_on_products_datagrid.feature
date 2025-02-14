@ticket-BB-17327
@fixture-OroPricingBundle:PricelistsWithCurrencies.yml
@fixture-OroPricingBundle:ProductPricesManagement.yml

Feature: Price lists on Products Datagrid
  In order to manage prices on the products grid
  As an Administrator
  I need to be able to change price lists on products grid with the same list of enabled currencies

  Scenario: Enable currency on products grid
    Given I login as administrator
    When I go to Products/ Products
    Then I shouldn't see "Price (USD)" column in grid
    And I shouldn't see "Price (EUR)" column in grid
    And the "USD" checkbox should not be checked
    And the "EUR" checkbox should not be checked

  Scenario: Select first price list
    Given I check "USD"
    And I should see "Price (USD)" column in grid
    And I shouldn't see "Price (EUR)" column in grid
    When I select price list with name "first price list" on sidebar
    Then I should see "Price (USD)" column in grid
    And I shouldn't see "Price (EUR)" column in grid
    And the "USD" checkbox should be checked
    And the "EUR" checkbox should not be checked

  Scenario: Select second price list
    Given I check "EUR"
    And I should see "Price (USD)" column in grid
    And I should see "Price (EUR)" column in grid
    When I select price list with name "second price list" on sidebar
    Then I should see "Price (USD)" column in grid
    And I should see "Price (EUR)" column in grid
    And the "USD" checkbox should be checked
    And the "EUR" checkbox should be checked

  Scenario: Disable all permissions for price lists
    Given I go to System/User Management/Roles
    And click edit "Administrator" in grid
    And select following permissions:
      | Price List | View:None | Create:None | Edit:None | Delete:None | Assign:None | Recalculate:None |
    And I save and close form

  Scenario: Check product list page
    Given I go to Products/ Products
    Given I should not see "Default Price List" entity for "PriceListSidebarSelector" select
    And I should not see "first price list" entity for "PriceListSidebarSelector" select
    And I should not see "second price list" entity for "PriceListSidebarSelector" select
    And shouldn't see "Price (USD)" column in grid
    And shouldn't see "Price (EUR)" column in grid
    And records in grid should be 1
