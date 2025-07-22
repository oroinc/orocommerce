@feature-BB-21126
@fixture-OroShoppingListBundle:product_kits_remove_from_in_shopping_list_dialog.yml

Feature: Product kits remove from in shopping list dialog

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
      | Shopping List 1 Configuration | Barcode Scanner(x2) Product 3 Base Unit(x2) Product 2 |
      | Shopping List 2 Label         | Product Kit Shopping List                               |
      | Shopping List 2 Configuration | Base Unit(x1) Product 1                                |

  Scenario: Remove Product Kit Line Item from "In Shopping List" dialog
    When I click "Shopping List 1 Kit Line Item Delete Button"
    Then I should see "Are you sure you want to delete this product?"
    When click "Yes, Delete"
    Then I should see 'The "Product Kit 1" product was successfully deleted' flash message
    And I should see "Product Kit In Shopping List Dialog" with elements:
      | Title                         | Product Kit 1             |
      | Shopping List 1 Label         | Product Kit Shopping List |
      | Shopping List 1 Configuration | Base Unit(x1) Product 1  |

  Scenario: Check shopping list view page
    Given I open a new browser tab and set "ProductKitShoppingList" alias for it
    And Buyer is on "Product Kit Shopping List" shopping list
    Then I should see following grid:
      | SKU               | Product                                       | Availability | Qty | Unit  | Price  | Subtotal |
      | product-kit-1     | Product Kit 1 Product Kit 1 Line Item 2 Notes | In Stock     | 1   | piece | $41.00 | $41.00   |
      | simple-product-01 | Base Unit: Product 1                          |              | 1   | piece | $31.00 |          |
    And I should see "Summary 1 Item"
    And I should see "Total $41.00"

  Scenario: Check shopping list edit page
    When I click "Shopping List Actions"
    And click "Edit"
    Then I should see following grid:
      | SKU               | Product                                       | Availability | Qty Update All | Price  | Subtotal |
      | product-kit-1     | Product Kit 1 Product Kit 1 Line Item 2 Notes | In Stock     | 1 piece        | $41.00 | $41.00   |
      | simple-product-01 | Base Unit: Product 1                          |              | 1 piece        | $31.00 |          |
    And I should see "Summary 1 Item"
    And I should see "Total $41.00"

  Scenario: Remove Product Kit Line Item from "In Shopping List" dialog
    Given I switch to the browser tab "ProductKitViewPage"
    When I click "Shopping List 1 Kit Line Item Delete Button"
    Then I should see "Are you sure you want to delete this product?"
    When click "Yes, Delete"
    Then I should see 'The "Product Kit 1" product was successfully deleted' flash message
    And I should see "Product Kit In Shopping List Dialog" with elements:
      | Title   | Product Kit 1                         |
      | No Data | There are no shopping list line items |

  Scenario: Check shopping list view page
    Given I switch to the browser tab "ProductKitShoppingList"
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
