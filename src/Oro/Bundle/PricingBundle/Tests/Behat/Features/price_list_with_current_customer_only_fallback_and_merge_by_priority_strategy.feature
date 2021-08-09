@regression
@pricing-storage-combined
@ticket-BB-20698
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
@fixture-OroCheckoutBundle:CheckoutProductFixture.yml

Feature: Price List With Current Customer Only fallback And Merge By Priority Strategy
  Checking is price list with "current customer only" fallback works correctly for each strategy

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    And I login as administrator
    And I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer

  Scenario: Check that product have price for Customer
    Given I proceed as the Buyer
    When I type "SKU123" in "search"
    And I click "Search Button"
    Then I should see "400-Watt Bulb Work Light" for "SKU123" product
    And I should see "Your Price: $2.00 / item" for "SKU123" product

  Scenario: Switch Pricing Strategy to Merge by priority
    Given I proceed as the Admin
    And I go to System/Configuration
    And I follow "Commerce/Catalog/Pricing" on configuration sidebar
    When uncheck "Use default" for "Pricing Strategy" field
    And I fill form with:
      | Pricing Strategy | Merge by priority |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Switch fallback for Price List to "Current customer only" for Customer
    Given I proceed as the Admin
    When I go to Customers/Customers
    And click Edit AmandaRCole in grid
    And I fill form with:
      | Fallback | Current customer only |
    And I submit form
    Then I should see "Customer has been saved" flash message

  Scenario: Check that product have no price for Customer anymore
    Given I proceed as the Buyer
    And I type "SKU123" in "search"
    And I click "Search Button"
    Then I should see "400-Watt Bulb Work Light" for "SKU123" product
    And I should see "Price not available" for "SKU123" product
