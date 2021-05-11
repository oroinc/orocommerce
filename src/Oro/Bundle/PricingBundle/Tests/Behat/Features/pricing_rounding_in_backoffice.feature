@ticket-BB-20273
@fixture-OroPricingBundle:PriceRoundingInBackoffice.yml

Feature: Pricing Rounding In Backoffice
  In order to check pricing rounding on price list view page
  As an Administrator
  I add product prices with edge rounding cases

  Scenario: Feature Background
    Given login as administrator
    And I go to Sales/ Price Lists
    And click view "Default Price List" in grid

  Scenario: Adds price with max scale allowed
    When I click "Add Product Price"
    And I fill "Add Product Price Form" with:
      | Product  | Product1 |
      | Quantity | 1        |
      | Unit     | item     |
      | Price    | 123.9999 |
    And I click "Save"

  Scenario: Adds price with greater scale than allowed
    When I click "Add Product Price"
    And I fill "Add Product Price Form" with:
      | Product  | Product2  |
      | Quantity | 1         |
      | Unit     | item      |
      | Price    | 456.99999 |
    And I click "Save"

  Scenario: Checks that prices are saved and displayed correctly
    And I should see following grid:
      | Product SKU | Product name | Quantity | Unit | Value    | Currency | Type   |
      | PSKU1       | Product1     | 1        | item | 123.9999 | USD      | Manual |
      | PSKU2       | Product2     | 1        | item | 457.00   | USD      | Manual |
