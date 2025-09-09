@ticket-BB-25990
@fixture-OroProductBundle:ProductWithUnitContainingColon.yml

Feature: Create product with unit containing colon
  In order to manage products
  As administrator
  I need to be able to create product with unit of quantity containing colon

  Scenario: Check product creation with unit of quantitty contains colon
    Given I login as administrator
    When I go to Products/ Products
    Then I should see following grid:
      | SKU     | Name         |
      | SKU1    | Product 1    |
    When I click "Create Product"
    And I click "Continue"
    Then Page title equals to "Create Product - Products - Products"
    When I fill "Create Product Form" with:
      | SKU              | Test123                                    |
      | Name             | Test Product                               |
      | Status           | Enable                                     |
      | Unit Of Quantity | uom: 7                                     |
    And I save form
    Then I should see "Product has been saved" flash message

  Scenario: Check created product on grid not contain errors
    When I go to Products/ Products
    Then I should see following grid:
      | SKU     | Name         |
      | Test123 | Test Product |
      | SKU1    | Product 1    |
