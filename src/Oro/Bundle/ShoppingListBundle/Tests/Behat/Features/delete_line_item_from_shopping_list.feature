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
    And I should see following line items in "Shopping List Line Items Table":
      | SKU | Quantity | Unit |
      | AA1 | 1        | set  |
      | AA1 | 2        | item |
    When I delete line item 1 in "Shopping List Line Items Table"
    And I click "Yes, Delete"
    Then I should see "Shopping list item has been deleted" flash message
    And I should see following line items in "Shopping List Line Items Table":
      | SKU | Quantity | Unit |
      | AA1 | 2        | item |
