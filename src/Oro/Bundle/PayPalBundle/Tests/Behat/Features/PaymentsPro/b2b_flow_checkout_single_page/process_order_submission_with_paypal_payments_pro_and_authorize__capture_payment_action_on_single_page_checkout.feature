@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroAuthorizeNetBundle:AuthorizeNetFixture.yml
@ticket-BB-8806
@ticket-BB-11433
@ticket-BB-14390
@ticket-BB-13932

Feature: Process order submission with PayPal Payments Pro and Authorize & Capture payment action on Single Page Checkout
  In order to purchase goods using PayPal Payments Pro payment system
  As a Buyer
  I want to enter and complete Single Page Checkout with payment via PayPal

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    # Enable SinglePage checkout
    And I proceed as the Admin
    And I login as administrator
    And go to System/Workflows
    And I click "Activate" on row "Single Page Checkout" in grid
    And I click "Activate"
    And I should see "Workflow activated" flash message
    # Create new PayPal Payments Pro Integration
    And I go to System/Integrations/Manage Integrations
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
    And I should see "Integration saved" flash message
    And I should see PayPalPro in grid
    # Create new Payment Rule for PayPal Payments Pro integration
    And I go to System/Payment Rules
    And I click "Create Payment Rule"
    And I check "Enabled"
    And I fill in "Name" with "PayPalPro"
    And I fill in "Sort Order" with "1"
    And I select "PayPalPro" from "Method"
    And I click "Add Method Button"
    And I save and close form
    And I should see "Payment rule has been saved" flash message

  Scenario: Successful order payment with PayPal Payments Pro
    Given There are products in the system available for order
    And I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I click "Create Order"
    And I fill "PayPal Credit Card Form" with:
      | CreditCardNumber | 5424000000000015 |
      | Month            | 11               |
      | Year             | 2027             |
      | CVV              | 123              |
    And I click "Delete this shopping list after submitting order"
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Check that payment can be captured for the first order
    Given I proceed as the Admin
    And I go to Sales/Orders
    And I click View Payment authorized in grid
    When I click "Capture"
    And I click "Yes, Charge" in modal window
    Then I should see "The payment of $13.00 has been captured successfully" flash message

  Scenario: Successful order payment and error on capture with PayPal Payments Pro
    Given I proceed as the Buyer
    And I open page with shopping list List 1
    And I click "Create Order"
    And I fill "PayPal Credit Card Form" with:
      | CreditCardNumber | 5424000000000015 |
      | Month            | 11               |
      | Year             | 2027             |
      | CVV              | 123              |
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Check capture does not work with invalid credentials
    Given I proceed as the Admin
    And I go to System/Integrations/Manage Integrations
    And I click Edit PayPalPro in grid
    And I fill PayPal integration fields with next data:
      | User | invalid |
    And I save and close form
    Then I should see "Integration saved" flash message

    And I go to Sales/Orders
    And I click View Payment authorized in grid
    When I click "Capture"
    And I click "Yes, Charge" in modal window
    Then I should see "Declined" flash message

  Scenario: Unsuccessful order payment, capture button is not shown in backoffice
    Given I proceed as the Buyer
    And I open page with shopping list List 2
    And I click "Create Order"
    And I fill "PayPal Credit Card Form" with:
      | CreditCardNumber | 5105105105105100 |
      | Month            | 11               |
      | Year             | 2027             |
      | CVV              | 123              |
    And I click "Submit Order"
    Then I should see "We were unable to process your payment. Please verify your payment information and try again." flash message
