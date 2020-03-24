@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
Feature: Check Money Order payment status
  In order to be able to use Check/Money payment
  As an administrator
  I want to be able to see actual payment status for Order when it's paid by Check/Money payment method

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create new Check/Money Order Integration
    Given I operate as the Admin
    And I login as administrator
    When I go to System/Integrations/Manage Integrations
    And I click "Create Integration"
    And I select "Check/Money Order" from "Type"
    And I fill "Money Check Integration Form" with:
      | Name        | CheckMoneyOrder   |
      | Label       | Check/Money Order |
      | Short Label | Check/Money Order |
      | Pay To      | Oro Inc           |
      | Send To     | Oro Inc           |
    And I save and close form
    Then I should see "Integration saved" flash message

  Scenario: Create new Payment Rule for Check/Money Order integration with invalid expression
    Given I operate as the Admin
    When I go to System/Payment Rules
    And I click "Create Payment Rule"
    And I check "Enabled"
    And I fill in "Name" with "CheckMoneyOrder"
    And I fill in "Sort Order" with "1"
    And I select "CheckMoneyOrder" from "Method"
    And I click "Add Method Button"
    And I fill "Payment Rule Form" with:
      | Expression | name = 'test' |
    And I save and close form
    Then I should see "Payment rule has been saved" flash message

  Scenario: Check there is no errors on storefront
    Given I operate as the Buyer
    And There are products in the system available for order
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I click "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    Then I should not see "Undefined index"

  Scenario: Update Payment Rule for Check/Money Order integration with valid expression
    Given I operate as the Admin
    And I click "Edit"
    And I fill "Payment Rule Form" with:
      | Expression | |
    And I save and close form
    Then I should see "Payment rule has been saved" flash message

  Scenario: Successful order payment with Check/Money Order
    Given I operate as the Buyer
    And on the "Payment" checkout step I go back to "Edit Shipping Method"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Check/Money Order" on the "Payment" checkout step and press Continue
    And I fill "Checkout Order Review Form" with:
      | PO Number | TEST_PO_NUMBER |
    And I should see "Subtotal $10.00"
    And I should see "Shipping $3.00"
    And I should see "Total $13.00"
    When I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Capture order payment with Check/Money Order
    Given I operate as the Admin
    When I go to Sales/ Orders
    And I filter PO Number as is equal to "TEST_PO_NUMBER"
    And click View TEST_PO_NUMBER in grid
    And I should see "Payment Status Pending payment"
    And I should see following "Order Payment Transaction Grid" grid:
      | Payment Method  | Type    | Amount | Successful |
      | CheckMoneyOrder | Pending | $13.00 | Yes        |
    And I click "Capture" on first row in "Order Payment Transaction Grid" grid
    And I should see "The customer will be charged $13.00. Are you sure you want to continue?"
    And I click "Yes, Charge"
    Then I should see "The payment of $13.00 has been captured successfully." flash message
