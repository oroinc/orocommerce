@regression
@pricing-storage-combined
@ticket-BB-27513
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
@fixture-OroProductBundle:ProductsExportFixture.yml

Feature: CPL processing with versioned price deleted via UI

  Scenario: Prepare Admin session
    Given I login as administrator

  Scenario: Create Price List
    When I go to Sales/ Price Lists
    And I click "Create Price List"
    And I fill "Price List Form" with:
      | Name       | TESTPL        |
      | Currencies | US Dollar ($) |
      | Active     | true          |
    And I save and close form
    Then I should see "Price List has been saved" flash message

  Scenario: Assign Price List to customer
    When I go to Customers/Customers
    And click Edit AmandaRCole in grid
    And I fill form with:
      | Fallback | Current customer only |
    And I click "Add Price List"
    And I choose Price List "TESTPL" in 1 row
    And I submit form
    Then I should see "Customer has been saved" flash message

  Scenario: Import price to create a versioned price
    When I go to Sales/ Price Lists
    And click View TESTPL in grid
    And I download "ProductPrice" Data Template file
    And I fill template with data:
      | Product SKU | Quantity | Unit Code | Price | Currency |
      | PSKU2       | 1        | item      | 40    | USD      |
    And I import file
    And I reload the page
    Then I should see following grid:
      | Product SKU | Product name | Quantity | Unit | Value | Currency |
      | PSKU2       | Product 2    | 1        | item | 40.00 | USD      |

  Scenario: Check imported price is available in CPL
    When I go to Sales/Price Calculation Details
    And I filter SKU as Contains "PSKU2"
    And fill "Price Calculation Details Grid Sidebar" with:
      | Website  | Default     |
      | Customer | AmandaRCole |
    And click on PSKU2 in grid
    Then I should see next prices for "Customer Prices":
      | Item (USD) |
      | 1 $40.00   |

  Scenario: Delete the imported (versioned) price from the price list grid
    When I go to Sales/ Price Lists
    And click View TESTPL in grid
    And I click delete PSKU2 in "Price list Product prices Grid"
    And I click "Yes" in confirmation dialogue
    Then there are no records in "Price list Product prices Grid"

  Scenario: Check that the deleted versioned price is removed from CPL
    When I go to Sales/Price Calculation Details
    And I filter SKU as Contains "PSKU2"
    And fill "Price Calculation Details Grid Sidebar" with:
      | Website  | Default     |
      | Customer | AmandaRCole |
    And click on PSKU2 in grid
    Then I should see "Customer Prices No Prices"
