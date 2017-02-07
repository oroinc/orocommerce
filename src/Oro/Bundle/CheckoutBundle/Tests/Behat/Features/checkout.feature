@fixture-Checkout.yml
Feature: Checkout workflow
  Scenario: Create order from Shopping List 1
    Given There is EUR currency in the system configuration
      And AmandaRCole@example.org customer user has Buyer role
      And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
      And I press "Create Order"
      And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
      And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
      And I had checked "Flat Rate" on the "Shipping Method" checkout step and press Continue
      And I had checked "Payment Terms" on the "Payment" checkout step and press Continue
      And I had checked "Delete the shopping list" on the "Order Review" checkout step and press Submit Order
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

    When I follow "click here to review"
    Then I should see an Order page with Order #1

  Scenario: Checking Order History grid with Open Orders
    Given I signed in as AmandaRCole@example.org on the store frontend
    When I open Order History page on the store frontend
    Then there is no records in grid "OpenOrdersGrid"

    When I reset "Completed" filter on grid "OpenOrdersGrid"
      And I click View Order on List 1 in grid "OpenOrdersGrid"
    Then I should see an Order page with Order #1
