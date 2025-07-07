@feature-BB-21126
@fixture-OroShoppingListBundle:product_kits_add_with_required_kit_items.yml

Feature: Product kits add with required kit items

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

  Scenario: Search for the product kit
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I type "productkit1" in "search"
    And I click "Search Button"
    Then I should not see an "Configure and Add to Shopping List" element

  Scenario: Add Product Kit with only required Kit Items to Shopping List
    When I click "View Details" for "Product Kit 1" product
    Then I should see an "Configure and Add to Shopping List" element
    And I should not see an "In Shopping List" element
    When I click "Configure and Add to Shopping List"
    Then I should see "Product Kit Dialog" with elements:
      | Title                | Product Kit 1                    |
      | Kit Item 1 Name      | Barcode Scanner                  |
      | Kit Item 2 Name      | Base Unit                        |
      | Price                | Total: $41.00                    |
      | Kit Item 1 Product 1 | simpleproduct03 Product 3 $31.00 |
      | Kit Item 1 Product 2 | None                             |
      | Kit Item 2 Product 1 | simpleproduct01 Product 1 $31.00 |
      | Kit Item 2 Product 2 | simpleproduct02 Product 2 $31.00 |
    And "Product Kit Line Item Form" must contain values:
      | Readonly Kit Item Line Item 1 Quantity |   |
      | Kit Item Line Item 2 Quantity          | 1 |
      | Notes                                  |   |
    And "Product Kit Line Item Totals Form" must contain values:
      | Quantity | 1     |
      | Unit     | piece |
    And I should see "Product Kit Dialog" with elements:
      | Price | Total: $41.00 |
    And I should see an "Product Kit Dialog Shopping List Dropdown" element
    When I click "Product Kit Dialog Shopping List Dropdown"
    Then I should see "Create New Shopping List" in the "Shopping List Button Group Menu" element
    And I should see "Add to Shopping List 1" in the "Shopping List Button Group Menu" element
    When I click "Create New Shopping List" in "Shopping List Button Group Menu" element
    And I fill in "Shopping List Name" with "Product Kit Shopping List"
    And I click "Create and Add"
    Then I should see 'Product kit has been added to \"Product Kit Shopping List\"' flash message
    And I should see an "In Shopping List" element

  Scenario: Check "In Shopping List" dialog
    When I click "In Shopping List"
    Then I should see "Product Kit In Shopping List Dialog" with elements:
      | Title                         | Product Kit 1             |
      | Shopping List 1 Label         | Product Kit Shopping List |
      | Shopping List 1 Configuration | Base Unit(x1) Product 1  |
    And I close ui dialog
    And click on "Flash Message Close Button"

  Scenario: Check shopping list widget
    When I open shopping list widget
    Then I should see "Product Kit Shopping List" on shopping list widget
    And I should see "1 item | $41.00"
    And I close shopping list widget

  Scenario: Check shopping list view page
    When Buyer is on "Product Kit Shopping List" shopping list
    Then I should see following grid:
      | SKU             | Product              | Availability | Qty | Unit  | Price  | Subtotal |
      | productkit1     | Product Kit 1        | In Stock     | 1   | piece | $41.00 | $41.00   |
      | simpleproduct01 | Base Unit: Product 1 |              | 1   | piece | $31.00 |          |
    And I should see "Summary 1 Item"
    And I should see "Subtotal $41.00"
    And I should see "Total $41.00"

  Scenario: Check shopping list edit page
    When I click "Shopping List Actions"
    And click "Edit"
    Then I should see following grid:
      | SKU             | Product              | Availability | Qty Update All | Price  | Subtotal |
      | productkit1     | Product Kit 1        | In Stock     | 1 piece        | $41.00 | $41.00   |
      | simpleproduct01 | Base Unit: Product 1 |              | 1 piece        | $31.00 |          |
    And I should see "Summary 1 Item"
    And I should see "Subtotal $41.00"
    And I should see "Total $41.00"

  Scenario: Remove product kit from shopping list
    When I click "Delete" on row "productkit1" in grid
    Then I should see "Are you sure you want to delete this product?"
    When click "Yes, Delete"
    Then I should see 'The "Product Kit 1" product was successfully deleted' flash message
    And I should see "There are no shopping list line items"
    And I should see "Summary No Items"
    And I should see "Total: $0.00"
