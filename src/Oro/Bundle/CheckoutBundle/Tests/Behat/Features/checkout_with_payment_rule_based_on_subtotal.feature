@ticket-BB-15968
@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroCheckoutBundle:InventoryLevel.yml

Feature: Checkout with payment rule based on subtotal
  In order to process checkout
  As a Buyer
  I need to have an ability to use payment rules based on subtotal, without additional costs like shipping price

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | User  | second_session |

  Scenario: Create payment rule based on subtotal value and check usage during checkout
    Given I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I click "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    Then I should see Checkout Totals with data:
      | Subtotal | $10.00 |
      | Shipping | $3.00  |
    And I should see "Total $13.00"
    And I should see "SELECT A PAYMENT METHOD"
    And I should see "Payment Term"

    When I proceed as the Admin
    And I login as administrator
    And I go to System / Payment Rules
    And I click "edit" on first row in grid
    And I fill "Payment Rule Form" with:
      | Expression | subtotal.value < 10 |
    And I save form
    Then I should see "Payment rule has been saved" flash message
    When I proceed as the User
    And I reload the page
    Then I should see "The selected payment method is not available. Please return to the payment method selection step and select a different one." flash message
    And I should see "No payment methods are available, please contact us to complete the order submission."

    When I proceed as the Admin
    And I fill "Payment Rule Form" with:
      | Expression | subtotal.value < 13 |
    And I save form
    When I proceed as the User
    And I reload the page
    Then I should not see "The selected payment method is not available. Please return to the payment method selection step and select a different one." flash message
    And I should not see "No payment methods are available, please contact us to complete the order submission."
    And I should see "SELECT A PAYMENT METHOD"
    And I should see "Payment Term"
