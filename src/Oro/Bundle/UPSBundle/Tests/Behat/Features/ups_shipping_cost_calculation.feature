@behat-test-env
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroUPSBundle:ProductWithShippingOptions.yml
@fixture-OroUPSBundle:Integration.yml
@fixture-OroUPSBundle:ShippingMethodsConfigsRule.yml
Feature: UPS shipping cost calculation
  In order to be able to use UPS as a shipping provider
  As a Buyer
  I need to be able to get UPS shipping cost during checkout

  Scenario: Check that UPS shipping cost is calculated correctly on all steps
    Given I expect the following shipping costs:
      | Method          | Cost  | Currency |
      | UPS 2nd Day Air | 99.75 | USD      |
    When I login as AmandaRCole@example.org buyer
    And I open page with shopping list List 1
    And I scroll to top
    And I wait line items are initialized
    When I click "Create Order"
    Then Buyer is on enter billing information checkout step
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I should see "UPS 2nd Day Air: $1,199.75"
    And I check "UPS 2nd Day Air" on the "Shipping Method" checkout step and press Continue
    Then I see next subtotals for "Checkout Step":
      | Subtotal | Amount    |
      | Shipping | $1,199.75 |
    When I check "Payment Terms" on the "Payment" checkout step and press Continue
    Then I see next subtotals for "Checkout Step":
      | Subtotal | Amount    |
      | Shipping | $1,199.75 |
    When I click "Submit Order"
    Then I should see "Thank You For Your Purchase!"
