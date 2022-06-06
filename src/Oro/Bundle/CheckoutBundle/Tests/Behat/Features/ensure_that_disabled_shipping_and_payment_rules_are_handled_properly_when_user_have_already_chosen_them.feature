@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroFlatRateShippingBundle:FlatRate2Integration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTerm30Integration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroCheckoutBundle:PaymentRuleForPaymentTerm30.yml
@fixture-OroCheckoutBundle:ShippingRuleForFlatRate2.yml
@community-edition-only

Feature: Ensure that disabled shipping and payment rules are handled properly when user have already chosen them

  Scenario: Create two session
    Given sessions active:
      | Admin  | first_session  |
      | Buyer  | second_session |

  Scenario: Reach "Order Review" step
    Given I proceed as the Buyer
    Given There is EUR currency in the system configuration
    And AmandaRCole@example.org customer user has Buyer role
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I click "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I click on "ShippingMethodFlatRate2"
    And on the "Shipping" checkout step I press Continue
    And I click on "PaymentMethodPaymentTerm30"
    And on the "Payment" checkout step I press Continue

  Scenario: Disable shipping rule
    Given I proceed as the Admin
    And login as administrator
    And go to System/ Shipping Rules
    And I click Disable "Flat Rate 2$" in grid
    Then I should see "Shipping rule has been disabled successfully" flash message

  Scenario: Ensure order cannot be submitted when shipping method is not available anymore
    Given I proceed as the Buyer
    And I click "Submit Order"
    Then I should see "The selected shipping method is not available. Please return to the shipping method selection step and select a different one." flash message
    And on the "Order Review" checkout step I go back to "Edit Shipping Method"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I click on "PaymentMethodPaymentTerm30"
    And on the "Payment" checkout step I press Continue

  Scenario: Disable payment rule
    Given I proceed as the Admin
    And go to System/ Payment Rules
    And I click Disable "Payment Term 30" in grid
    Then I should see "Payment rule has been disabled successfully" flash message

  Scenario: Ensure order cannot be submitted when payment method is not available anymore
    Given I proceed as the Buyer
    And I click "Submit Order"
    Then I should see "The selected payment method is not available. Please return to the payment method selection step and select a different one." flash message
    And on the "Order Review" checkout step I go back to "Edit Payment"
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I check "Delete this shopping list after submitting order" on the "Order Review" checkout step and press Submit Order
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
