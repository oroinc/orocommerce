@ticket-BB-7164
@automatically-ticket-tagged
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroCheckoutBundle:InventoryLevel.yml
@community-edition-only
Feature: Checkout workflow
  In order to create order on front store
  As a buyer
  I want to start and complete checkout

  Scenario: Create order from Shopping List 1
    Given There is EUR currency in the system configuration
      And AmandaRCole@example.org customer user has Buyer role
      And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
      And I press "Create Order"
      And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
      And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
      And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
      And I check "Payment Terms" on the "Payment" checkout step and press Continue
      And I check "Delete the shopping list" on the "Order Review" checkout step and press Submit Order
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

    When I follow "click here to review"
    Then I should be on Order Frontend View page

  Scenario: Checking Order History grid with Open Orders
    Given I signed in as AmandaRCole@example.org on the store frontend
    When I open Order History page on the store frontend
    Then there is no records in "OpenOrdersGrid"

    When I reset "Completed" filter on grid "OpenOrdersGrid"
      And I click View Order on List 1 in grid "OpenOrdersGrid"
    Then I should be on Order Frontend View page
