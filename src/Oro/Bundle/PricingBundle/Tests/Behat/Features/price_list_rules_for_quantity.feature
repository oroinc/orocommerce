@regression
@ticket-BB-BB-22948
@fixture-OroProductBundle:single_product_with_category.yml
@skip
# Unskip when BB-24001 will be fixed
Feature: Price List Rules for Quantity

  Scenario: Create Price List with Rules
    Given I login as administrator
    When I go to Sales/ Price Lists
    And I click "Create Price List"
    And I fill form with:
      | Name       | PL1            |
      | Currencies | US Dollar ($)  |
      | Active     | true           |
      | Rule       | product.id > 0 |
    And I click "Add Price Calculation Rules"
    And I fill "Price Calculation Rules Form" with:
      | Price for quantity expression | pricelist[1].prices.quantity + 0.2345 |
      | Price Unit                    | pricelist[1].prices.unit              |
      | Price Currency                | pricelist[1].prices.currency          |
      | Calculate As                  | pricelist[1].prices.value * 1.2       |
      | Priority                      | 1                                     |
    And I save and close form
    Then should see "Price List has been saved" flash message

  Scenario: Set default price list prices
    Given I go to Sales/ Price Lists
    And click View Default Price List in grid
    When I click "Add Product Price"
    And I fill "Add Product Price Form" with:
      | Product  | PSKU1 |
      | Quantity | 1     |
      | Unit     | each  |
      | Price    | 100   |
    And I click "Save"
    Then should see "Product Price has been added" flash message

  Scenario: Check generated prices in dependent price list
    Given I go to Sales/ Price Lists
    And click View PL1 in grid
    Then number of records in "Price list Product prices Grid" should be 1
    And I should see following "Price list Product prices Grid" grid:
      | Product SKU | Product Name | Quantity | Unit | Value  | Currency | Type      |
      | PSKU1       | Product 1    | 1.23     | each | 120.00 | USD      | Generated |

  Scenario: Change Product Unit precision
    Given I go to Products/ Products
    And click edit "PSKU1" in grid
    And I fill "ProductForm" with:
      | PrimaryPrecision | 3 |
    When I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check generated prices in dependent price list
    Given I go to Sales/ Price Lists
    And click View PL1 in grid
    Then number of records in "Price list Product prices Grid" should be 1
    And I should see following "Price list Product prices Grid" grid:
      | Product SKU | Product Name | Quantity | Unit | Value  | Currency | Type      |
      | PSKU1       | Product 1    | 1.235    | each | 120.00 | USD      | Generated |
