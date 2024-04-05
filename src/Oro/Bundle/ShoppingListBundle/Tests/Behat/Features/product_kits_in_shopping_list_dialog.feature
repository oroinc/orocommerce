@feature-BB-21126
@fixture-OroShoppingListBundle:product_kits_in_shopping_list_dialog.yml

Feature: Product kits in shopping list dialog

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

  Scenario: Open product kit view page
    When I click "View Details" for "Product Kit 1" product
    And I set alias "ProductKitViewPage" for the current browser tab
    Then I should see an "In Shopping List" element

  Scenario: Open "In Shopping List" dialog
    When I click "In Shopping List"
    Then I should see "Product Kit In Shopping List Dialog" with elements:
      | Title                         | Product Kit 1                                           |
      | Shopping List 1 Label         | Product Kit Shopping List                               |
      | Shopping List 1 Configuration | Barcode Scanner[x 2] Product 3 Base Unit[x 2] Product 2 |
      | Shopping List 2 Label         | Product Kit Shopping List                               |
      | Shopping List 2 Configuration | Base Unit[x 1] Product 1                                |
      | Shopping List 3 Label         | Product Kit Shopping List                               |
      | Shopping List 3 Configuration | Barcode Scanner[x 1] Product 3 Base Unit[x 1] Product 2 |
    When I click on "Shopping List 1 Kit Line Item Quantity"
    Then the "Shopping List 1 Kit Line Item Quantity Input" field element should contain "2"
    And the "Shopping List 1 Kit Line Item Unit Select" field element should contain "piece"
    When I click on "Shopping List 2 Kit Line Item Quantity"
    Then the "Shopping List 2 Kit Line Item Quantity Input" field element should contain "1"
    And the "Shopping List 2 Kit Line Item Unit Select" field element should contain "piece"

  Scenario: Update quantity in "In Shopping List" dialog
    When I click on "Shopping List 1 Kit Line Item Quantity"
    And I type "3" in "Shopping List 1 Kit Line Item Quantity Input"
    And I click on "Shopping List 1 Kit Line Item Save Changes Button"
    And I close ui dialog

  Scenario: Check shopping list widget
    When I open shopping list widget
    Then I should see "Product Kit Shopping List" on shopping list widget
    And I should see "3 items | $515.00"
    And I close shopping list widget

  Scenario: Check shopping list view page
    Given I open a new browser tab and set "ProductKitShoppingList" alias for it
    When Buyer is on "Product Kit Shopping List" shopping list
    Then I should see following grid:
      | SKU               | Item                                          |          | Qty | Unit   | Price   | Subtotal |
      | product-kit-1     | Product Kit 1 Product Kit 1 Line Item 1 Notes | In Stock | 3   | pieces | $134.00 | $402.00  |
      | simple-product-03 | Barcode Scanner: Product 3                    |          | 2   | pieces | $31.00  |          |
      | simple-product-02 | Base Unit: Product 2                          |          | 2   | pieces | $31.00  |          |
      | product-kit-1     | Product Kit 1 Product Kit 1 Line Item 2 Notes | In Stock | 1   | piece  | $41.00  | $41.00   |
      | simple-product-01 | Base Unit: Product 1                          |          | 1   | piece  | $31.00  |          |
      | product-kit-1     | Product Kit 1 Product Kit 1 Line Item 3 Notes | In Stock | 1   | piece  | $72.00  | $72.00   |
      | simple-product-03 | Barcode Scanner: Product 3                    |          | 1   | piece  | $31.00  |          |
      | simple-product-02 | Base Unit: Product 2                          |          | 1   | piece  | $31.00  |          |
    And I should see "Summary 3 Items"
    And I should see "Subtotal $515.00"
    And I should see "Total $515.00"

  Scenario: Check shopping list edit page
    When I click "Shopping List Actions"
    And click "Edit"
    Then I should see following grid:
      | SKU               | Item                                          |          | Qty Update All | Price   | Subtotal |
      | product-kit-1     | Product Kit 1 Product Kit 1 Line Item 1 Notes | In Stock | 3 piece        | $134.00 | $402.00  |
      | simple-product-03 | Barcode Scanner: Product 3                    |          | 2 pieces       | $31.00  |          |
      | simple-product-02 | Base Unit: Product 2                          |          | 2 pieces       | $31.00  |          |
      | product-kit-1     | Product Kit 1 Product Kit 1 Line Item 2 Notes | In Stock | 1 piece        | $41.00  | $41.00   |
      | simple-product-01 | Base Unit: Product 1                          |          | 1 piece        | $31.00  |          |
      | product-kit-1     | Product Kit 1 Product Kit 1 Line Item 3 Notes | In Stock | 1 piece        | $72.00  | $72.00   |
      | simple-product-03 | Barcode Scanner: Product 3                    |          | 1 piece        | $31.00  |          |
      | simple-product-02 | Base Unit: Product 2                          |          | 1 piece        | $31.00  |          |
    And I should see "Summary 3 Items"
    And I should see "Subtotal $515.00"
    And I should see "Total $515.00"

  Scenario: Edit product kit line item from "In Shopping List" dialog
    Given I switch to the browser tab "ProductKitViewPage"
    When I click "In Shopping List"
    And I click "Shopping List 2 Kit Line Item Edit Button"
    Then I should see "Product Kit Dialog" with elements:
      | Title                | Editing "Product Kit 1" in "Product Kit Shopping List" |
      | Kit Item 1 Name      | Barcode Scanner                                        |
      | Kit Item 2 Name      | Base Unit                                              |
      | Price                | Total: $41.00                            |
      | Kit Item 1 Product 1 | simple-product-03 Product 3 $31.00                     |
      | Kit Item 1 Product 2 | None                                                   |
      | Kit Item 2 Product 1 | simple-product-01 Product 1 $31.00                     |
      | Kit Item 2 Product 2 | simple-product-02 Product 2 $31.00                     |
    And I should not see "Disabled Product 1"
    And I should not see "DD01"
    # Kit Item Line Items sorted by sort order (Barcode Scanner, then Base Unit)
    And "Product Kit Line Item Form" must contain values:
      | Readonly Kit Item Line Item 2 Quantity |                                 |
      | Kit Item Line Item 1 Quantity          | 1                               |
      | Notes                                  | Product Kit 1 Line Item 2 Notes |
    And "Product Kit Line Item Totals Form" must contain values:
      | Quantity | 1     |
      | Unit     | piece |
    When I fill "Product Kit Line Item Totals Form" with:
      | Quantity | 2 |
    Then I should see "Product Kit Dialog" with elements:
      | Price | Total: $82.00 |
    When I click "Product Kit Dialog Shopping List Dropdown"
    Then I should not see "Create New Shopping List" in the "Shopping List Button Group Menu" element
    And I should see "Update Product Kit Shopping List" in the "Shopping List Button Group Menu" element
    And I should see "Remove From Product Kit Shopping List" in the "Shopping List Button Group Menu" element
    When I click "Update Product Kit Shopping List" in "Shopping List Button Group Menu" element
    Then I should see 'Product kit has been updated in "Product Kit Shopping List"' flash message

  Scenario: Edit another product kit line item from "In Shopping List" dialog
    When I click "Shopping List 3 Kit Line Item Edit Button"
    Then I should see "Product Kit Dialog" with elements:
      | Title                | Editing "Product Kit 1" in "Product Kit Shopping List" |
      | Kit Item 1 Name      | Barcode Scanner                                        |
      | Kit Item 2 Name      | Base Unit                                              |
      | Price                | Total: $72.00                            |
      | Kit Item 1 Product 1 | simple-product-03 Product 3 $31.00                     |
      | Kit Item 1 Product 2 | None                                                   |
      | Kit Item 2 Product 1 | simple-product-01 Product 1 $31.00                     |
      | Kit Item 2 Product 2 | simple-product-02 Product 2 $31.00                     |
    And I should not see "Disabled Product 1"
    And I should not see "DD01"
    # Kit Item Line Items sorted by sort order (Barcode Scanner, then Base Unit)
    And "Product Kit Line Item Form" must contain values:
      | Kit Item Line Item 2 Quantity | 1                               |
      | Kit Item Line Item 1 Quantity | 1                               |
      | Notes                         | Product Kit 1 Line Item 3 Notes |
    And "Product Kit Line Item Totals Form" must contain values:
      | Quantity | 1     |
      | Unit     | piece |
    When I click "Kit Item Line Item 1 Product 2"
    And I click "Kit Item Line Item 2 Product 1"
    And I fill "Product Kit Line Item Form" with:
      | Notes | Product Kit 1 Line Item 3 Notes Updated |
    Then I should see "Product Kit Dialog" with elements:
      | Price | Total: $41.00 |
    When I click "Product Kit Dialog Shopping List Dropdown"
    Then I should not see "Create New Shopping List" in the "Shopping List Button Group Menu" element
    And I should see "Update Product Kit Shopping List" in the "Shopping List Button Group Menu" element
    And I should see "Remove From Product Kit Shopping List" in the "Shopping List Button Group Menu" element
    When I click "Update Product Kit Shopping List" in "Shopping List Button Group Menu" element
    Then I should see 'Product kit has been updated in "Product Kit Shopping List"' flash message

  Scenario: Check "In Shopping List" dialog
    And I should see "Product Kit In Shopping List Dialog" with elements:
      | Title                         | Product Kit 1                                           |
      | Shopping List 1 Label         | Product Kit Shopping List                               |
      | Shopping List 1 Configuration | Barcode Scanner[x 2] Product 3 Base Unit[x 2] Product 2 |
      | Shopping List 2 Label         | Product Kit Shopping List                               |
      | Shopping List 2 Configuration | Base Unit[x 1] Product 1                                |
    When I click on "Shopping List 1 Kit Line Item Quantity"
    Then the "Shopping List 1 Kit Line Item Quantity Input" field element should contain "3"
    And the "Shopping List 1 Kit Line Item Unit Select" field element should contain "piece"
    When I click on "Shopping List 2 Kit Line Item Quantity"
    Then the "Shopping List 2 Kit Line Item Quantity Input" field element should contain "3"
    And the "Shopping List 2 Kit Line Item Unit Select" field element should contain "piece"

  Scenario: Check product kit line item edit dialog
    When I click "Shopping List 2 Kit Line Item Edit Button"
    Then I should see "Product Kit Dialog" with elements:
      | Title                | Editing "Product Kit 1" in "Product Kit Shopping List" |
      | Kit Item 1 Name      | Barcode Scanner                                        |
      | Kit Item 2 Name      | Base Unit                                              |
      | Price                | Total: $123.00                           |
      | Kit Item 1 Product 1 | simple-product-03 Product 3 $31.00                     |
      | Kit Item 1 Product 2 | None                                                   |
      | Kit Item 2 Product 1 | simple-product-01 Product 1 $31.00                     |
      | Kit Item 2 Product 2 | simple-product-02 Product 2 $31.00                     |
    And "Product Kit Line Item Form" must contain values:
      | Readonly Kit Item Line Item 2 Quantity |                                                                         |
      | Kit Item Line Item 1 Quantity          | 1                                                                       |
      | Notes                                  | Product Kit 1 Line Item 2 Notes Product Kit 1 Line Item 3 Notes Updated |
    When I close ui dialog
    And I close ui dialog
    And click on "Flash Message Close Button"

  Scenario: Check shopping list widget
    When I open shopping list widget
    Then I should see "Product Kit Shopping List" on shopping list widget
    And I should see "2 items | $525.00"
    And I close shopping list widget

  Scenario: Check shopping list view page
    Given I switch to the browser tab "ProductKitShoppingList"
    And Buyer is on "Product Kit Shopping List" shopping list
    Then I should see following grid:
      | SKU               | Item                                                                                  |          | Qty | Unit   | Price   | Subtotal |
      | product-kit-1     | Product Kit 1 Product Kit 1 Line Item 1 Notes                                         | In Stock | 3   | pieces | $134.00 | $402.00  |
      | simple-product-03 | Barcode Scanner: Product 3                                                            |          | 2   | pieces | $31.00  |          |
      | simple-product-02 | Base Unit: Product 2                                                                  |          | 2   | pieces | $31.00  |          |
      | product-kit-1     | Product Kit 1 Product Kit 1 Line Item 2 Notes Product Kit 1 Line Item 3 Notes Updated | In Stock | 3   | pieces | $41.00  | $123.00  |
      | simple-product-01 | Base Unit: Product 1                                                                  |          | 1   | piece  | $31.00  |          |
    And I should see "Summary 2 Items"
    And I should see "Subtotal $525.00"
    And I should see "Total $525.00"

  Scenario: Check shopping list edit page
    When I click "Shopping List Actions"
    And click "Edit"
    Then I should see following grid:
      | SKU               | Item                                                                                  |          | Qty Update All | Price   | Subtotal |
      | product-kit-1     | Product Kit 1 Product Kit 1 Line Item 1 Notes                                         | In Stock | 3 piece        | $134.00 | $402.00  |
      | simple-product-03 | Barcode Scanner: Product 3                                                            |          | 2 pieces       | $31.00  |          |
      | simple-product-02 | Base Unit: Product 2                                                                  |          | 2 pieces       | $31.00  |          |
      | product-kit-1     | Product Kit 1 Product Kit 1 Line Item 2 Notes Product Kit 1 Line Item 3 Notes Updated | In Stock | 3 piece        | $41.00  | $123.00  |
      | simple-product-01 | Base Unit: Product 1                                                                  |          | 1 piece        | $31.00  |          |
    And I should see "Summary 2 Items"
    And I should see "Subtotal $525.00"
    And I should see "Total $525.00"
