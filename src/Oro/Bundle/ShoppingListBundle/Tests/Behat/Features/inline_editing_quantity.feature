@regression
@ticket-BB-13435
@ticket-BAP-20647
@fixture-OroShoppingListBundle:ShoppingListWithProductBigPriceFixture.yml
Feature: Inline Editing Quantity
  In order to manage quantity of products in a shopping list
  As a Buyer
  I should not be able to exceed the maximum allowed shopping list subtotal

  Scenario: Check maximum quantity to order for line item
    Given I login as AmandaRCole@example.org buyer
    And I open page with shopping list Shopping List 1
    And I click on "Shopping List Line Item 1 Quantity"
    And I type "10000000000000000000000" in "Shopping List Line Item 1 Quantity Input"
    And I click on empty space
    And I should see "This value should be between 0 and 1,000,000,000."
    And I type "1" in "Shopping List Line Item 1 Quantity Input"
    And I should not see "This value should be between 0 and 1,000,000,000."

  Scenario: Exceed shopping list subtotal by setting huge quantity for line item
    When I click on empty space
    And I click on "Shopping List Line Item 1 Quantity"
    And I type "999999999" in "Shopping List Line Item 1 Quantity Input"
    And I click "Shopping List Line Item 1 Save Changes Button"
    When I should see "Shopping list subtotal amount cannot exceed the 999999999999999.9999" flash message
