@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroPaymentBundle:ProductsAndShoppingListsForPayments.yml
@ticket-BB-13932
@behat-test-env
Feature: Process order submission with PayPal Payments Pro and Authorize & Charge payment action on single page checkout

  In order to check that PayPal Payments Pro with "Authorize & Charge" payment action works on single page checkout
  As a user
  I want to enter and complete Single Page Checkout with payment via PayPal

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I activate "Single Page Checkout" workflow
    And I create PayPal PaymentsPro integration with following settings:
      | creditCardPaymentAction | charge |
    And I create payment rule with "PayPalPro" payment method

  Scenario: Error from Backend when pay order with PayPal Payments Pro
    Given There are products in the system available for order
    And I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I open page with shopping list List 2
    And I click "Create Order"
    And I fill credit card form with next data:
      | CreditCardNumber | 5105105105105100 |
      | Month            | 11               |
      | Year             | 2027             |
      | CVV              | 123              |
    When I click "Submit Order"
    Then I should see "We were unable to process your payment. Please verify your payment information and try again." flash message

  Scenario: Successful order payment with PayPal Payments Pro
    Given I open page with shopping list List 1
    And I click "Create Order"
    And I fill credit card form with next data:
      | CreditCardNumber | 5424000000000015 |
      | Month            | 11               |
      | Year             | 2027             |
      | CVV              | 123              |
    When I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    And I should not see "Payment has not been processed."

  Scenario: Check order is paid in full
    Given I proceed as the Admin
    And I login as administrator
    When I go to Sales/Orders
    Then I should see following grid:
      | Order Number | Payment Status |
      | 2            | Paid in full   |
