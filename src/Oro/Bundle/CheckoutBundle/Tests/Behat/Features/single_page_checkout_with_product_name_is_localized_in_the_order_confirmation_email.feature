@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroCheckoutBundle:CheckoutCustomerFixture.yml
@fixture-OroCheckoutBundle:CheckoutLocalizedProductFixture.yml
@fixture-OroCheckoutBundle:CheckoutShoppingListFixture.yml
@fixture-OroCheckoutBundle:CheckoutQuoteFixture.yml
@fixture-OroCheckoutBundle:InventoryLevel.yml

Feature: Single page checkout with product name is localized in the order confirmation email
  As a Customer User
  I want to see localized product name in the order confirmation email

  Scenario: Feature Background
    Given I enable the existing localizations
    And I activate "Single Page Checkout" workflow

  Scenario: Create order, process checkout and check order confirmation email
    Given AmandaRCole@example.org customer user has Buyer role
    And I signed in as AmandaRCole@example.org on the store frontend
    When I click on "Localization dropdown"
    Then I click "Zulu"
    When I open page with shopping list List 1
    And I scroll to top
    And I wait line items are initialized
    And I click "Create Order"
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    And Email should contains the following "PG-PA103(MG-MA103)" text
