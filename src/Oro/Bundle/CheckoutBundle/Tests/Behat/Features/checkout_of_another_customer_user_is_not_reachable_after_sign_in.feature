@ticket-BB-27963
@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroCheckoutBundle:ShoppingListForCheckoutForNancy.yml

Feature: Checkout of another customer user is not reachable after sign in
  In order to keep an in-progress checkout private to the buyer who started it
  As a Buyer
  I want a checkout of another customer user to stay out of my reach and to survive my sign in

  Scenario: Feature Background
    Given I enable configuration options:
      | oro_shopping_list.availability_for_guests |
      | oro_checkout.guest_checkout               |

  Scenario: A checkout started by one buyer is not reachable by another buyer who signs in
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I open page with shopping list List 1
    When I click "Create Order"
    And I click "Continue"
    Then I should see "Shipping Information"
    And I remember current URL

    When I signed in as NancyJSallee@example.org on the store frontend
    And I follow remembered URL
    Then I should see "403 Forbidden You don't have permission to access this page."
    And I should not see "Shipping Information"

    When I signed in as AmandaRCole@example.org on the store frontend
    And I follow remembered URL
    Then I should see "Shipping Information"
