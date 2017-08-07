@fixture-OroShoppingListBundle:ShoppingListRule.yml
Feature: Check shopping list popup

  Scenario: Change shipping list units in popup
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I click "Shopping List Add"
    Then I click "Shopping List Edit"
    And I should see "Choose list"
    And I fill "Shopping List Form" with:
      | List      | Shopping list |
      | Unit      | set           |
      | Quantity  | 5             |
    And press "Item Add"
    Then I click "Item Edit"
    And I click "Item Edit Unit"
    Then I should see a "Item Disabled" element
