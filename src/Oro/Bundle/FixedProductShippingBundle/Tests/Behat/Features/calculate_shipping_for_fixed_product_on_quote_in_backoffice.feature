@regression
@ticket-BB-27174
@fixture-OroFixedProductShippingBundle:FixedProductShippingIntegrations.yml
@fixture-OroFixedProductShippingBundle:FixedProductShipping.yml
@fixture-OroFixedProductShippingBundle:FixedProductShippingCheckout.yml

Feature: Calculate Shipping for Fixed Product on Quote in back-office
  In order to be sure Fixed Product shipping cost includes product shipping cost
  As administrator
  I trigger Calculate Shipping on a new Quote I expect Fixed Product methods to show populated prices

  Scenario: Fixed Product Shipping methods show populated prices on Quote
    Given I login as administrator
    When I go to Sales/ Quotes
    And I click "Create Quote"
    And I fill "Quote Form" with:
      | Customer        | Company A       |
      | Customer User   | AmandaMu Cole   |
      | LineItemProduct | simpleproduct01 |
    And I click "Shipping Information"
    When I click on "Calculate Shipping"
    Then I should see "Fixed Product 1 (Surcharge Type: Percent, Surcharge On: Product Price, Surcharge Amount: 10%) $3.00"
    And I should see "Fixed Product 2 (Surcharge Type: Percent, Surcharge On: Product Shipping Cost, Surcharge Amount: 15%) $1.15"
    And I should see "Fixed Product 3 (Surcharge Type: Fixed Amount, Surcharge Amount: $20.00) $21.00"
