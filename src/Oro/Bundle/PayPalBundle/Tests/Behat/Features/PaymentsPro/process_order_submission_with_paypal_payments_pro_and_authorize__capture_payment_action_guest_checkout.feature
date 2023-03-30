@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroPaymentBundle:ProductsAndShoppingListsForPayments.yml
@behat-test-env
Feature: Process order submission with PayPal Payments Pro and Authorize & Capture payment action guest checkout
  In order to purchase goods using PayPal Payments Pro payment system
  As a Guest customer
  I want to enter and complete checkout without registration with payment via PayPal

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Guest | second_session |

  Scenario: Create new PayPal Payments Pro Integration
    Given I proceed as the Admin
    And I login as administrator
    When I go to System/Integrations/Manage Integrations
    And I click "Create Integration"
    And I select "PayPal Payments Pro" from "Type"
    And I fill PayPal integration fields with next data:
      | Name                         | PayPalPro      |
      | Label                        | PayPalPro      |
      | Short Label                  | PPlPro         |
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
    And I should see PayPalPro in grid
    And I create payment rule with "PayPalPro" payment method

  Scenario: Enable guest shopping list setting
    Given I go to System/ Configuration
    And I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And uncheck "Use default" for "Enable Guest Shopping List" field
    And I check "Enable Guest Shopping List"
    When I save form
    Then I should see "Configuration saved" flash message
    And the "Enable Guest Shopping List" checkbox should be checked

  Scenario: Enable guest checkout setting
    Given I follow "Commerce/Sales/Checkout" on configuration sidebar
    And uncheck "Use default" for "Enable Guest Checkout" field
    And I check "Enable Guest Checkout"
    When I save form
    Then the "Enable Guest Checkout" checkbox should be checked

  Scenario: Create Shopping List as unauthorized user
    Given I proceed as the Guest
    And I am on homepage
    And type "SKU123" in "search"
    And I click "Search Button"
    And I click "product1"
    When I click "Add to Shopping List"
    Then I should see "Product has been added to" flash message and I close it
    When I open shopping list widget
    And I click "View List"
    Then I should see "product1"

  Scenario: Successful order payment with PayPal Payments Pro
    Given I click on "Create Order"
    When I click "Continue as a Guest"
    And I fill form with:
      | First Name      | Tester1         |
      | Last Name       | Testerson       |
      | Email           | tester@test.com |
      | Street          | Fifth avenue    |
      | City            | Berlin          |
      | Country         | Germany         |
      | State           | Berlin          |
      | Zip/Postal Code | 10115           |
    And I click "Ship to This Address"
    And I click "Continue"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I fill credit card form with next data:
      | CreditCardNumber | 5105105105105100 |
      | Month            | 11               |
      | Year             | 2027             |
      | CVV              | 123              |
    And I click "Continue"
    And I uncheck "Save my data and create an account" on the checkout page
    And I click "Submit Order"
    Then I should see "We were unable to process your payment. Please verify your payment information and try again." flash message
    When I click "Flash Message Close Button"
    And I fill credit card form with next data:
      | CreditCardNumber | 5424000000000015 |
      | Month            | 11               |
      | Year             | 2027             |
      | CVV              | 123              |
    And I click "Continue"
    And I uncheck "Save my data and create an account" on the checkout page
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Successful capture
    Given I operate as the Admin
    And I go to Sales/Orders
    And I click View Payment authorized in grid
    When I click "Capture"
    Then I should see "Charge The Customer" in the "UiWindow Title" element
    When I click "Yes, Charge" in modal window
    Then I should see "The payment of $13.00 has been captured successfully" flash message
