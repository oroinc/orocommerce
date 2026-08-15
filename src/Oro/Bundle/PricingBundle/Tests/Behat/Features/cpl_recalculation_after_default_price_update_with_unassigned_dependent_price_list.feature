@regression
@pricing-storage-combined
@ticket-BB-27606
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
@fixture-OroProductBundle:ProductsExportFixture.yml

Feature: CPL recalculation after default price update with unassigned dependent price list
  In order to show actual prices on storefront
  As an Administrator
  I want combined price list to be recalculated when default price is updated for a product
  that is not assigned to a dependent price list in the same chain

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create dependent price list assigned only to PSKU1
    Given I proceed as the Admin
    And I login as administrator
    When I go to Sales/ Price Lists
    And I click "Create Price List"
    And I fill "Price List Form" with:
      | Name       | TESTPL                         |
      | Currencies | US Dollar ($)                  |
      | Active     | true                           |
      | Rule       | product.sku == 'PSKU1'         |
    And I click "Add Price Calculation Rules"
    And I fill "Price Calculation Rules Form" with:
      | Price for quantity    | 1                               |
      | Price Unit Static     | item                            |
      | Price Currency Static | $                               |
      | Calculate As          | pricelist[1].prices.value * 0.1 |
      | Priority              | 1                               |
    And I save and close form
    Then I should see "Price List has been saved" flash message

  Scenario: Add dependent price list to system configuration
    Given I proceed as the Admin
    When I go to System/Configuration
    And I follow "Commerce/Catalog/Pricing" on configuration sidebar
    And I click "Add Price List"
    And I choose Price List "TESTPL" in 2 row
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Configure customer price list chain with default and dependent price lists
    Given I proceed as the Admin
    When I go to Customers/Customers
    And click Edit AmandaRCole in grid
    And I fill form with:
      | Fallback | Current customer only |
    And I choose Price List "Default Price List" in 1 row
    And I click "Add Price List"
    And I choose Price List "TESTPL" in 2 row
    And I submit form
    Then I should see "Customer has been saved" flash message

  Scenario: Create product prices in default price list
    Given I proceed as the Admin
    When I go to Sales/ Price Lists
    And click View Default Price List in grid
    And I click "Add Product Price"
    And I fill "Add Product Price Form" with:
      | Product  | PSKU1 |
      | Quantity | 1     |
      | Unit     | item  |
      | Price    | 100   |
    And I click "Save"
    Then should see "Product Price has been added" flash message
    When I click "Add Product Price"
    And I fill "Add Product Price Form" with:
      | Product  | PSKU2 |
      | Quantity | 1     |
      | Unit     | item  |
      | Price    | 50    |
    And I click "Save"
    Then should see "Product Price has been added" flash message

  Scenario: Check initial storefront price for product not assigned to dependent price list
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When type "PSKU2" in "search"
    And I click "Search Button"
    Then should see "$50.00" for "PSKU2" product

  Scenario: Check initial price in price calculation details
    Given I proceed as the Admin
    When I go to Sales/Price Calculation Details
    And I filter SKU as Contains "PSKU2"
    And fill "Price Calculation Details Grid Sidebar" with:
      | Website  | Default     |
      | Customer | AmandaRCole |
    And click on PSKU2 in grid
    Then I should see next prices for "Customer Prices":
      | Item (USD) |
      | 1 $50.00   |

  Scenario: Update product price in default price list
    Given I proceed as the Admin
    When I go to Sales/ Price Lists
    And click View Default Price List in grid
    And click edit PSKU2 in "Price list Product prices Grid"
    And fill "Update Product Price Form" with:
      | Price | 77 |
    And I click "Save"
    Then should see "Product Price has been added" flash message

  Scenario: Check updated price in price calculation details
    Given I proceed as the Admin
    When I go to Sales/Price Calculation Details
    And I filter SKU as Contains "PSKU2"
    And fill "Price Calculation Details Grid Sidebar" with:
      | Website  | Default     |
      | Customer | AmandaRCole |
    And click on PSKU2 in grid
    Then I should see next prices for "Customer Prices":
      | Item (USD) |
      | 1 $77.00   |

  Scenario: Check updated storefront price for product not assigned to dependent price list
    Given I proceed as the Buyer
    When I reload the page
    And type "PSKU2" in "search"
    And I click "Search Button"
    Then should see "$77.00" for "PSKU2" product
