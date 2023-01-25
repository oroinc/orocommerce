@ticket-BB-16550
@ticket-BB-12789
@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:CheckoutWithShippingRule.yml
@fixture-OroCheckoutBundle:Payment.yml

Feature: Checkout with shipping rule
  In order to check shipping rules work correctly on checkout
  As a Buyer
  I need to have shipping rules checking any line item be applied properly

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Ensure shipping rule is applied properly
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I open page with shopping list List1
    And I click "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    Then I should not see "The selected shipping method is not available. Please return to the shipping method selection step and select a different one." flash message
    And I should not see "No shipping methods are available, please contact us to complete the order submission."
    And I should see "Select a Shipping Method"
    And I should see "Flat Rate: $3.00"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Term" on the "Payment" checkout step and press Continue
    Then I should see Checkout Totals with data:
      | Subtotal | $40.00 |
      | Shipping | $3.00  |
    And I should see "Total $43.00"

  Scenario: Create a new shipping rule with new condition that apply to order and to see the change of shipping cost
    When I proceed as the Admin
    And I login as administrator
    And I go to System / Shipping Rules
    And I click "edit" on first row in grid
    And I fill "Shipping Rule" with:
      | Expression | lineItems.any(lineItem.product.sku = "SKU3") |
    And I save form
    Then I should see "Shipping rule has been saved" flash message
    When I go to System / Shipping Rules
    And I click "Create Shipping Rule"
    And fill "Shipping Rule" with:
      | Enable     | true      |
      | Name       | Default 2 |
      | Sort Order | 2         |
      | Currency   | USD       |
      | Method     | Flat Rate |
    And fill "Fast Shipping Rule Form" with:
      | Price        | 1.5       |
      | Handling Fee | 1.0       |
      | Type         | per_order |
    And I save and close form
    When I proceed as the Buyer
    And I reload the page
    Then I should see Checkout Totals with data:
      | Subtotal | $40.00 |
      | Shipping | $2.50  |
    And I should see "Total $42.50"
    When I proceed as the Admin
    When I go to System / Shipping Rules
    And I click "Delete" on row "Default 2" in grid
    And I click "Yes, Delete"
    Then I should see "Shipping Rule deleted"
    And there are 1 records in grid
    When I proceed as the Buyer
    And I reload the page
    Then I should see Checkout Totals with data:
      | Subtotal | $40.00 |
    And I should see "Total $40.00"
    When on the "Order Review" checkout step I go back to "Edit Shipping Method"
    And I should see "No shipping methods are available, please contact us to complete the order submission."
