@regression
@ticket-BB-20395
@fixture-OroProductBundle:products.yml
@pricing-storage-combined

Feature: Price list rules rounding
  In order to control price generation based on a price rule
  As an Administrator
  I want to have ability to configure and use rounding precision for price rule

  Scenario: Create Rule based price list
    Given I login as administrator
    When I go to Sales/ Price Lists
    And I click "Create Price List"
    And I fill form with:
      | Name       | Rule based Price List |
      | Currencies | US Dollar ($)         |
      | Active     | true                  |
      | Rule       | product.id > 0        |
    And I click "Add Price Calculation Rules"
    And I click "Enter expression unit"
    And I click "Enter expression currency"
    And I fill "Price Calculation Rules Form" with:
      | Price for quantity | pricelist[1].prices.quantity  |
      | Price Unit         | pricelist[1].prices.unit      |
      | Price Currency     | pricelist[1].prices.currency  |
      | Calculate As       | pricelist[1].prices.value/2.7 |
      | Priority           | 1                             |
    And I save and close form
    Then I should see "Price List has been saved" flash message

  Scenario: Check generated prices
    When I go to Sales/ Price Lists
    And I click view "Rule based Price List" in grid
    Then I should see following grid containing rows:
      | Product SKU | Quantity | Unit | Value  | Currency | Type      |
      | PSKU1       | 1        | each | 3.7037 | USD      | Generated |
      | PSKU2       | 1        | each | 3.7037 | USD      | Generated |
      | PSKU3       | 1        | each | 3.7037 | USD      | Generated |

  Scenario: Change Price Calculation Precision in Price Lists
    When I go to System/ Configuration
    And I follow "Commerce/Catalog/Pricing" on configuration sidebar
    And uncheck "Use default" for "Price Calculation Precision in Price Lists" field
    And I fill form with:
      | Price Calculation Precision in Price Lists | 3 |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check generated prices
    When I go to Sales/ Price Lists
    And I click view "Rule based Price List" in grid
    And I click "Recalculate"
    Then I should see "Product Prices have been successfully recalculated" flash message
    And I should see following grid containing rows:
      | Product SKU | Quantity | Unit | Value | Currency | Type      |
      | PSKU1       | 1        | each | 3.704 | USD      | Generated |
      | PSKU2       | 1        | each | 3.704 | USD      | Generated |
      | PSKU3       | 1        | each | 3.704 | USD      | Generated |
