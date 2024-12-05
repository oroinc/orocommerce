@feature-BB-21126
@fixture-OroShoppingListBundle:product_kits_remove_from_shopping_list_page.yml

Feature: Product kits remove from shopping list page

  Scenario: Feature Background
    Given There is USD currency in the system configuration
    And sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    And I login as administrator
    And I go to System / Localization / Translations
    And I filter Key as equal to "oro.frontend.shoppinglist.lineitem.unit.label"
    And I edit "oro.frontend.shoppinglist.lineitem.unit.label" Translated Value as "Unit"

  Scenario: Remove product kit from shopping list edit page
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And Buyer is on "Product Kit Shopping List" shopping list
    And I click "Shopping List Actions"
    And click "Edit"
    When I click "Delete" on row "Product Kit 1 Line Item 1 Notes" in grid
    Then I should see "Are you sure you want to delete this product?"
    When click "Yes, Delete"
    Then I should see 'The "Product Kit 1" product was successfully deleted' flash message

  Scenario: Check shopping list view page
    Given I open a new browser tab and set "ProductKitShoppingList" alias for it
    And Buyer is on "Product Kit Shopping List" shopping list
    Then I should see following grid:
      | SKU               | Product                                       | Availability | Qty | Unit  | Price  | Subtotal |
      | product-kit-1     | Product Kit 1 Product Kit 1 Line Item 2 Notes | IN STOCK     | 1   | piece | $41.00 | $41.00   |
      | simple-product-01 | Base Unit: Product 1                          |              | 1   | piece | $31.00 |          |
    And I should see "Summary 1 Item"
    And I should see "Total $41.00"

  Scenario: Check shopping list edit page
    When I click "Shopping List Actions"
    And click "Edit"
    Then I should see following grid:
      | SKU               | Product                                       | Availability | Qty Update All | Price  | Subtotal |
      | product-kit-1     | Product Kit 1 Product Kit 1 Line Item 2 Notes | IN STOCK     | 1 piece        | $41.00 | $41.00   |
      | simple-product-01 | Base Unit: Product 1                          |              | 1 piece        | $31.00 |          |
    And I should see "Summary 1 Item"
    And I should see "Total $41.00"

  Scenario: Remove product kit from shopping list edit page
    Given Buyer is on "Product Kit Shopping List" shopping list
    And I click "Shopping List Actions"
    And click "Edit"
    When I click "Delete" on row "Product Kit 1 Line Item 2 Notes" in grid
    Then I should see "Are you sure you want to delete this product?"
    When click "Yes, Delete"
    Then I should see 'The "Product Kit 1" product was successfully deleted' flash message

  Scenario: Check shopping list view page
    When Buyer is on "Product Kit Shopping List" shopping list
    Then there are no records in grid
    And I should see "There are no shopping list line items"
    And I should see "Summary No Items"
    And I should see "Total: $0.00"

  Scenario: Check shopping list edit page
    When I click "Shopping List Actions"
    And click "Edit"
    Then there are no records in grid
    And I should see "There are no shopping list line items"
    And I should see "Summary No Items"
    And I should see "Total: $0.00"
