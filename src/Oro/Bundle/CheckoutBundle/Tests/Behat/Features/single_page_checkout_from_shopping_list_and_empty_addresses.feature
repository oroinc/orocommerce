@regression
@ticket-BB-20680
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroCheckoutBundle:CheckoutProductFixture.yml
@fixture-OroCheckoutBundle:CheckoutCustomerWithoutDefaultAddressesFixture.yml
@fixture-OroCheckoutBundle:InventoryLevel.yml

Feature: Single Page Checkout From Shopping List And Empty Addresses
  In order to create order on front store
  As a buyer
  I want to get validation errors when one of addresses is empty

  Scenario: Feature Background
    Given I activate "Single Page Checkout" workflow

  Scenario: Check both empty addresses validation
    Given I am on the homepage
    And I signed in as AmandaRCole@example.org on the store frontend
    And I open page with shopping list List 1
    And I click "Create Order"
    And I wait "Submit Order" button
    And I click "Submit Order"
    Then I should see "Please enter correct billing address."
    And I should see "Please enter correct shipping address."
    And click "Sign Out"

  Scenario: Check billing address validation
    Given I am on the homepage
    And I signed in as MarleneSBradley@example.com on the store frontend
    And I open page with shopping list List 1
    And I click "Create Order"
    And I wait "Submit Order" button
    And I click "Submit Order"
    Then I should see "Please enter correct shipping address."
    And click "Sign Out"

  Scenario: Check shipping address validation
    Given I am on the homepage
    And I signed in as NancyJSallee@example.org on the store frontend
    And I open page with shopping list List 1
    And I click "Create Order"
    And I wait "Submit Order" button
    And I click "Submit Order"
    Then I should see "Please enter correct billing address."
