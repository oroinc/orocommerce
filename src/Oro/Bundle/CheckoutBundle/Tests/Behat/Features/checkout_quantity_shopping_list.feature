@ticket-BB-8592
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroCheckoutBundle:SmallInventoryLevel.yml
@skip
Feature: Checkout from Shopping List Quantity Errors
  In order to to create order from Shopping List on front store
  As a buyer
  I want to start checkout from Shopping List view page and view quantity validation errors before submit order

  Scenario: Create order from Shopping List 1
    Given There is EUR currency in the system configuration
    And AmandaRCole@example.org customer user has Buyer role
    And I signed in as AmandaRCole@example.org on the store frontend

    When I open page with shopping list List 1
    And I press "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    Then I should see "There is not enough quantity for this product"

    When I check "Delete the shopping list" on the "Order Review" checkout step and press Submit Order
    Then I should see "There was an error while processing the order"
    And I should see "There is not enough quantity for this product"
