@feature-BB-21126
@fixture-OroShoppingListBundle:product_kits_add_from_in_shopping_list_dialog.yml

Feature: Product kits add from in shopping list dialog

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
    And I should see "Configure and Add to Shopping List" in the "Product Kit In Shopping List Dialog Action Panel" element
    When I click on "Shopping List 1 Kit Line Item Quantity"
    Then the "Shopping List 1 Kit Line Item Quantity Input" field element should contain "2"
    And I should see "Shopping List 1 Kit Line Item Single Unit" element with text "piece" inside "Grid" element

  Scenario: Configure and Add to Shopping List from "In Shopping List" dialog
    When I click "Configure and Add to Shopping List" in "Product Kit In Shopping List Dialog Action Panel" element
    Then I should see "Product Kit Dialog" with elements:
      | Title                | Product Kit 1                      |
      | Kit Item 1 Name      | Barcode Scanner                    |
      | Kit Item 2 Name      | Base Unit                          |
      | Price                | Total: $41.00                      |
      | Kit Item 1 Product 1 | simple-product-03 Product 3 $31.00 |
      | Kit Item 1 Product 2 | None                               |
      | Kit Item 2 Product 1 | simple-product-01 Product 1 $31.00 |
      | Kit Item 2 Product 2 | simple-product-02 Product 2 $31.00 |
    And "Product Kit Line Item Form" must contain values:
      | Readonly Kit Item Line Item 1 Quantity |   |
      | Kit Item Line Item 2 Quantity          | 1 |
      | Notes                                  |   |
    And "Product Kit Line Item Totals Form" must contain values:
      | Quantity | 1     |
      | Unit     | piece |
    And I should see an "Product Kit Dialog Shopping List Dropdown" element
    When I click "Product Kit Dialog Shopping List Dropdown"
    Then I should see "Create New Shopping List" in the "Shopping List Button Group Menu" element
    And I should see "Add to Product Kit Shopping List" in the "Shopping List Button Group Menu" element
    And I should not see "Remove From Product Kit Shopping List" in the "Shopping List Button Group Menu" element
    And I should not see "Update Product Kit Shopping List" in the "Shopping List Button Group Menu" element

    When I fill "Product Kit Line Item Form" with:
      | Notes | Product Kit 1 Line Item 2 Notes |
    And I click "Add to Product Kit Shopping List" in "Shopping List Button Group Menu" element
    Then I should see 'Product kit has been added to \"Product Kit Shopping List\"' flash message

  Scenario: Check "In Shopping List" dialog
    Given I should see "Product Kit In Shopping List Dialog" with elements:
      | Title                         | Product Kit 1                                           |
      | Shopping List 1 Label         | Product Kit Shopping List                               |
      | Shopping List 1 Configuration | Barcode Scanner[x 2] Product 3 Base Unit[x 2] Product 2 |
      | Shopping List 2 Label         | Product Kit Shopping List                               |
      | Shopping List 2 Configuration | Base Unit[x 1] Product 1                                |
    When I click on "Shopping List 1 Kit Line Item Quantity"
    Then the "Shopping List 1 Kit Line Item Quantity Input" field element should contain "2"
    And I should see "Shopping List 1 Kit Line Item Single Unit" element with text "piece" inside "Grid" element
    When I click on "Shopping List 2 Kit Line Item Quantity"
    Then the "Shopping List 2 Kit Line Item Quantity Input" field element should contain "1"
    And I should see "Shopping List 2 Kit Line Item Single Unit" element with text "piece" inside "Grid" element
    And I close ui dialog
    And click on "Flash Message Close Button"

  Scenario: Check shopping list widget
    When I open shopping list widget
    Then I should see "Product Kit Shopping List" on shopping list widget
    And I should see "2 items | $309.00"
    And I close shopping list widget

  Scenario: Check shopping list view page
    Given I open a new browser tab and set "ProductKitShoppingList" alias for it
    And Buyer is on "Product Kit Shopping List" shopping list
    Then I should see following grid:
      | SKU               | Item                                          | Availability | Qty | Unit   | Price   | Subtotal |
      | product-kit-1     | Product Kit 1 Product Kit 1 Line Item 1 Notes | IN STOCK     | 2   | pieces | $134.00 | $268.00  |
      | simple-product-03 | Barcode Scanner: Product 3                    |              | 2   | pieces | $31.00  |          |
      | simple-product-02 | Base Unit: Product 2                          |              | 2   | pieces | $31.00  |          |
      | product-kit-1     | Product Kit 1 Product Kit 1 Line Item 2 Notes | IN STOCK     | 1   | piece  | $41.00  | $41.00   |
      | simple-product-01 | Base Unit: Product 1                          |              | 1   | piece  | $31.00  |          |
    And I should see "Summary 2 Items"
    And I should see "Subtotal $309.00"
    And I should see "Total $309.00"

  Scenario: Check shopping list edit page
    When I click "Shopping List Actions"
    And click "Edit"
    Then I should see following grid:
      | SKU               | Item                                          | Availability | Qty Update All | Price   | Subtotal |
      | product-kit-1     | Product Kit 1 Product Kit 1 Line Item 1 Notes | IN STOCK     | 2 piece        | $134.00 | $268.00  |
      | simple-product-03 | Barcode Scanner: Product 3                    |              | 2 pieces       | $31.00  |          |
      | simple-product-02 | Base Unit: Product 2                          |              | 2 pieces       | $31.00  |          |
      | product-kit-1     | Product Kit 1 Product Kit 1 Line Item 2 Notes | IN STOCK     | 1 piece        | $41.00  | $41.00   |
      | simple-product-01 | Base Unit: Product 1                          |              | 1 piece        | $31.00  |          |
    And I should see "Summary 2 Items"
    And I should see "Subtotal $309.00"
    And I should see "Total $309.00"

  Scenario: Add one more product kit configuration from "In Shopping List" dialog
    Given I switch to the browser tab "ProductKitViewPage"
    When I click "In Shopping List"
    And I click "Configure and Add to Shopping List" in "Product Kit In Shopping List Dialog Action Panel" element
    And I click "Kit Item Line Item 2 Product 2"
    And I click "Product Kit Dialog Shopping List Dropdown"
    And I click "Add to Product Kit Shopping List" in "Shopping List Button Group Menu" element
    Then I should see 'Product kit has been added to \"Product Kit Shopping List\"' flash message

  Scenario: Check "In Shopping List" dialog
    Given I should see "Product Kit In Shopping List Dialog" with elements:
      | Title                         | Product Kit 1                                           |
      | Shopping List 1 Label         | Product Kit Shopping List                               |
      | Shopping List 1 Configuration | Barcode Scanner[x 2] Product 3 Base Unit[x 2] Product 2 |
      | Shopping List 2 Label         | Product Kit Shopping List                               |
      | Shopping List 2 Configuration | Base Unit[x 1] Product 1                                |
      | Shopping List 3 Label         | Product Kit Shopping List                               |
      | Shopping List 3 Configuration | Base Unit[x 1] Product 2                                |
    When I click on "Shopping List 1 Kit Line Item Quantity"
    Then the "Shopping List 1 Kit Line Item Quantity Input" field element should contain "2"
    And I should see "Shopping List 1 Kit Line Item Single Unit" element with text "piece" inside "Grid" element
    When I click on "Shopping List 2 Kit Line Item Quantity"
    Then the "Shopping List 2 Kit Line Item Quantity Input" field element should contain "1"
    And I should see "Shopping List 2 Kit Line Item Single Unit" element with text "piece" inside "Grid" element
    When I click on "Shopping List 3 Kit Line Item Quantity"
    Then the "Shopping List 3 Kit Line Item Quantity Input" field element should contain "1"
    And I should see "Shopping List 3 Kit Line Item Single Unit" element with text "piece" inside "Grid" element
