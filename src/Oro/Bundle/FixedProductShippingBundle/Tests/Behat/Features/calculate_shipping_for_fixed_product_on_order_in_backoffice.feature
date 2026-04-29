@regression
@ticket-BB-27174
@fixture-OroFixedProductShippingBundle:FixedProductShippingIntegrations.yml
@fixture-OroFixedProductShippingBundle:FixedProductShipping.yml
@fixture-OroFixedProductShippingBundle:FixedProductShippingCheckout.yml

Feature: Calculate Shipping for Fixed Product on Order in back-office
  In order to be sure Fixed Product shipping cost includes product shipping cost
  As administrator
  I trigger Calculate Shipping on an Order I expect Fixed Product methods to show populated prices

  Scenario: Fixed Product Shipping methods show populated prices on Order
    Given I login as administrator
    When I go to Sales/ Orders
    And I click "Create Order"
    And I click "Add Product"
    And I fill "Order Form" with:
      | Customer      | Company A       |
      | Customer User | AmandaMu Cole   |
      | Product       | simpleproduct01 |
    When I click "Calculate Shipping Button"
    Then I should see "Fixed Product 1 (Surcharge Type: Percent, Surcharge On: Product Price, Surcharge Amount: 10%) $3.00"
    And I should see "Fixed Product 2 (Surcharge Type: Percent, Surcharge On: Product Shipping Cost, Surcharge Amount: 15%) $1.15"
    And I should see "Fixed Product 3 (Surcharge Type: Fixed Amount, Surcharge Amount: $20.00) $21.00"
