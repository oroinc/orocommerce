@ticket-BB-14904
@automatically-ticket-tagged
@fixture-OroPricingBundle:PricelistsForImport.yml

Feature: Export and Import Products Prices with zero price
  In order to export and import products prices with zero price
  As an Administrator
  I want to be sure that zero prices can be imported and then exported

  Scenario: Import Products Prices in Price List
    Given I login as administrator
    And go to Customers/ Customer Groups
    And click edit "Non-Authenticated Visitors" in grid
    And fill "Customer Group Form" with:
      | Price List | priceListForImport |
    And I save and close form
    When go to Sales/ Price Lists
    And I click view "priceListForImport" in grid
    And download "Product Price" Data Template file
    And I fill template with data:
      | Product SKU | Quantity | Unit Code | Price   | Currency |
      | PSKU1       | 1        | item      | 6.0000  | USD      |
      | PSKU2       | 2        | item      | 10.0000 | USD      |
      | PSKU3       | 1        | item      | 0       | USD      |
      | PSKU4       | 3        | item      | 12.0000 | USD      |
    And I import file
    And reload the page
    Then should see following grid:
      | Product SKU         | Product name    | Quantity | Unit | Value   | Currency |
      | PSKU1               | Product 1       | 1        | item | 6.00    | USD      |
      | PSKU2               | Product 2       | 2        | item | 10.00   | USD      |
      | PSKU3               | Product 3       | 1        | item | 0.00    | USD      |
      | PSKU4               | Product 4       | 3        | item | 12.00   | USD      |

  Scenario: Export Products Price that was imported before
    Given go to Sales/ Price Lists
    And I click view "priceListForImport" in grid
    When I click "Export Button"
    Then I should see "Export started successfully. You will receive email notification upon completion." flash message
    And Email should contains the following "Export performed successfully. 4 product prices were exported. Download" text
    And Exported file for "ProductPrice" contains following rows in any order:
      | Product SKU | Quantity | Unit Code | Price   | Currency |
      | PSKU1       | 1        | item      | 6.0000  | USD      |
      | PSKU2       | 2        | item      | 10.0000 | USD      |
      | PSKU3       | 1        | item      | 0.0000  | USD      |
      | PSKU4       | 3        | item      | 12.0000 | USD      |
    And I click Logout in user menu

  Scenario: Check imported Products Prices on fronstore
    Given I am on the homepage
    When click "NewCategory "
    Then should see "Listed Price: $0.00 / item" for "PSKU3" product
    And should see "Listed Price: $6.00 / item" for "PSKU1" product
