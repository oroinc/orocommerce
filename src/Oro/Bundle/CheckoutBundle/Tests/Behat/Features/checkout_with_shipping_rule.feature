@ticket-BB-16550
@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroCheckoutBundle:CheckoutWithShippingRule.yml

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
    And I should see "SELECT A SHIPPING METHOD"
    And I should see "Flat Rate: $3.00"

  Scenario: Make shipping rule based on subtotal value and ensure it is applied properly despite the applied coupons
    When I proceed as the Admin
    And I login as administrator
    And I go to System / Shipping Rules
    And I click "edit" on first row in grid
    And I fill "Shipping Rule" with:
      | Expression | lineItems.any(lineItem.product.sku = "SKU3") |
    And I save form
    Then I should see "Shipping rule has been saved" flash message

    When I proceed as the Buyer
    And I reload the page
    Then I should see "The selected shipping method is not available. Please return to the shipping method selection step and select a different one." flash message
    And I should see "No shipping methods are available, please contact us to complete the order submission."

