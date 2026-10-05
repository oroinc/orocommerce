@ticket-BB-27650
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroCheckoutBundle:ShoppingListForCheckoutForNancy.yml
@fixture-OroCheckoutBundle:CheckoutCustomerFixture.yml
@fixture-OroOrderBundle:ShoppingListReassignForCheckout.yml

Feature: Order to checkout page access for checkout of another customer
  In order to keep the data of other customers protected
  As a buyer I should not be able to start a checkout from a checkout
  or a shopping list I have no access to

  Scenario: Buyer starts a checkout from own shopping list
    Given I signed in as NancyJSallee@example.org on the store frontend
    When I open page with shopping list Shopping List 6
    And I click "Create Order"
    Then Checkout "Order Summary Products Grid" should contain products:
      | Product1 | 1 | item |
    And I remember ID from current URL as "victim_checkout_id"

  Scenario: Buyer of another customer is denied access to the order to checkout page
    Given I signed in as AmandaRCole@example.org on the store frontend
    When I open order to checkout page for the checkout "$victim_checkout_id$"
    Then I should see "403 Forbidden"
    And I should see "You don't have permission to access this page."
    When I open Order History page on the store frontend
    Then there is no records in "OpenOrdersGrid"

  Scenario: Owner of the checkout is still able to open the order to checkout page
    Given I signed in as NancyJSallee@example.org on the store frontend
    When I open order to checkout page for the checkout "$victim_checkout_id$"
    Then Checkout "Order Summary Products Grid" should contain products:
      | Product1 | 1 | item |

  Scenario: Checkout products are not refilled from the shopping list reassigned to another buyer
    Given I set configuration property "oro_shopping_list.show_all_in_shopping_list_widget" to "1"
    And I signed in as RuthWMaxwell@example.org on the store frontend
    And I open page with shopping list Shopping List 6
    When I click "Shopping List Actions"
    And I click "Reassign"
    And I filter First Name as is equal to "John" in "Shopping List Action Reassign Grid"
    And I click "Shopping List Action Reassign Radio"
    And I click "Shopping List Action Submit"
    Then I should see "John Doe"
    When I signed in as john@example.org on the store frontend
    And I type "AA2" in "search"
    And I click "Search Button"
    And I click "Product2"
    And I click "Shopping List Dropdown"
    And I click "Add to Shopping List 6"
    Then I should see 'Product has been added to "Shopping List 6"' flash message
    When I signed in as NancyJSallee@example.org on the store frontend
    And I open order to checkout page for the checkout "$victim_checkout_id$"
    Then Checkout "Order Summary Products Grid" should contain products:
      | Product1 | 1 | item |
    And Checkout "Order Summary Products Grid" should not contain products:
      | Product2 | 1 | item |
