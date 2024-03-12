@regression
@ticket-BB-22677
@fixture-OroProductBundle:quick_order_product.yml

Feature: Shipping cost fields available when creating product
  In order to be able to set shipping cost while creating a product
  As an administrator
  I need to have shipping cost fields available on product creation page

  Scenario: Create product
    Given I login as administrator
    And I go to Products/ Products
    And I click "Create Product"
    And click "Continue"
    And fill "Create Product Form" with:
      | SKU    | PRODA1    |
      | Name   | ProductA1 |
      | Status | Enabled   |
    And click "Shipping Options"
    Then I should see "Product Shipping Cost Unit Each" element inside "Product Shipping Cost Sub Form" element
    And I fill "Shipping Cost Attribute Product Form" with:
      | Shipping Cost USD | 12.34 |
    And I save and close form
    Then I should see "Product has been saved" flash message
    When I click "Shipping Options"
    Then I should see following "Shipping Cost Attribute Grid" grid:
      | UNIT | EUR | USD   |
      | each | N/A | 12.34 |

  Scenario: Change primary unit while creating a product
    Given I go to Products/ Products
    And I click "Create Product"
    And click "Continue"
    And fill "Create Product Form" with:
      | SKU    | PRODA2    |
      | Name   | ProductA2 |
      | Status | Enabled   |
    And click "Shipping Options"
    Then I should see "Product Shipping Cost Unit Each" element inside "Product Shipping Cost Sub Form" element
    And I fill "Shipping Cost Attribute Product Form" with:
      | Shipping Cost USD | 12.34 |
    Then I fill product fields with next data:
      | PrimaryUnit | item |
    And click "Shipping Options"
    Then I should see "Product Shipping Cost Unit Item" element inside "Product Shipping Cost Sub Form" element
    And I fill "Shipping Cost Attribute Product Form" with:
      | Shipping Cost USD | 43.21 |
    And I save and close form
    Then I should see "Product has been saved" flash message
    When I click "Shipping Options"
    Then I should see following "Shipping Cost Attribute Grid" grid:
      | UNIT | EUR | USD   |
      | item | N/A | 43.21 |
