@ticket-BB-27192
@regression
@fixture-OroShoppingListBundle:ShoppingListOrphanedListFixture.yml

Feature: Orphaned shopping list is not reused when its owner is deleted
  In order not to add products to an orphaned shopping list left behind by a deleted customer user
  As a customer user
  I expect a new shopping list to be created for me when I click "Add to Shopping List"

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create Shopping List as the first customer user
    Given I proceed as the Buyer
    And I signed in as cu1@example.org on the store frontend
    And I type "product_simple_1" in "search"
    And I click "Search Button"
    When I click "View Details" for "product_simple_1" product
    And I click "Add to Shopping List"
    Then I should see 'Product has been added to "Shopping List"' flash message and I close it
    When I open shopping list widget
    And I click "Shopping List" on shopping list widget
    And I click "Shopping List Actions"
    And I click "Rename"
    And I fill "Shopping List Rename Action Form" with:
      | Label | SL1 |
    And I click "Shopping List Action Submit"
    Then I should see "Shopping list has been successfully renamed" flash message and I close it

  Scenario: Delete the first customer user
    Given I proceed as the Admin
    And I login as administrator
    And I go to Customers/ Customer Users
    And I click delete "cu1@example.org" in grid
    And I click "Yes, Delete"
    Then I should see "Customer User deleted" flash message

  Scenario: Create Shopping List as the second customer user
    Given I proceed as the Buyer
    And I signed in as cu2@example.org on the store frontend
    And I type "product_simple_1" in "search"
    And I click "Search Button"
    When I click "View Details" for "product_simple_1" product
    And I click "Add to Shopping List"
    Then I should see 'Product has been added to "Shopping List"' flash message and I close it
    When I open shopping list widget
    Then I should see "Shopping List" on shopping list widget
    And I should not see "SL1" on shopping list widget
    When I click "Shopping List" on shopping list widget
    Then I should see following grid:
      | SKU              | Product          |
      | product_simple_1 | Simple product 1 |
