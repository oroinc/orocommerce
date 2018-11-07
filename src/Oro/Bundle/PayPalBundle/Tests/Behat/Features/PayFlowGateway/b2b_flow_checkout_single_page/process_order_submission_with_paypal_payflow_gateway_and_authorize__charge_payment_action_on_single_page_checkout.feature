@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroAuthorizeNetBundle:AuthorizeNetFixture.yml
@ticket-BB-13932

Feature: Process order submission with PayPal PayFlow Gateway and Authorize & Charge payment action on single page checkout

  In order to check that PayPal PayFlow Gateway with "Authorize & Charge" payment action works on single page checkout
  As a user
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
    # Create new PayPal PayFlow Gateway Integration
    And I go to System/Integrations/Manage Integrations
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

  Scenario: Error from Backend when pay order with PayPal PayFlow Gateway
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

  Scenario: Successful order payment with PayPal PayFlow Gateway
    Given I open page with shopping list List 1
    And I click "Create Order"
    And I fill credit card form with next data:
      | CreditCardNumber | 5424000000000015 |
      | Month            | 11               |
      | Year             | 2027             |
      | CVV              | 123              |
    When I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Check order is paid in full
    Given I proceed as the Admin
    When I go to Sales/Orders
    Then I should see following grid:
      | Order Number | Payment Status |
      | 2            | Paid in full   |
