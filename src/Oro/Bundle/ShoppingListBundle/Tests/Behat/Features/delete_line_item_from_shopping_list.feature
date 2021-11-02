@ticket-BB-14070
@fixture-OroShoppingListBundle:ShoppingListFixture.yml

Feature: Delete line item from Shopping List
  In order to have ability to manage shopping list
  As a Buyer
  I want to delete line item from Shopping List and this should not affect
  another line items with the same product but different unit

  Scenario: Delete product from Shopping List
    Given I login as AmandaRCole@example.org buyer
    And Buyer is on "Shopping List 5" shopping list
    And I click "Shopping List Actions"
    And I click "Edit"
    And I should see following grid:
      | SKU | Qty Update All |
      | AA1 | 1 set          |
      | AA1 | 2 item         |
    When I click Delete AA1 in grid
    And I click "Yes, Delete" in modal window
    Then I should see 'The "Product1" product was successfully deleted' flash message
    And I should see following grid:
      | SKU | Qty Update All |
      | AA1 | 2 item         |
