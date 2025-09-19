@feature-BB-26165
@fixture-OroShoppingListBundle:product_kits_add_with_shopping_list_limit.yml

Feature: Product kits add with shopping list limit
  In order to check that Add to shopping list button shown on dialog when shopping list limit is 1

  Scenario: Feature Background
    Given I enable configuration options:
      | oro_shopping_list.shopping_list_limit |
    And I set configuration property "oro_shopping_list.shopping_list_limit" to "1"

  Scenario: Search for the product kit
    Given I signed in as AmandaRCole@example.org on the store frontend
    When I type "product-kit-1" in "search"
    And I click "Search Button"
    Then I should not see an "Configure and Add to Shopping List" element

  Scenario: Add product kit to shopping list
    When I click "View Details" for "Product Kit 1" product
    Then I should see an "Configure and Add to Shopping List" element
    And I should not see an "In Shopping List" element
    When I click "Configure and Add to Shopping List"
    Then I should see an "Add to Shopping List button" element
