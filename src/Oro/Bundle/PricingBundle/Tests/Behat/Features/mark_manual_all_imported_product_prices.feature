@regression
@ticket-BB-19081
@fixture-OroPricingBundle:ProductPriceForImportedProductPrices.yml

Feature: Mark manual all imported product prices
  In order to prevent rewriting of imported product price on price recalculation
  As an Administrator
  I want to have type "Manual" on all imported product prices

  Scenario: Create price list with generated product prices
    Given I login as administrator
    And I go to Sales/ Price Lists
    When I click "Create Price List"
    And I fill form with:
      | Name       | Generated Price List     |
      | Currencies | US Dollar ($)            |
      | Active     | true                     |
      | Rule       | product.sku == 'PSKU1'   |
    When I click "Add Price Calculation Rules"
    And I click "Enter expression unit"
    And I click "Enter expression currency"
    And I fill "Price Calculation Rules Form" with:
      | Price for quantity | 1                            |
      | Price Unit         | pricelist[1].prices.unit     |
      | Price Currency     | pricelist[1].prices.currency |
      | Calculate As       | pricelist[1].prices.value    |
      | Priority           | 1                            |
    And I save and close form
    Then I should see "Price List has been saved" flash message and I close it
    When I reload the page
    And I should see following "Price list Product prices Grid" grid:
      | Product SKU | Quantity | Unit  | Value   | Currency | Type      |
      | PSKU1       | 1        | item  | 100.00  | USD      | Generated |

  Scenario: Import prices in price list
    Given download "Product Price" Data Template file
    And I fill template with data:
      | Product SKU | Quantity | Unit Code | Price     | Currency |
      | PSKU1       | 1        | item      | 105.0000  | USD      |
    And I import file
    And reload the page
    Then should see following grid:
      | Product SKU | Quantity | Unit  | Value   | Currency | Type      |
      | PSKU1       | 1        | item  | 105.00  | USD      | Manual    |
    When I click "Recalculate"
    Then I should see "Product Prices have been successfully recalculated"
    And should see following grid:
      | Product SKU | Quantity | Unit  | Value   | Currency | Type      |
      | PSKU1       | 1        | item  | 105.00  | USD      | Manual    |
