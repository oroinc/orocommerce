@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
Feature: Payment Term payment status
  In order to be able to use Payment Term payment
  As an administrator
  I want to be able to see actual payment status for Order when it's paid by Payment Term payment method

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create new Payment Term Integration
    Given I operate as the Admin
    And I login as administrator
    When I go to System/Integrations/Manage Integrations
    And I click "Create Integration"
    And I select "Payment Term" from "Type"
    And I fill "Payment Term Integration Form" with:
      | Name        | PaymentTerm |
      | Label       | PaymentTerm |
      | Short Label | PaymentTerm |
    And I save and close form
    Then I should see "Integration saved" flash message

  Scenario: Create new Payment Rule for Payment Term integration
    Given I operate as the Admin
    When I go to System/Payment Rules
    And I click "Create Payment Rule"
    And I check "Enabled"
    And I fill in "Name" with "PaymentTerm"
    And I fill in "Sort Order" with "1"
    And I select "PaymentTerm" from "Method"
    And I click "Add Method Button"
    And I save and close form
    Then I should see "Payment rule has been saved" flash message

  Scenario: Successful order payment with Payment Term
    Given I operate as the Buyer
    And There are products in the system available for order
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I click "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Term" on the "Payment" checkout step and press Continue
    And I fill "Checkout Order Review Form" with:
      | PO Number | TEST_PO_NUMBER |
    And I should see "Subtotal $10.00"
    And I should see "Shipping $3.00"
    And I should see "Total $13.00"
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Capture order payment with Payment Term
    Given I operate as the Admin
    When I go to Sales/ Orders
    And I filter PO Number as is equal to "TEST_PO_NUMBER"
    And click View TEST_PO_NUMBER in grid
    And I should see "Payment Status Pending payment"
    And I should see following "Order Payment Transaction Grid" grid:
      | Payment Method | Type    | Amount | Successful |
      | Payment Term   | Pending | $13.00 | Yes        |
    And I click "Capture" on first row in "Order Payment Transaction Grid" grid
    And I should see "The customer will be charged $13.00. Are you sure you want to continue?"
    And I click "Yes, Charge"
    Then I should see "The payment of $13.00 has been captured successfully." flash message
