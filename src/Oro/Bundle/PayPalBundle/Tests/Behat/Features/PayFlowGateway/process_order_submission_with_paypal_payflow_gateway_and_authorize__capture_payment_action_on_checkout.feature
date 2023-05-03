@regression
@ticket-BB-8806
@ticket-BB-14390
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroPaymentBundle:ProductsAndShoppingListsForPayments.yml
@behat-test-env
Feature: Process order submission with PayPal PayFlow Gateway and Authorize & Capture payment action on Checkout
  In order to be able to purchase products using PayPal PayFlow Gateway payment system
  As a Buyer
  I want to be able to make orders under Checkout

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create new PayPal PayFlow Gateway Integration
    Given I proceed as the Admin
    And I login as administrator
    When I go to System/Integrations/Manage Integrations
    And I click "Create Integration"
    And I select "PayPal Payflow Gateway" from "Type"
    And I fill PayPal integration fields with next data:
      | Name                         | PayPalFlow     |
      | Label                        | PayPalFlow     |
      | Short Label                  | PPlFlow        |
      | Allowed Credit Card Types    | Mastercard     |
      | Partner                      | PayPal         |
      | Vendor                       | qwerty123456   |
      | User                         | qwer12345      |
      | Password                     | qwer123423r23r |
      | Payment Action               | Authorize      |
      | Express Checkout Name        | ExpressPayPal  |
      | Express Checkout Label       | ExpressPayPal  |
      | Express Checkout Short Label | ExprPPl        |
    And I save and close form
    Then I should see "Integration saved" flash message
    And I should see PayPalFlow in grid

  Scenario: Create new Payment Rule for PayPal PayFlow Gateway integration
    Given I go to System/Payment Rules
    And I click "Create Payment Rule"
    And I check "Enabled"
    And I fill in "Name" with "PayPalPro"
    And I fill in "Sort Order" with "1"
    And I select "PayPalFlow" from "Method"
    And I click "Add Method Button"
    And I save and close form
    Then I should see "Payment rule has been saved" flash message

  Scenario: User returns to Payment step if he reloads the page on Order Review step
    Given There are products in the system available for order
    And I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 2
    And I click "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I fill credit card form with next data:
      | CreditCardNumber | 5105105105105100 |
      | Month            | 11               |
      | Year             | 2027             |
      | CVV              | 123              |
    And I click "Continue"
    And I reload the page
    Then I should see "Select a Payment Method"

  Scenario: Error from Backend when pay order with PayPal PayFlow Gateway
    Given I open page with shopping list List 2
    When I click "Create Order"
    And I fill credit card form with next data:
      | CreditCardNumber | 5105105105105100 |
      | Month            | 11               |
      | Year             | 2027             |
      | CVV              | 123              |
    And I click "Continue"
    And I click "Submit Order"
    Then I should see "We were unable to process your payment. Please verify your payment information and try again." flash message

  Scenario: Successful order payment with PayPal PayFlow Gateway
    Given I open page with shopping list List 1
    When I click "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I fill credit card form with next data:
      | CreditCardNumber | 5424000000000015 |
      | Month            | 11               |
      | Year             | 2027             |
      | CVV              | 123              |
    And I click "Continue"
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Error during capture
    Given I operate as the Admin
    And I go to System/Integrations/Manage Integrations
    And I click Edit PayPalFlow in grid
    And I fill PayPal integration fields with next data:
      | User | invalid |
    When I save and close form
    Then I should see "Integration saved" flash message
    And I go to Sales/Orders
    And I click View Payment authorized in grid
    And I click "Capture"
    And I should see "Charge The Customer" in the "UiWindow Title" element
    When I click "Yes, Charge" in modal window
    Then I should see "Declined" flash message

  Scenario: Successful capture
    Given I go to System/Integrations/Manage Integrations
    And I click Edit PayPalFlow in grid
    And I fill PayPal integration fields with next data:
      | User | qwer12345 |
    When I save and close form
    Then I should see "Integration saved" flash message
    And I go to Sales/Orders
    And I click View Payment authorized in grid
    And I click "Capture"
    And I should see "Charge The Customer" in the "UiWindow Title" element
    When I click "Yes, Charge" in modal window
    Then I should see "The payment of $13.00 has been captured successfully" flash message

  Scenario: Capture button is not shown in backoffice for unsuccessful order payment
    Given I go to Sales/Orders
    Then there is no "Payment declined" in grid
