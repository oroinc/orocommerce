@ticket-BB-14572
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:ShippingDestinationUnitedStates.yml
@fixture-OroCheckoutBundle:CheckoutCustomerFixture.yml
@fixture-OroCheckoutBundle:CheckoutCustomerUSAddressFixture.yml
@fixture-OroCheckoutBundle:CheckoutProductFixture.yml
@fixture-OroCheckoutBundle:CheckoutShoppingListFixture.yml
@fixture-OroCheckoutBundle:CheckoutQuoteFixture.yml
@fixture-OroCheckoutBundle:InventoryLevel.yml

Feature: Single Page Checkout No Shipping Methods
  In order to complete single page checkout process
  As a Customer User
  I want to proceed checkout after "No shipping methods available" error is fixed by choosing suitable Shipping Address

  Scenario: Feature Background
    Given There is USD currency in the system configuration
    And I activate "Single Page Checkout" workflow

  Scenario: Create order from Shopping List 1 and verify quantity
    Given AmandaRCole@example.org customer user has Buyer role
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I scroll to top
    And I wait line items are initialized
    And I click "Create Order"
    Then Checkout "Order Summary Products Grid" should contain products:
      | 400-Watt Bulb Work Light | 5 | items |
    And I should see Checkout Totals with data:
      | Subtotal | $10.00 |

  Scenario: Submit order button is activated after suitable shipping address is chosen
    Given I should see "No shipping methods are available, please contact us to complete the order submission."
    When I select "ORO, First avenue, HOLLYWOOD FL US 33019" from "Select Shipping Address"
    And I fill "Checkout Order Review Form" with:
      | Do not ship later than | Jul 1, 2018 |
    Then I wait "Submit Order" button
    And I should not see "No shipping methods are available, please contact us to complete the order submission."
    When I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
