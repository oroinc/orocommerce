@regression
@ticket-BB-14089
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPayPalBundle:PayPalExpressProductsWithTaxes.yml
@behat-test-env
Feature: Paypal payflow gateway express payments should not have tax line when price includes tax
  In order to complete checkout with products which prices are already include tax
  As a buyer
  I want to be able to see correct checkout total information on PayPal site

  Scenario: Proceed PayPal Payflow Gateway Express Checkout without separate "tax" line item
    Given I create PayPal PaymentsPro integration
    And I create payment rule with "ExpressPayPal" payment method
    And There are products in the system available for order
    And I enable configuration options:
      | oro_tax.product_prices_include_tax |
    And I set configuration property "oro_tax.use_as_base_by_default" to "destination"
    Then I login as AmandaRCole@example.org buyer
    And I am on the homepage
    And I open page with shopping list List 1
    And I click "Create Order"
    And I select "ORO, Third avenue, TALLAHASSEE FL US 32003" on the "Billing Information" checkout step and press Continue
    And I select "ORO, Third avenue, TALLAHASSEE FL US 32003" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "ExpressPayPal" on the "Payment" checkout step and press Continue
    Then I should see Checkout Totals with data:
      | Subtotal | $10.00  |
      | Shipping | $3.00   |
      | Tax      | $0.91   |
    And I should see "Total $13.00"
    When I click "Submit Order"
    Then I should not see the following products before pay:
      | NAME | DESCRIPTION |
      | Tax  |  Tax |
    And I see the "Thank You" page with "Thank You For Your Purchase!" title
