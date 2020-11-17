@regression
@pricing-storage-combined
@ticket-BB-8594
@ticket-BB-17545
@automatically-ticket-tagged
@fixture-OroPricingBundle:ProductPrices.yml
Feature: Minimum Price Selection Strategies
  As an Administrator
  I want be able to configure Minimum Price Selection Strategy
  So that, we need to add switcher for Minimum Price Selection Strategy selection

  Scenario: Create two session
    Given I login as AmandaRCole@example.org the "Buyer" at "first_session" session
    And I login as administrator and use in "second_session" as "Admin"

  Scenario: Minimum price strategy is available by default
    Given I operate as the Admin
    When I go to System/Configuration
    And I follow "Commerce/Catalog/Pricing" on configuration sidebar
    Then I should see "Pricing Strategy"
    Then I should see "Minimal prices"
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Create product with prices
    Given I operate as the Admin
    When I go to Products/Products
    And I click "Create Product"
    And I click "NewCategory"
    And I click "Continue"
    And fill "ProductForm" with:
      | SKU        | product2 |
      | Name       | product2 |
      | Status     | Enabled  |
    And click "AddPrice"
    And fill "ProductPriceForm" with:
      | Price List     | Default Price List |
      | Quantity       | 1                  |
      | Value          | 1                  |
      | Currency       | $                  |
    And I submit form
    Then I should see "Product has been saved" flash message
    When I continue as the Buyer
    And I am on the homepage
    And I click "NewCategory"
    And I click "View Details" for "product2" product
    And I should see "$1.00"

  Scenario: Add new price to product price
    Given I operate as the Admin
    And I go to Products/Products
    And click Edit PSKU1 in grid
    And click "AddPrice"
    And fill "ProductPriceForm" with:
      | Price List     | Default Price List |
      | Quantity       | 1                  |
      | Value          | 7                  |
      | Currency       | $                  |
    And I submit form
    Then I should see "Product has been saved" flash message
    When I continue as the Buyer
    And I am on the homepage
    And I click "NewCategory"
    And I click "View Details" for "PSKU1" product
    And I should see "$7.00"

  Scenario: Price for Customer
    Given I operate as the Admin
    When I go to Customers/Customers
    And click Edit first customer in grid
    And I choose Price List "priceListForCustomer" in 1 row
    And I submit form
    Then I should see "Customer has been saved" flash message
    When I continue as the Buyer
    And I reload the page
    And I should see "$6.00"

  Scenario: Change price list for customer
    Given I operate as the Admin
    When I go to Customers/Customers
    And click Edit first customer in grid
    And I choose Price List "Default Price List" in 1 row
    And I submit form
    Then I should see "Customer has been saved" flash message
    When I continue as the Buyer
    And I reload the page
    And I should see "$7.00"

  Scenario: Price fallback user on group
    Given I operate as the Admin
    When I go to Customers/Customer Groups
    And click Edit Group with PriceList in grid
    And I choose Price List "priceListForGroup" in 1 row
    And I submit form
    Then I should see "Customer group has been saved" flash message
    When I continue as the Buyer
    And I reload the page
    And I should see "$5.00"

  Scenario: Price fallback user on website
    Given I operate as the Admin
    When I go to System/Websites
    And click Edit Default in grid
    And I choose Price List "priceListForWebsite" in 1 row
    And I submit form
    Then I should see "Website has been saved" flash message
    When I continue as the Buyer
    And I reload the page
    And I should see "$4.00"

  Scenario: Change group of customer
    Given I operate as the Admin
    When I go to Customers/Customer Groups
    And click Edit Group with PriceList2 in grid
    And I choose Price List "priceListForCustomerGroup2" in 1 row
    And I submit form
    Then I should see "Customer Group has been saved" flash message
    When I go to Customers/Customers
    And click Edit first customer in grid
    And I fill form with:
      | Group | Group with PriceList2 |
    And I submit form
    Then I should see "Customer has been saved" flash message
    When I continue as the Buyer
    And I reload the page
    And I should see "$4.00"
    And I should see "$10.00"

  Scenario: Pricing strategy changing
    Given I operate as the Admin
    When I go to System/Configuration
    And I follow "Commerce/Catalog/Pricing" on configuration sidebar
    And I fill "PriceSelectionStrategyForm" with:
      | Use Default          | false             |
      | Pricing Strategy     | Merge by priority |
    And I submit form
    Then I should see "Configuration saved" flash message
    When I continue as the Buyer
    And I reload the page
    And I should see "$7.00"
    And I should see "$10.00"

  Scenario: Remove group
    Given I operate as the Admin
    When I go to Customers/Customer Groups
    And click Delete Group with PriceList2 in grid
    And I confirm deletion
    Then I should see "Customer Group deleted" flash message
    When I continue as the Buyer
    And I reload the page
    And I should see "$7.00"
    And I should not see "$10.00"

  Scenario: Unassign price list from customer
    Given I operate as the Admin
    When I go to Customers/Customers
    And click Edit first customer in grid
    And click "UnassignPriceList"
    And I submit form
    Then I should see "Customer has been saved" flash message
    When I continue as the Buyer
    And I reload the page
    And I should not see "$5.00"

  Scenario: Unassign price list from group
    Given I operate as the Admin
    When I go to Customers/Customer Groups
    And click Edit Group with PriceList in grid
    And click "UnassignPriceList"
    And I submit form
    Then I should see "Customer group has been saved" flash message
    When I continue as the Buyer
    And I reload the page
    And I should see "$4.00"

  Scenario: Unassign price list from website
    Given I operate as the Admin
    When I go to System/Websites
    And click Edit Default in grid
    And click "UnassignPriceList"
    And I submit form
    Then I should see "Website has been saved" flash message
    When I continue as the Buyer
    And I reload the page
    And I should see "$7.00"

