@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroCheckoutBundle:InventoryLevel.yml
@fixture-OroCheckoutBundle:CheckoutWorkflow.yml

Feature: Javascript validation on "Default" Checkout workflow
  In order to create order on front store
  As a buyer
  I want to start "Default" checkout and see validation errors

  Scenario: Check validation error
    Given There is EUR currency in the system configuration
    And AmandaRCole@example.org customer user has Buyer role
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I click "Create Order (Custom)"
    And I click "Continue"
    Then I should see "This value should not be blank."

  Scenario: Check validation without error
    Given I open page with shopping list List 1
    And I click "Create Order (Custom)"
    And I fill "Checkout Order Review Form" with:
      | Notes | Customer test note |
    And I click "Continue"
    Then I should not see "This value should not be blank."
    And I should see "Thank You For Your Purchase!"
