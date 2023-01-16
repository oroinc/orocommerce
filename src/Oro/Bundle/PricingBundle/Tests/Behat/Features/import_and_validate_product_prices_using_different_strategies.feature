@ticket-BB-21880
@regression
@fixture-OroPricingBundle:ProductsWithoutPrices.yml

Feature: Import and validate product prices using different strategies
  Check whether the prices of products do not change after import validation and the validation results are the
  same as importing using 'Reset and Add' strategy

  Scenario: Import prices with 'Reset And Add' strategy
    Given I login as administrator
    And go to Sales/ Price Lists
    And click view "Default Price List" in grid
    When I download "Product Price" Data Template file
    And fill template with data:
      | Product SKU | Quantity | Unit Code | Price | Currency |
      | product_1   | 1        | item      | 10    | USD      |
      | product_2   | 1        | item      | 20    | USD      |
      | product_3   | 1        | item      | 30    | USD      |
      | product_3   | 1        | item      | 30    | USD      |
    And I import product prices file with strategy "Reset and Add"
    Then Email should contains the following "Errors: 1 processed: 3, read: 4, added: 2, updated: 0, replaced: 1" text
    When I reload the page
    Then I should see following grid:
      | Product SKU | Product name | Quantity | Unit | Value | Currency |
      | product_1   | Product 1    | 1        | item | 10.00 | USD      |
      | product_2   | Product 2    | 1        | item | 20.00 | USD      |
      | product_3   | Product 3    | 1        | item | 30.00 | USD      |

  Scenario: Validation imported prices with 'Reset And Add' strategy and duplicated prices
    Given I fill template with data:
      | Product SKU | Quantity | Unit Code | Price | Currency |
      | product_1   | 1        | item      | 10    | USD      |
      | product_2   | 1        | item      | 20    | USD      |
      | product_3   | 1        | item      | 30    | USD      |
      | product_3   | 1        | item      | 30    | USD      |
    And I validate file with strategy "Reset and Add"
    # Skipped validation of existing prices and checked for duplicates only in the imported file
    Then Email should contains the following "Errors: 1 processed: 3, read: 4" text

  Scenario: Validation imported prices with 'Reset And Add' strategy any product prices
    Given I fill template with data:
      | Product SKU | Quantity | Unit Code | Price | Currency |
      | product_1   | 1        | item      | 100   | USD      |
      | product_2   | 1        | item      | 200   | USD      |
      | product_3   | 1        | item      | 300   | USD      |
    And I validate file with strategy "Reset and Add"
    Then Email should contains the following "Errors: 0 processed: 3, read: 3" text
    When I reload the page
    Then I should see following grid:
      | Product SKU | Product name | Quantity | Unit | Value | Currency |
      | product_1   | Product 1    | 1        | item | 10.00 | USD      |
      | product_2   | Product 2    | 1        | item | 20.00 | USD      |
      | product_3   | Product 3    | 1        | item | 30.00 | USD      |

  Scenario: Import prices with 'Reset And Add' strategy
    Given I fill template with data:
      | Product SKU | Quantity | Unit Code | Price | Currency |
      | product_1   | 1        | item      | 100   | USD      |
      | product_2   | 1        | item      | 200   | USD      |
      | product_3   | 1        | item      | 300   | USD      |
    And I import product prices file with strategy "Reset and Add"
    Then Email should contains the following "Errors: 0 processed: 3, read: 3, added: 3, updated: 0, replaced: 0" text
    When I reload the page
    Then I should see following grid:
      | Product SKU | Product name | Quantity | Unit | Value  | Currency |
      | product_1   | Product 1    | 1        | item | 100.00 | USD      |
      | product_2   | Product 2    | 1        | item | 200.00 | USD      |
      | product_3   | Product 3    | 1        | item | 300.00 | USD      |
