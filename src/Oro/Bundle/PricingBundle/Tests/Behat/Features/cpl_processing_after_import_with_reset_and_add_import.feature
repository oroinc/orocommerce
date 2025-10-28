@regression
@pricing-storage-combined
@ticket-BB-26237
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
@fixture-OroProductBundle:ProductsExportFixture.yml

Feature: CPL processing after import with reset and add import

  Scenario: Prepare Admin session
    Given I login as administrator
    When I go to Sales/ Price Lists
    And I click "Create Price List"
    And I fill "Price List Form" with:
      | Name       | TESTPL        |
      | Currencies | US Dollar ($) |
      | Active     | true          |
    And I save and close form
    Then I should see "Price List has been saved" flash message

  Scenario: Add product price
    Given I click "Add Product Price"
    When I fill "Add Product Price Form" with:
      | Product  | PSKU1 |
      | Quantity | 1     |
      | Unit     | item  |
      | Price    | 2     |
    And I click "Save"
    Then I should see "Product Price has been added" flash message

  Scenario: Assign Price List to customer
    When I go to Customers/Customers
    And click Edit AmandaRCole in grid
    And I fill form with:
      | Fallback | Current customer only |
    And I click "Add Price List"
    And I choose Price List "TESTPL" in 1 row
    And I submit form
    Then I should see "Customer has been saved" flash message

  Scenario: Check price available in price debug
    When I go to Sales/Price Calculation Details
    And I filter SKU as Contains "PSKU1"
    And fill "Price Calculation Details Grid Sidebar" with:
      | Website  | Default     |
      | Customer | AmandaRCole |
    And click on PSKU1 in grid
    And I should see next prices for "Customer Prices":
      | Item (USD) |
      | 1 $2.00    |

  Scenario: Check prices imported with Reset and Add strategy to not empty price list are added as expected
    When I go to Sales/ Price Lists
    And click View TESTPL in grid
    And I download "ProductPrice" Data Template file
    And I fill template with data:
      | Product SKU | Quantity | Unit Code | Price | Currency |
      | PSKU2       | 1        | item      | 20    | USD      |
      | PSKU3       | 1        | item      | 31    | USD      |
    And I import product prices file with strategy "Reset and Add"
    And I reload the page
    Then I should see following grid:
      | Product SKU | Product name | Quantity | Unit | Value | Currency |
      | PSKU2       | Product 2    | 1        | item | 20.00 | USD      |
      | PSKU3       | Product 3    | 1        | item | 31.00 | USD      |

  Scenario Outline: Check price available in price debug
    When I go to Sales/Price Calculation Details
    And I filter SKU as Contains "<SKU>"
    And fill "Price Calculation Details Grid Sidebar" with:
      | Website  | Default     |
      | Customer | AmandaRCole |
    And click on <SKU> in grid
    And I should see next prices for "Customer Prices":
      | Item (USD) |
      | 1 <Price>  |

    Examples:
      | SKU   | Price  |
      | PSKU2 | $20.00 |
      | PSKU3 | $31.00 |

  Scenario: Check PSKU1 was removed from CPL
    When I go to Sales/Price Calculation Details
    And I filter SKU as Contains "PSKU1"
    And fill "Price Calculation Details Grid Sidebar" with:
      | Website  | Default     |
      | Customer | AmandaRCole |
    And click on PSKU1 in grid
    And I should see "Customer Prices No Prices"

  Scenario: Check price imported to not empty price list is added as expected
    When I go to Sales/ Price Lists
    And click View TESTPL in grid
    And I download "ProductPrice" Data Template file
    And I fill template with data:
      | Product SKU | Quantity | Unit Code | Price | Currency |
      | PSKU1       | 1        | item      | 11    | USD      |
    And I import file
    And I reload the page
    Then I should see following grid:
      | Product SKU | Product name | Quantity | Unit | Value | Currency |
      | PSKU1       | Product 1    | 1        | item | 11.00  | USD      |
      | PSKU2       | Product 2    | 1        | item | 20.00 | USD      |
      | PSKU3       | Product 3    | 1        | item | 31.00 | USD      |

  Scenario Outline: Check price available in price debug
    When I go to Sales/Price Calculation Details
    And I filter SKU as Contains "<SKU>"
    And fill "Price Calculation Details Grid Sidebar" with:
      | Website  | Default     |
      | Customer | AmandaRCole |
    And click on <SKU> in grid
    And I should see next prices for "Customer Prices":
      | Item (USD) |
      | 1 <Price>  |

    Examples:
      | SKU   | Price  |
      | PSKU1 | $11.00 |
      | PSKU2 | $20.00 |
      | PSKU3 | $31.00 |
