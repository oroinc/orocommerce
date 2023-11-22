@regression
@ticket-BB-22677
@fixture-OroProductBundle:quick_order_product.yml

Feature: Price attribute fields available when creating product
  In order to be able to set price attributes while creating a product
  As an administrator
  I need to have price attribute fields available on product creation page

  Scenario: Create Price Attributes
    Given I login as administrator
    And I go to Products/ Price Attributes
    When I click "Create Price Attribute"
    And I fill form with:
      | Name       | MSRP |
      | Field Name | msrp |
      | Currencies | USD  |
    And I save and close form
    Then I should see "Price Attribute has been saved" flash message

  Scenario: Create product
    Given I go to Products/ Products
    And I click "Create Product"
    And click "Continue"
    And fill "Create Product Form" with:
      | SKU    | PRODA1    |
      | Name   | ProductA1 |
      | Status | Enabled   |
    And click "Product Prices"
    Then I should see "Product Price Attribute Unit Each" element inside "Product Price Attribute MSRP Form" element
    And I add price 10 to Price Attribute MSRP
    And I save and close form
    Then I should see "Product has been saved" flash message
    Then I should see following "MSRP Product Price Attributes Grid" grid:
      | Unit | USD   |
      | each | 10.00 |

  Scenario: Change primary unit while creating a product
    Given I go to Products/ Products
    And I click "Create Product"
    And click "Continue"
    And fill "Create Product Form" with:
      | SKU    | PRODA2    |
      | Name   | ProductA2 |
      | Status | Enabled   |
    And click "Product Prices"
    Then I should see "Product Price Attribute Unit Each" element inside "Product Price Attribute MSRP Form" element
    And I add price 10 to Price Attribute MSRP
    Then I fill product fields with next data:
      | PrimaryUnit | item |
    And click "Product Prices"
    Then I should see "Product Price Attribute Unit Item" element inside "Product Price Attribute MSRP Form" element
    And I add price 15 to Price Attribute MSRP
    And I save and close form
    Then I should see "Product has been saved" flash message
    Then I should see following "MSRP Product Price Attributes Grid" grid:
      | Unit | USD   |
      | item | 15.00 |
