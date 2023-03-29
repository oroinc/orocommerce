@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroPaymentBundle:ProductsAndShoppingListsForPayments.yml
@ticket-BB-13932
@behat-test-env
Feature: Process order submission with PayPal Payments Pro and enabled zero "authorization amount" option and Charge payment action on single page checkout

  In order to check that PayPal Payments Pro with zero "authorization amount" option and "Authorize & Charge" payment action works on single page checkout
  As a user
  I want to finish checkout and save credit card data
  I want to finish checkout using already saved credit card data

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I activate "Single Page Checkout" workflow
    And I create PayPal PaymentsPro integration with following settings:
      | creditCardPaymentAction | charge |
      | zeroAmountAuthorization | true   |
    And I create payment rule with "PayPalPro" payment method

  Scenario: Successful first order payment with PayPal Payments Pro
    Given There are products in the system available for order
    And I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I open page with shopping list List 1
    And I click "Create Order"
    And I fill credit card form with next data:
      | CreditCardNumber | 5424000000000015 |
      | Month            | 11               |
      | Year             | 2027             |
      | CVV              | 123              |
    When I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Successful second order payment with PayPal Payments Pro  and already saved credit card data
    Given I proceed as the Buyer
    And I open page with shopping list List 2
    And I click "Create Order"
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Check orders are paid in full
    Given I proceed as the Admin
    And I login as administrator
    When I go to Sales/Orders
    Then I should see following grid:
      | Order Number | Payment Status |
      | 1            | Paid in full   |
      | 2            | Paid in full   |
