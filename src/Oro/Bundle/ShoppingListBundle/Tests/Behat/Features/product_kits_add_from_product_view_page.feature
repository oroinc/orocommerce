@feature-BB-21126
@fixture-OroShoppingListBundle:product_kits_add_from_product_view_page.yml

Feature: Product kits add from product view page

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
    When I type "product-kit-1" in "search"
    And I click "Search Button"
    Then I should not see an "Configure and Add to Shopping List" element

  Scenario: Add product kit to shopping list
    When I click "View Details" for "Product Kit 1" product
    Then I should see an "Configure and Add to Shopping List" element
    And I should not see an "In Shopping List" element
    When I click "Configure and Add to Shopping List"
    Then I should see "Product Kit Dialog" with elements:
      | Title                | Product Kit 1                      |
      | Kit Item 1 Name      | Barcode Scanner                    |
      | Kit Item 2 Name      | Base Unit                          |
      | Price                | Price as configured: $41.00        |
      | Kit Item 1 Product 1 | simple-product-03 Product 3 $31.00 |
      | Kit Item 1 Product 2 | None                               |
      | Kit Item 2 Product 1 | simple-product-01 Product 1 $31.00 |
      | Kit Item 2 Product 2 | simple-product-02 Product 2 $31.00 |
    And I should not see "Disabled Product 1"
    And I should not see "DD01"
    # Kit Item Line Items sorted by sort order (Barcode Scanner, then Base Unit)
    And "Product Kit Line Item Form" must contain values:
      | Readonly Kit Item Line Item 1 Quantity |   |
      | Kit Item Line Item 2 Quantity          | 1 |
      | Notes                                  |   |
    And "Product Kit Line Item Totals Form" must contain values:
      | Quantity | 1     |
      | Unit     | piece |
    And I should see an "Product Kit Dialog Shopping List Dropdown" element
    When I click "Kit Item Line Item 1 Product 1"
    Then "Product Kit Line Item Form" must contain values:
      | Kit Item Line Item 1 Quantity | 1 |
    And I should see "Product Kit Dialog" with elements:
      | Price | Price as configured: $72.00 |
    When I click "Kit Item Line Item 2 Product 2"
    And I fill "Product Kit Line Item Form" with:
      | Kit Item Line Item 1 Quantity | 2                               |
      | Kit Item Line Item 2 Quantity | 2                               |
      | Notes                         | Product Kit 1 Line Item 1 Notes |
    And I fill "Product Kit Line Item Totals Form" with:
      | Quantity | 2 |
    Then I should see "Product Kit Dialog" with elements:
      | Price | Price as configured: $268.00 |
    When I click "Product Kit Dialog Shopping List Dropdown"
    Then I should see "Create New Shopping List" in the "Shopping List Button Group Menu" element
    And I should not see "Remove From Shopping List 1" in the "Shopping List Button Group Menu" element
    When I click "Create New Shopping List" in "Shopping List Button Group Menu" element
    And I fill in "Shopping List Name" with "Product Kit Shopping List"
    And I click "Create and Add"
    Then I should see 'Product kit has been added to \"Product Kit Shopping List\"' flash message
    And click on "Flash Message Close Button"
    And I should see an "In Shopping List" element

  Scenario: Check "In Shopping List" dialog
    When I click "In Shopping List"
    Then I should see "Product Kit In Shopping List Dialog" with elements:
      | Title                         | Product Kit 1                                           |
      | Shopping List 1 Label         | Product Kit Shopping List                               |
      | Shopping List 1 Configuration | Barcode Scanner[x 2] Product 3 Base Unit[x 2] Product 2 |
    And I close ui dialog

  Scenario: Check shopping list widget
    When I open shopping list widget
    Then I should see "Product Kit Shopping List" on shopping list widget
    And I should see "1 item | $268.00"
    And I close shopping list widget

  Scenario: Check shopping list view page
    Given Buyer is on "Product Kit Shopping List" shopping list
    Then I should see following grid:
      | SKU               | Item                                          |          | Qty | Unit   | Price   | Subtotal |
      | product-kit-1     | Product Kit 1 Product Kit 1 Line Item 1 Notes | In Stock | 2   | pieces | $134.00 | $268.00  |
      | simple-product-03 | Barcode Scanner: Product 3                    |          | 2   | pieces | $31.00  |          |
      | simple-product-02 | Base Unit: Product 2                          |          | 2   | pieces | $31.00  |          |
    And I should see "Summary 1 Item"
    And I should see "Subtotal $268.00"
    And I should see "Total $268.00"

  Scenario: Check shopping list edit page
    When I click "Shopping List Actions"
    And click "Edit"
    Then I should see following grid:
      | SKU               | Item                                          |          | Qty Update All | Price   | Subtotal |
      | product-kit-1     | Product Kit 1 Product Kit 1 Line Item 1 Notes | In Stock | 2 piece        | $134.00 | $268.00  |
      | simple-product-03 | Barcode Scanner: Product 3                    |          | 2 pieces       | $31.00  |          |
      | simple-product-02 | Base Unit: Product 2                          |          | 2 pieces       | $31.00  |          |
    And I should see "Summary 1 Item"
    And I should see "Subtotal $268.00"
    And I should see "Total $268.00"
