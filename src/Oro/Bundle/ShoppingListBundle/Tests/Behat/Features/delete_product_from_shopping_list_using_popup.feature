@fixture-OroShoppingListBundle:DeleteProductFromShoppingList.yml
Feature: Delete product from Shopping List using popup
  As Customer User I have a possibility to delete products from Shopping list using popup

  Scenario: Delete product from Shopping List
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I should not see "In Shopping List"
    And I should see "Add to Shopping List"
    And I click "Shopping List Add"
    And I should see "In Shopping List"
    And I should not see "Add to Shopping List"
    And I should see "Update Shopping List"
    Then I click "Shopping List Edit"
    Then I click "Delete"
    And I click "Yes, Delete"
    Then I should see "Shopping list item has been deleted" flash message
    And I should not see "In Shopping List"
    And I should see "Add to Shopping List"