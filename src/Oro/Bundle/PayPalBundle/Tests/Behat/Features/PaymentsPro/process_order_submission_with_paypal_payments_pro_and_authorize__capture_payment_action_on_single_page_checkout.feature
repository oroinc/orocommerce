@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroAuthorizeNetBundle:AuthorizeNetFixture.yml

Feature: Process order submission with PayPal Payments Pro and Authorize & Capture payment action on Single Page Checkout
  ToDo: BAP-16103 Add missing descriptions to the Behat features

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create new PayPal Payments Pro Integration
    Given I operate as the Admin
    And I login as administrator
    When I go to System/Integrations/Manage Integrations
    And I click "Create Integration"
    And I select "PayPal Payments Pro" from "Type"
    And I fill PayPal integration fields with next data:
      | Name                         | PayPalPro            |
      | Label                        | PayPalPro            |
      | Short Label                  | PPlPro               |
      | Allowed Credit Card Types    | Mastercard           |
      | Partner                      | PayPal               |
      | Vendor                       | qwerty123456         |
      | User                         | qwer12345            |
      | Password                     | qwer123423r23r       |
      | Payment Action               | Authorize            |
      | Express Checkout Name        | ExpressPayPal        |
      | Express Checkout Label       | ExpressPayPal        |
      | Express Checkout Short Label | ExprPPl              |
    And I save and close form
    Then I should see "Integration saved" flash message
    And I should see PayPalPro in grid

  Scenario: Create new Payment Rule for PayPal Payment Pro integration
    Given I go to System/Payment Rules
    And I click "Create Payment Rule"
    And I check "Enabled"
    And I fill in "Name" with "PayPalPro"
    And I fill in "Sort Order" with "1"
    And I select "PayPalPro" from "Method"
    And I press "Add Method Button"
    When I save and close form
    Then I should see "Payment rule has been saved" flash message

  Scenario: Enable SinglePage checkout
    Given go to System/Workflows
    When I click "Activate" on row "Single Page Checkout" in grid
    And I click "Activate"
    Then I should see "Workflow activated" flash message

  Scenario: Successful order payment with PayPal Payments Pro
    Given There are products in the system available for order
    And I operate as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I press "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" from "Select Billing Address"
    And I select "Fifth avenue, 10115 Berlin, Germany" from "Select Shipping Address"
    And I check "Flat Rate" on the checkout page
    And I fill "PayPal Credit Card Form" with:
      | CreditCardNumber | 5424000000000015 |
      | Month            | 11               |
      | Year             | 2027             |
      | CVV              | 123              |
    And I press "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Successful capture
    Given I operate as the Admin
    And I go to Sales/Orders
    And I click View Payment authorized in grid
    When I click "Capture"
    Then I should see "Charge The Customer" in the "UiWindow Title" element
    When I click "Yes, Charge" in modal window
    Then I should see "The payment of $13.00 has been captured successfully" flash message

  Scenario: Unsuccessful order payment, capture button is not shown in backoffice
    Given I operate as the Buyer
    When I open page with shopping list List 2
    And I press "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" from "Select Billing Address"
    And I select "Fifth avenue, 10115 Berlin, Germany" from "Select Shipping Address"
    And I check "Flat Rate" on the checkout page
    And I fill "PayPal Credit Card Form" with:
      | CreditCardNumber | 5105105105105100 |
      | Month            | 11               |
      | Year             | 2027             |
      | CVV              | 123              |
    And I press "Submit Order"
    Then I should see only following flash messages:
      | We were unable to process your payment. Please verify your payment information and try again. |

    When I operate as the Admin
    And I go to Sales/Orders
    Then there is no "Payment declined" in grid
