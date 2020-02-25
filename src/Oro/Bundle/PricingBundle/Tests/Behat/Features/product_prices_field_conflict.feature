@regression
@ticket-BB-17371
@fixture-OroPricingBundle:ProductPricesFieldsConflict.yml

Feature: Product prices field conflict
  In order to be able to control the error of the dynamic fields conflict
  As an administrator
  I adding an existing field and checking if the form has the expected behavior, new field is ignored

  Scenario: Create product price
    Given login as administrator
    And go to Products/Products
    And click edit "PSKU1" in grid
    And click "Product Prices"
    And click "Add Product Price"
    When I set Product Price collection element values in 1 row:
      | Price List     | Default Price List |
      | Quantity value | 1                  |
      | Quantity Unit  | each               |
      | Value          | 1.0                |
      | Currency       | $                  |
    And save form
    Then I should see "Product has been saved" flash message

  Scenario: Create custom field with name "prices"
    Given I go to System / Entities / Entity Management
    And filter Name as is equal to "Product"
    And I click View Product in grid
    And I click "Create field"
    When I fill form with:
      | Field Name   | prices       |
      | Storage Type | Table column |
      | Type         | Text         |
    And I click "Continue"
    And I save and close form
    Then I should see "Field saved" flash message
    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Check product price field
    Given I go to Products/Products
    And click edit "PSKU1" in grid
    And click "Product Prices"
    And click "Add Product Price"
    When I set Product Price collection element values in 2 row:
      | Price List     | Second Price List |
      | Quantity value | 2                 |
      | Quantity Unit  | each              |
      | Value          | 2.0               |
      | Currency       | $                 |
    And save form
    Then I should see "Product has been saved" flash message
