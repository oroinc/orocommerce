@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroAlternativeCheckoutBundle:AlternativeCheckout.yml
@fixture-OroCheckoutBundle:InventoryLevel.yml
Feature: Alternative Checkout workflow threshold
  In order to create order on front store
  As a buyer
  I want to start and request approval of alternative checkout

  Scenario: Activate Alternative Checkout workflow
    Given I login as administrator
    When I go to System/ Workflows
    And I click Activate Alternative Checkout in grid
    And I press "Activate"
    Then I should see "Workflow activated" flash message

  Scenario: Create order with Alternative Checkout with threshold
    Given There is EUR currency in the system configuration
    And AmandaRCole@example.org customer user has Buyer role
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List Threshold
    And I press "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I check "Delete the shopping list" on the "Order Review" checkout step and press Request Approval
    Then I should see "You exceeded the allowable amount of $5000."

    When I press "Request Approval"
    Then I should see "Pending approval"
