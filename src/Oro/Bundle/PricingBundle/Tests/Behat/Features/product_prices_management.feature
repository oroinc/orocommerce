@fixture-OroPricingBundle:ProductPricesManagement.yml
@ticket-BB-11635

Feature: Product prices management
  In order to have flexibility of configuring product prices
  As admin
  I need to have ability to manage product prices and see appropriate validation errors

  Scenario: No validation error, when deleted price and edited prices have same values for PriceList, quantity, unit and currency
    Given I login as administrator
    And I go to Products/ Products
    And click edit "PSKU1" in grid
    When I click "Product Prices"
    Then I should see following data for Product Price collection:
      | Price List         | Quantity value | Quantity Unit | Value   |
      | Default Price List | 1              | item          | 5.0000  |
      | Default Price List | 10             | item          | 50.0000 |
    When I remove element in row #1 from Product Price collection
    Then I should see following data for Product Price collection:
      | Price List         | Quantity value | Quantity Unit | Value   |
      | Default Price List | 10             | item          | 50.0000 |
    When I set Product Price collection element values in 1 row:
      | Price List     | Default Price List |
      | Quantity value | 1                  |
      | Quantity Unit  | item               |
      | Value          | 7                  |
    And I save form
    Then I should see "Product has been saved" flash message
    And I should see following data for Product Price collection:
      | Price List         | Quantity value | Quantity Unit | Value  |
      | Default Price List | 1              | item          | 7.0000 |

  Scenario: Check success flash message when update product price
    Given I go to Sales/ Price Lists
    And click view "Default Price List" in grid
    And click "Product Prices"
    And click edit "PSKU1" in grid
    And fill "Price Modal Window Form" with:
      | Price | 777 |
    When I click "Save"
    Then I should see "Product Price has been added" flash message
    And I should see "777" in grid

  Scenario: Validation error appears for not unique prices (have same values for PriceList, quantity, unit and currency)
    Given I go to Products/ Products
    And click edit "PSKU1" in grid
    When I click "Product Prices"
    And set Product Price with:
      | Price List         | Quantity value | Quantity Unit | Value |
      | Default Price List | 5              | item          | 10    |
      | Default Price List | 5              | item          | 20    |
    And I save form
    Then I should see "Product has duplication of product prices." error message
