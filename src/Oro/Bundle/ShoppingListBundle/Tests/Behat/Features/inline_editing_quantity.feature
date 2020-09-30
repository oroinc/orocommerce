@regression
@ticket-BB-13435
@fixture-OroShoppingListBundle:ShoppingListWithProductPriceFixture.yml
Feature: Inline Editing Quantity
  In order to manage quantity of products in a shopping list
  As a Buyer
  I should not be able to exceed the maximum allowed shopping list subtotal

  Scenario: Exceed shopping list subtotal by setting huge quantity for line item
    Given I login as AmandaRCole@example.org buyer
    And I open page with shopping list Shopping List 1
    And I click on "Shopping List Line Item 1 Quantity"
    And I type "1000000000000000" in "Shopping List Line Item 1 Quantity Input"
    And I click on "Shopping List Line Item 1 Save Changes Button"
    Then I should see "Shopping list subtotal amount cannot exceed the 999999999999999.9999" flash message
