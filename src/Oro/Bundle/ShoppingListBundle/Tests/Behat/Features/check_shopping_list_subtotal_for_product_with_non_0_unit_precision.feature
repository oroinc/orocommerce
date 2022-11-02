@regression
@ticket-BB-17724
@fixture-OroShoppingListBundle:ShoppingListWithFractionalPriceFixture.yml

Feature: Check shopping list subtotal for product with non 0 unit precision
  In order to have correct subtotal for product with different qty that has several fractional and
  unit precision equals to 2
  As an Buyer
    I should see correct subtotals on Shopping list page

  Scenario: Check shopping list subtotals
    Given I login as AmandaRCole@example.org buyer
    And I open page with shopping list Shopping List 1
    And I click on "Shopping List Line Item 1 Quantity"
    And I type "0.6" in "Shopping List Line Item 1 Quantity Input"
    And I click on "Shopping List Line Item 1 Save Changes Button"
    Then I should see "Subtotal $9.59" in the "Subtotals" element

    And I click on "Shopping List Line Item 1 Quantity"
    And I type "10" in "Shopping List Line Item 1 Quantity Input"
    And I click on "Shopping List Line Item 1 Save Changes Button"
    And I click on empty space
    Then I should see "Subtotal $129.90" in the "Subtotals" element

    And I click on "Shopping List Line Item 1 Quantity"
    And I type "200.5" in "Shopping List Line Item 1 Quantity Input"
    And I click on "Shopping List Line Item 1 Save Changes Button"
    And I click on empty space
    Then I should see "Subtotal $2,203.50" in the "Subtotals" element
