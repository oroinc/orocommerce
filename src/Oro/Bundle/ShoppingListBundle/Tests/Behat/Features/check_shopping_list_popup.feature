@regression
@fixture-OroShoppingListBundle:ShoppingListWidgetPopupFixture.yml
Feature: Check shopping list popup

  Scenario: Change shipping list units in popup
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I type "SKU2" in "search"
    And I click "Search Button"
    When I click "Shopping List Add"
    And I click "Shopping List Edit"
    And I should see "Choose list"
    And I fill "Shopping List Form" with:
      | List      | Shopping List |
      | Unit      | set           |
      | Quantity  | 5             |
    And click "Item Add"
    And I click "Item Edit"
    And I click "Item Edit Unit"
    Then I should see a "Item Disabled" element

  Scenario: Shopping list add validation
    Given I am on the homepage
    And I open shopping list widget
    And I click "Create New List"
    And I type "" in "New Shopping List Name Field"
    And I click "Create"
    And I should see "This value should not be blank."
    And I type "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer sed elementum eros. Suspendisse odio magna, finibus et tellus euismod, dapibus dapibus magna. Proin ut tortor sed dui tincidunt pellentesque. Donec vel pharetra odio, ac varius ligula. Pellentesque tempus suscipit cursus." in "New Shopping List Name Field"
    And I click "Create"
    And I should see "This value is too long. It should have 255 characters or less."
