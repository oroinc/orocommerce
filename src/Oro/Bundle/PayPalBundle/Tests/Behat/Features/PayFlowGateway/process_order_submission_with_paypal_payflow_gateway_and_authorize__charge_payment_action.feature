@regression
@ticket-BB-15784
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroAuthorizeNetBundle:AuthorizeNetFixture.yml
Feature: Process order submission with PayPal PayFlow Gateway and Authorize & Charge payment action
  ToDo: BAP-16103 Add missing descriptions to the Behat features
  Scenario: Create new PayPal PayFlow Gateway Integration
    Given I login as AmandaRCole@example.org the "Buyer" at "first_session" session
    And I login as administrator and use in "second_session" as "Admin"
    When I go to System/Integrations/Manage Integrations
    And I click "Create Integration"
    And I select "PayPal Payflow Gateway" from "Type"
    And I fill PayPal integration fields with next data:
      | Name                         | PayPalFlow           |
      | Label                        | PayPalFlow           |
      | Short Label                  | PPlFlow              |
      | Allowed Credit Card Types    | Mastercard           |
      | Partner                      | PayPal               |
      | Vendor                       | qwerty123456         |
      | User                         | qwer12345            |
      | Password                     | qwer123423r23r       |
      | Payment Action               | Authorize and Charge |
      | Express Checkout Name        | ExpressPayPal        |
      | Express Checkout Label       | ExpressPayPal        |
      | Express Checkout Short Label | ExprPPl              |
    And I save and close form
    Then I should see "Integration saved" flash message
    And I should see PayPalFlow in grid

  Scenario: Disable guest access to check right callbacks
    Given I go to System/Configuration
    And I follow "Commerce/Guests/Website Access" on configuration sidebar
    When uncheck "Use default" for "Enable Guest Access" field
    And I uncheck "Enable Guest Access"
    And I save form
    Then I should see "Configuration Saved" flash message

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

  Scenario: Error from Backend when pay order with PayPal PayFlow Gateway
    Given There are products in the system available for order
    And I operate as the Buyer
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
    And I click "Submit Order"
    Then I should see "We were unable to process your payment. Please verify your payment information and try again." flash message

  Scenario: Successful order payment with PayPal PayFlow Gateway
    Given There are products in the system available for order
    When I open page with shopping list List 1
    And I click "Create Order"
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

    And I operate as the Admin
    And I go to Sales/Orders
    And I should see Paid in full in grid
