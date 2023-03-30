@regression
@ticket-BB-16483
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroPaymentBundle:ProductsAndShoppingListsForPayments.yml
@behat-test-env
Feature: Correct render payflow with payments_pro simultaneously
  In order to use multiple PayPal integration on Frontstore
  As a Buyer
  I want to process order submission with PayPal Payments Pro or PayPal PayFlow Gateway

  Scenario: Successful order payment and amount capture with PayPal Payments Pro
    Given There are products in the system available for order
    And I create PayPal PaymentsPro integration
    And I create payment rule with "PayPalPro" payment method
    And I create PayPal Payflow integration
    And I create payment rule with "PayPalFlow" payment method
    And I login as AmandaRCole@example.org buyer
    When I open page with shopping list List 1
    And I click "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    Then I should see "PayPalPro"
    And I should see "PayPalFlow"
    And I should not see flash messages
    And I should not see "Error occurred during layout update. Please contact system administrator."
