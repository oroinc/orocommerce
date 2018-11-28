@fixture-OroProductBundle:quick_order_product.yml
Feature: User should have the possibility to change Primary Unit
  In order to Check that the User can change and save Primary Unit of Quantity of product
  As administrator
  I need to make sure that the User can change and save Primary Unit of Quantity of product

  Scenario: Product should be Saved If I change the Primary Unit of Quantity
    Given I login as administrator
    And I go to Products/ Products
    And click edit "PSKU1" in grid
    And set Product Price with:
      | Price List         | Quantity value | Quantity Unit | Value |
      | Default Price List | 5              | each          | 10    |
    When I fill product fields with next data:
      | PrimaryUnit      | item |
      | PrimaryPrecision | 0    |
    Then I should see "each - removed"
    And set Product Price with:
      | Price List         | Quantity value | Quantity Unit | Value |
      | Default Price List | 5              | item          | 10    |
    And save and close form
    And I should see product with:
      | Unit | item |

  Scenario: Validate product price Attribute If I change the Primary Unit of Quantity
    Given I go to Products/ Price Attributes
    And I click "Create Price Attribute"
    And I fill form with:
      | Name       | MSRP |
      | Field Name | msrp |
      | Currencies | USD  |
    And I save and close form
    Then I should see "Price Attribute has been saved" flash message
    And I go to Products/ Products
    And click edit "CONTROL1" in grid
    And I add price 10 to Price Attribute MSRP
    And I save and close form
    And I click "Edit"
    And I fill product fields with next data:
      | PrimaryUnit      | item |
      | PrimaryPrecision | 0    |
    When save and close form
    Then I should see "Units: 'each', are used in price attribute prices and can't be removed" error message
    And I clear Price Attribute MSRP
    When I save and close form
    Then I should see "Product has been saved" flash message
