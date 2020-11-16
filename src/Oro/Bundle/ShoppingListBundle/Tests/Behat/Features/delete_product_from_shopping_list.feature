@ticket-BB-14045
@fixture-OroShoppingListBundle:ShoppingListWithItemsFixture.yml

Feature: Delete product from Shopping List
  In order to have ability to manage shopping list
  As a Buyer
  I want to delete products from Shopping list on shopping list view page or using popup

  Scenario: Delete product from Shopping List
    Given I login as AmandaRCole@example.org buyer
    And I open shopping list widget
    And I click "Shopping List 1"
    When I click "Delete" on row "AA1" in grid
    And I click "Yes, Delete"
    When I click "Delete" on row "BB2" in grid
    And I click "Yes, Delete"
    Then I should see "There are no shopping List line items"
    And I should not see "Create Order"
    And I should not see "Request Quote"
    And I should not see "Duplicate List"
    And I should see "Delete"

  Scenario: Delete product from Shopping List using popup
    Given I am on the homepage
    And I type "AA1" in "search"
    And I click "Search Button"
    And I should not see "In Shopping List"
    And I should see "Add to Shopping List 1"
    And I click "Shopping List Add"
    And I should see "In Shopping List"
    When I click "Shopping List Edit"
    And I click "Delete"
    And I click "Yes, Delete"
    Then I should see "Shopping list item has been deleted" flash message
    And I should not see "In Shopping List"
    And I should see "Add to Shopping List 1"
