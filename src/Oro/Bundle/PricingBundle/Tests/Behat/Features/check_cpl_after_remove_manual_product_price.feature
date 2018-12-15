@regression
@ticket-BB-13422
@fixture-OroProductBundle:ProductsExportFixture.yml
@fixture-OroPricingBundle:PriceListToProductFixture.yml

Feature: Check CPL after remove manual product price
  In order to have ability to manage Combined Price Lists
  As an Administrator
  I want to be able add product and remove from Price List manually

  Scenario: Create price list
    Given I login as administrator
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

  Scenario: Remove Price for fourth product
    When I click delete "PSKU4" in grid
    Then number of records in "Price list Product prices Grid" should be 3
    And I should see following grid:
      | Product SKU | Product name | Quantity | Unit | Value | Currency | Type      |
      | PSKU1       | Product 1    | 1        | item | 8.40  | USD      | Generated |
      | PSKU2       | Product 2    | 1        | item | 13.20 | USD      | Generated |
      | PSKU3       | Product 3    | 1        | item | 15.60 | USD      | Generated |

  Scenario: Recalculate Price List
    When I click "Recalculate"
    Then I should see "Price List Rules were Recalculates successful" flash message

    When I reload the page
    Then number of records in "Price list Product prices Grid" should be 3
    And I should see following grid:
      | Product SKU | Product name | Quantity | Unit | Value | Currency | Type      |
      | PSKU1       | Product 1    | 1        | item | 8.40  | USD      | Generated |
      | PSKU2       | Product 2    | 1        | item | 13.20 | USD      | Generated |
      | PSKU3       | Product 3    | 1        | item | 15.60 | USD      | Generated |
