@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroCheckoutBundle:InventoryLevel.yml

Feature: Saved addresses with different types on "Default" Checkout workflow
  In order to create order on front store
  As a buyer
  I want to start "Default" checkout and use saved address with different types

  Scenario: Create order and select billing address type
    Given There is EUR currency in the system configuration
    And AmandaRCole@example.org customer user has Buyer role
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I click "Create Order"
    And I fill "Billing Information Form" with:
      | Billing Address | ORO, Fourth avenue, 10111 Berlin, Germany |
    Then I should not see "Ship to this address" element inside "Billing Information Form" element

  Scenario: Select billing and shipping address type
    Given I fill "Billing Information Form" with:
      | Billing Address | ORO, Fifth avenue, 10115 Berlin, Germany |
    Then I should see "Ship to this address" element inside "Billing Information Form" element
