@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroAuthorizeNetBundle:AuthorizeNetFixture.yml
@ticket-BB-13932

Feature: Order submission with PayPal PayFlow Gateway and zero "authorization amount" option on single page checkout

  In order to check that PayPal PayFlow Gateway and zero "authorization amount" option works on single page checkout
  As a user
  I want to finish checkout and save credit card data
  I want to finish checkout using already saved credit card data

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
    # Create new PayPal PayFlow Gateway Integration
    And I go to System/Integrations/Manage Integrations
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
      | Zero Amount Authorization    | true           |
      | Payment Action               | Authorize      |
      | Express Checkout Name        | ExpressPayPal  |
      | Express Checkout Label       | ExpressPayPal  |
      | Express Checkout Short Label | ExprPPl        |
    And I save and close form
    And I should see "Integration saved" flash message
    And I should see PayPalFlow in grid
    # Create new Payment Rule for PayPal PayFlow Gateway integration
    And I go to System/Payment Rules
    And I click "Create Payment Rule"
    And I check "Enabled"
    And I fill in "Name" with "PayPalFlow"
    And I fill in "Sort Order" with "1"
    And I select "PayPalFlow" from "Method"
    And I click "Add Method Button"
    And I save and close form
    And I should see "Payment rule has been saved" flash message
    And I wait for action

  Scenario: Successful first order payment with PayPal PayFlow Gateway and enabled "zero authorization amount" option
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
    And I wait for action
    When I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Check that payment can be captured for the first order
    Given I proceed as the Admin
    And I go to Sales/Orders
    And I click View Pending payment in grid
    When I click "Capture"
    And I click "Yes, Charge" in modal window
    Then I should see "The payment of $13.00 has been captured successfully" flash message

  Scenario: Successful second order payment and amount capture with PayPal PayFlow Gateway and already saved credit card data
    Given I proceed as the Buyer
    When I open page with shopping list List 2
    And I click "Create Order"
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Check that payment can be captured for the second order
    Given I proceed as the Admin
    And I go to Sales/Orders
    And I click View Pending payment in grid
    When I click "Capture"
    And I click "Yes, Charge" in modal window
    Then I should see "The payment of $13.00 has been captured successfully" flash message
