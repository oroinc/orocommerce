@regression
@ticket-BB-26335
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroCheckoutBundle:CheckoutCustomerAddressWithoutRegionFixture.yml
@fixture-OroCheckoutBundle:CheckoutProductFixture.yml
@fixture-OroCheckoutBundle:CheckoutShoppingListFixture.yml
@fixture-OroCheckoutBundle:CheckoutQuoteFixture.yml

Feature: Checkout by customer user with address change
  In order to create order from Shopping List on front store
  As a buyer
  I want to be able to change order shipping address without region error from previous address

  Scenario: Feature Background
    Given sessions active:
      | Buyer | first_session |

  Scenario: Create order for customer
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I am on homepage
    And I open page with shopping list List 1
    And I click "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And on the "Shipping Method" checkout step I go back to "Edit Shipping Information"
    And I select "ORO, EASTERN DISTRICT, PAGO PAGO AMERICAN SAMOA 96799" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    When I check "Delete this shopping list after submitting order" on the "Order Review" checkout step and press Submit Order
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
