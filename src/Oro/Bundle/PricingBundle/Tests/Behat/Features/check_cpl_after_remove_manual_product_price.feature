@regression
@pricing-storage-combined
@ticket-BB-13422
@ticket-BB-16591
@fixture-OroProductBundle:ProductsExportFixture.yml
@fixture-OroPricingBundle:PriceListToProductFixture.yml
@fixture-OroVisibilityBundle:customers.yml

Feature: Check CPL after remove manual product price
  In order to have ability to manage Combined Price Lists
  As an Administrator
  I want to be able add product and remove from Price List manually

  Scenario: Feature Background
    Given sessions active:
      | Admin    | first_session  |
      | Customer | second_session |

  Scenario: Create price list
    Given I proceed as the Admin
    And I login as administrator
    And I go to Sales/ Price Lists
    When I click "Create Price List"
    And I fill form with:
      | Name       | My Price List                                                              |
      | Currencies | US Dollar ($)                                                              |
      | Active     | true                                                                       |
      | Rule       | product.sku == 'PSKU1' or product.sku == 'PSKU2' or product.sku == 'PSKU3' |
    And I click "Add Price Calculation Rules"
    And I click "Enter expression unit"
    And I click "Enter expression currency"
    And I fill "Price Calculation Rules Form" with:
      | Price for quantity | 1                               |
      | Price Unit         | pricelist[1].prices.unit        |
      | Price Currency     | pricelist[1].prices.currency    |
      | Calculate As       | pricelist[1].prices.value * 1.2 |
      | Condition          | pricelist[1].prices.value > 1   |
      | Priority           | 1                               |
    And I save and close form
    Then I should see "Price List has been saved" flash message
    And I reload the page
    And number of records in "Price list Product prices Grid" should be 3
    And I should see following grid:
      | Product SKU | Product name | Quantity | Unit | Value | Currency | Type      |
      | PSKU1       | Product 1    | 1        | item | 8.40  | USD      | Generated |
      | PSKU2       | Product 2    | 1        | item | 13.20 | USD      | Generated |
      | PSKU3       | Product 3    | 1        | item | 15.60 | USD      | Generated |

  Scenario: Create custom price
    Given I click "Add Product Price"
    When I fill "Add Product Price Form" with:
      | Product  | Product 4 |
      | Quantity | 1         |
      | Unit     | item      |
      | Price    | 10        |
    And I click "Save"
    Then I should see "Product Price has been added" flash message
    And number of records in "Price list Product prices Grid" should be 4
    And I should see following grid:
      | Product SKU | Product name | Quantity | Unit | Value | Currency | Type      |
      | PSKU1       | Product 1    | 1        | item | 8.40  | USD      | Generated |
      | PSKU2       | Product 2    | 1        | item | 13.20 | USD      | Generated |
      | PSKU3       | Product 3    | 1        | item | 15.60 | USD      | Generated |
      | PSKU4       | Product 4    | 1        | item | 10.00 | USD      | Manual    |
    And go to Customers/Customers
    And click edit "first" in grid
    And fill "Customer Form" with:
      | Price List | My Price List |
    And save and close form
    And I go to Sales/ Price Lists
    And click view "My Price List" in grid
    When I proceed as the Customer
    And I signed in as AmandaRCole@example.org on the store frontend
    And type "PSKU" in "search"
    And I click "Search Button"
    Then should see "Listed Price: $10.00 / item" for "PSKU4" product

  Scenario: Remove Price for fourth product
    Given I proceed as the Admin
    When I click delete "PSKU4" in grid
    And I click "Yes" in confirmation dialogue
    Then number of records in "Price list Product prices Grid" should be 3
    And I should see following grid:
      | Product SKU | Product name | Quantity | Unit | Value | Currency | Type      |
      | PSKU1       | Product 1    | 1        | item | 8.40  | USD      | Generated |
      | PSKU2       | Product 2    | 1        | item | 13.20 | USD      | Generated |
      | PSKU3       | Product 3    | 1        | item | 15.60 | USD      | Generated |

  Scenario: Recalculate Price List
    When I click "Recalculate"
    Then I should see "Product Prices have been successfully recalculated" flash message

    When I reload the page
    Then number of records in "Price list Product prices Grid" should be 3
    And I should see following grid:
      | Product SKU | Product name | Quantity | Unit | Value | Currency | Type      |
      | PSKU1       | Product 1    | 1        | item | 8.40  | USD      | Generated |
      | PSKU2       | Product 2    | 1        | item | 13.20 | USD      | Generated |
      | PSKU3       | Product 3    | 1        | item | 15.60 | USD      | Generated |
    When I proceed as the Customer
    And I reload the page
    Then should see "Listed Price: $19.00 / item" for "PSKU4" product
