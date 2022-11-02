@fixture-OroShoppingListBundle:ShoppingListFixture.yml

Feature: Check shopping list quick search

  Scenario: Quick search doesn't display
    Given I signed in as AmandaRCole@example.org on the store frontend
    And type "AA1" in "search"
    And I click "Search Button"
    And I should see "Product1"
    And I click "Product1"
    And I click "Shopping List Dropdown"
    Then I should not see an "Shopping List Quick Search" element

  Scenario: Quick search does display if dropdown has more 5 items
    Given I click "Create New Shopping List"
    And I type "Shopping List 2" in "NewShoppingListNameField"
    And I click "Create and Add"
    And I click "Shopping List Dropdown"
    Then I should see an "Shopping List Quick Search" element

  Scenario: Find item via quick search
    Given I type "Update shopping list 5" in "Shopping List Quick Search"
    And I should see "Highlight Container" element inside "ShoppingListButtonGroup" element
    And I should see "Highlighted Text" element with text "Update Shopping List 5" inside "ShoppingListButtonGroup" element

  Scenario: Clear quick search field
    Given I click "Shopping List Quick Search Clear"
    And I should not see "Highlight Container" element inside "ShoppingListButtonGroup" element

  Scenario: Check quick search after shopping list was update
    Given I click "Add to Shopping List 1"
    Then I click "Shopping List Dropdown"
    And I should see an "Shopping List Quick Search" element
    And I type "Update shopping list 1" in "Shopping List Quick Search"
    And I should see "Highlight Container" element inside "ShoppingListButtonGroup" element
    And I should see "Highlighted Text" element with text "Update Shopping List 1" inside "ShoppingListButtonGroup" element
    And I type "Add to Shopping List 5" in "Shopping List Quick Search"
    And I should not see "Highlighted Text" element with text "Update Shopping List 5" inside "ShoppingListButtonGroup" element
    And I type "Update Shopping List 5" in "Shopping List Quick Search"
    And I should see "Highlighted Text" element with text "Update Shopping List 5" inside "ShoppingListButtonGroup" element
    And I type "Some other text" in "Shopping List Quick Search"
    And I should not see "Highlight Container" element inside "ShoppingListButtonGroup" element
    And I click "Shopping List Quick Search Clear"
