@feature-BB-21126
@fixture-OroShoppingListBundle:product_kits_merge_same_configurations.yml

Feature: Product kits merge same configurations

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

  Scenario: Open shopping list edit page
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And Buyer is on "Product Kit Shopping List" shopping list
    When I click "Shopping List Actions"
    And click "Edit"
    Then I should see following grid:
      | SKU               | Product                                       | Availability | Qty Update All | Price   | Subtotal |
      | product-kit-1     | Product Kit 1 Product Kit 1 Line Item 1 Notes | In Stock     | 2 piece        | $134.00 | $268.00  |
      | simple-product-03 | Barcode Scanner: Product 3                    |              | 2 pieces       | $31.00  |          |
      | simple-product-02 | Base Unit: Product 2                          |              | 2 pieces       | $31.00  |          |
      | product-kit-1     | Product Kit 1 Product Kit 1 Line Item 2 Notes | In Stock     | 1 piece        | $41.00  | $41.00   |
      | simple-product-01 | Base Unit: Product 1                          |              | 1 piece        | $31.00  |          |
    And I should see "Summary 2 Items"
    And I should see "Subtotal $309.00"
    And I should see "Total $309.00"

  Scenario: Edit product kit line item
    When I click "Row 1 Edit Line Item"
    Then I should see "Product Kit Dialog" with elements:
      | Title                | Editing "Product Kit 1" in "Product Kit Shopping List" |
      | Kit Item 1 Name      | Barcode Scanner                                        |
      | Kit Item 2 Name      | Base Unit                                              |
      | Price                | Total: $268.00                                         |
      | Kit Item 1 Product 1 | simple-product-03 Product 3 $31.00                     |
      | Kit Item 1 Product 2 | None                                                   |
      | Kit Item 2 Product 1 | simple-product-01 Product 1 $31.00                     |
      | Kit Item 2 Product 2 | simple-product-02 Product 2 $31.00                     |
    And "Product Kit Line Item Form" must contain values:
      | Kit Item Line Item 2 Quantity | 2                               |
      | Kit Item Line Item 1 Quantity | 2                               |
      | Notes                         | Product Kit 1 Line Item 1 Notes |
    And "Product Kit Line Item Totals Form" must contain values:
      | Quantity | 2     |
      | Unit     | piece |
    And I fill "Product Kit Line Item Form" with:
      | Notes | Product Kit 1 Line Item 1 Notes Updated |
    Then I should see "Product Kit Dialog" with elements:
      | Price | Total: $268.00 |
    When I click "Product Kit Dialog Shopping List Dropdown"
    Then I should not see "Create New Shopping List" in the "Shopping List Button Group Menu" element
    And I should see "Update Product Kit Shopping List" in the "Shopping List Button Group Menu" element
    And I should see "Remove From Product Kit Shopping List" in the "Shopping List Button Group Menu" element
    When I click "Update Product Kit Shopping List" in "Shopping List Button Group Menu" element
    Then I should see 'Product kit has been updated in "Product Kit Shopping List"' flash message

  Scenario: Edit another product kit line item
    When I click "Row 4 Edit Line Item"
    Then I should see "Product Kit Dialog" with elements:
      | Title                | Editing "Product Kit 1" in "Product Kit Shopping List" |
      | Kit Item 1 Name      | Barcode Scanner                                        |
      | Kit Item 2 Name      | Base Unit                                              |
      | Price                | Total: $41.00                                          |
      | Kit Item 1 Product 1 | simple-product-03 Product 3 $31.00                     |
      | Kit Item 1 Product 2 | None                                                   |
      | Kit Item 2 Product 1 | simple-product-01 Product 1 $31.00                     |
      | Kit Item 2 Product 2 | simple-product-02 Product 2 $31.00                     |
    And "Product Kit Line Item Form" must contain values:
      | Readonly Kit Item Line Item 2 Quantity |                                 |
      | Kit Item Line Item 1 Quantity          | 1                               |
      | Notes                                  | Product Kit 1 Line Item 2 Notes |
    And "Product Kit Line Item Totals Form" must contain values:
      | Quantity | 1     |
      | Unit     | piece |
    When I click "Kit Item Line Item 1 Product 2"
    And I click "Kit Item Line Item 2 Product 1"
    And I fill "Product Kit Line Item Form" with:
      | Kit Item Line Item 1 Quantity | 2                                       |
      | Kit Item Line Item 2 Quantity | 2                                       |
      | Notes                         | Product Kit 1 Line Item 2 Notes Updated |
    Then I should see "Product Kit Dialog" with elements:
      | Price | Total: $134.00 |
    When I click "Product Kit Dialog Shopping List Dropdown"
    Then I should not see "Create New Shopping List" in the "Shopping List Button Group Menu" element
    And I should see "Update Product Kit Shopping List" in the "Shopping List Button Group Menu" element
    And I should see "Remove From Product Kit Shopping List" in the "Shopping List Button Group Menu" element
    When I click "Update Product Kit Shopping List" in "Shopping List Button Group Menu" element
    Then I should see 'Product kit has been updated in "Product Kit Shopping List"' flash message

  Scenario: Check shopping list edit page
    Then I should see following grid:
      | SKU               | Product                                                                                       | Availability | Qty Update All | Price   | Subtotal |
      | product-kit-1     | Product Kit 1 Product Kit 1 Line Item 1 Notes Updated Product Kit 1 Line Item 2 Notes Updated | In Stock     | 3 piece        | $134.00 | $402.00  |
      | simple-product-03 | Barcode Scanner: Product 3                                                                    |              | 2 pieces       | $31.00  |          |
      | simple-product-02 | Base Unit: Product 2                                                                          |              | 2 pieces       | $31.00  |          |
    And I should see "Summary 1 Item"
    And I should see "Subtotal $402.00"
    And I should see "Total $402.00"
    And click on "Flash Message Close Button"

  Scenario: Check shopping list widget
    When I open shopping list widget
    Then I should see "Product Kit Shopping List" on shopping list widget
    And I should see "1 item | $402.00"
    And I close shopping list widget
