@feature-BB-21124
@ticket-BB-22446
@fixture-OroProductBundle:product_kits_management_ui.yml

Feature: Product kits management UI
  In order to manage product kits
  As an Administrator
  I want to have ability of adding and edit Product Kits

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session |

  Scenario: Create Product Kit product type
    Given I proceed as the Admin
    When I login as administrator
    And go to Products/ Products
    And click "Create Product"
    And fill "ProductForm Step One" with:
      | Type | Kit |
    And I click "Continue"
    And fill "Create Product Form" with:
      | SKU    | Product-with-kit |
      | Name   | Product with Kit |
      | Status | Enable           |
    And I click "Add Kit Item"
    Then "ProductKitForm" must contain values:
      | Kit Item 1 Sort Order | 1 |
      | Kit Item 2 Sort Order | 2 |
    And I should see that "Kit Item 1 Title" does not contain "Kit Item 1"
    When I click "Add Product" in "Product Kit Item 1" element
    And I click on PSKU2 in grid "KitItemProductsAddGrid"
    And I click "Add Product" in "Product Kit Item 1" element
    And I click on PSKU3 in grid "KitItemProductsAddGrid"
    And I click "Add Product" in "Product Kit Item 1" element
    And I click on PSKU4 in grid "KitItemProductsAddGrid"
    And I click "Add Product" in "Product Kit Item 1" element
    And I click on PSKU5 in grid "KitItemProductsAddGrid"
    And I fill "ProductKitForm" with:
      | Kit Item 1 Label      | Kit Item 1 |
      | Kit Item 1 Sort Order | 2          |
    Then I should see that "Kit Item 1 Title" contains "Kit Item 1"
    When I save and close form
    Then I should see "ProductKitForm" validation errors:
      | Kit Item 2 Label    | This value should not be blank.                             |
      | Kit Item 2 Products | Each kit option should have at least one product specified. |
    When I fill "ProductKitForm" with:
      | Kit Item 2 Label      | Kit Item 2 |
      | Kit Item 2 Sort Order | 1          |
    And I click "Add Product" in "Product Kit Item 2" element
    And I click on PSKU7 in grid "KitItemProductsAddGrid"
    And I save and close form
    Then I should see "Product has been saved" flash message
    When I click "Kit Item 1 Toggler"
    Then records in "Kit Item 1 Products Grid" should be 1
    When I click "Kit Item 2 Toggler"
    Then records in "Kit Item 2 Products Grid" should be 4

  Scenario: Check validations
    When I click "Edit"
    And I click "Kit Item 2 Toggler"
    And I fill "ProductKitForm" with:
      | Kit Item 2 Label |  |
    And I click "Remove" on row "PSKU2" in grid "Kit Item 2 Products Edit Grid"
    And I click "Yes, Delete"
    And I click "Remove" on row "PSKU3" in grid "Kit Item 2 Products Edit Grid"
    And I click "Yes, Delete"
    And I click "Remove" on row "PSKU4" in grid "Kit Item 2 Products Edit Grid"
    And I click "Yes, Delete"
    And I click "Remove" on row "PSKU5" in grid "Kit Item 2 Products Edit Grid"
    And I click "Yes, Delete"
    And I click "Kit Item 2 Toggler"
    And I save and close form
    Then I should see "ProductKitForm" validation errors:
      | Kit Item 2 Label    | This value should not be blank.                             |
      | Kit Item 2 Products | Each kit option should have at least one product specified. |
    When I fill "ProductKitForm" with:
      | Kit Item 2 Label | Kit Item 1 |
    And I click "Add Product" in "Product Kit Item 2" element
    And I click on PSKU4 in grid "KitItemProductsAddGrid"
    And I click "Add Product" in "Product Kit Item 2" element
    And I click on NA01 in grid "KitItemProductsAddGrid"
    And I save and close form
    Then I should see "ProductKitForm" validation errors:
      | Kit Item 2 Product Unit | Unit of quantity should be available for all specified products. |
    When I click "Remove" on row "NA01" in grid "Kit Item 2 Products Edit Grid"
    And I click "Yes, Delete"
    And I save and close form
    Then I should see "Product has been saved" flash message
    When I click "Kit Item 1 Toggler"
    Then records in "Kit Item 1 Products Grid" should be 1
    When I click "Kit Item 2 Toggler"
    Then records in "Kit Item 2 Products Grid" should be 1

  Scenario: Add more kit items and edit exist
    When I click "Edit"
    And I click "Add Kit Item"
    And I fill "ProductKitForm" with:
      | Kit Item 3 Label            | Kit Item 3 |
      | Kit Item 3 Sort Order       | 3          |
      | Kit Item 3 Minimum Quantity | 3          |
      | Kit Item 3 Maximum Quantity | 6          |
    And I click "Kit Item 3 Toggler"
    Then I should see "Minimum Quantity 3"
    And I should see "Maximum Quantity 6"
    When I click "Kit Item 3 Toggler"
    And I fill "ProductKitForm" with:
      | Kit Item 3 Minimum Quantity | string |
      | Kit Item 3 Maximum Quantity | string |
    Then I should see "ProductKitForm" validation errors:
      | Kit Item 3 Minimum Quantity | This value should be a valid number. |
      | Kit Item 3 Maximum Quantity | This value should be a valid number. |
    When I click "Kit Item 3 Toggler"
    Then I should see "Minimum Quantity 3"
    And I should see "Maximum Quantity 6"
    When I click "Kit Item 3 Toggler"
    And I fill "ProductKitForm" with:
      | Kit Item 3 Minimum Quantity | 2    |
      | Kit Item 3 Maximum Quantity |      |
      | Kit Item 3 Optional         | true |
    And I click "Kit Item 3 Toggler"
    Then I should see "Minimum Quantity 2"
    And I should see "Maximum Quantity N/A"
    And I should see "Optional Yes"
    When I click "Kit Item 3 Toggler"
    And I click "Add Product" in "Product Kit Item 3" element
    And I click on PSKU8 in grid "KitItemProductsAddGrid"
    And I click "Add Product" in "Product Kit Item 3" element
    And I click on PSKU9 in grid "KitItemProductsAddGrid"
    And I click "Add Product" in "Product Kit Item 3" element
    And I click on PSKU10 in grid "KitItemProductsAddGrid"
    And I click "Add Product" in "Product Kit Item 3" element
    And I click on PSKU11 in grid "KitItemProductsAddGrid"
    And I click "Add Product" in "Product Kit Item 3" element
    And I click on PSKU12 in grid "KitItemProductsAddGrid"
    And I fill "ProductKitForm" with:
      | Kit Item 1 Label            | Kit Item 1 Edited |
      | Kit Item 1 Sort Order       | 1                 |
      | Kit Item 1 Minimum Quantity | 2                 |
      | Kit Item 1 Maximum Quantity | 10                |
    And I click "Add Product" in "Product Kit Item 1" element
    And I click on PSKU1 in grid "KitItemProductsAddGrid"
    And I click "Add Product" in "Product Kit Item 1" element
    And I click on PSKU2 in grid "KitItemProductsAddGrid"
    And I click "Add Product" in "Product Kit Item 1" element
    And I click on PSKU3 in grid "KitItemProductsAddGrid"
    And I click "Add Product" in "Product Kit Item 1" element
    And I click on PSKU4 in grid "KitItemProductsAddGrid"
    And I click "Add Product" in "Product Kit Item 1" element
    And I click on PSKU5 in grid "KitItemProductsAddGrid"
    And I click "Add Product" in "Product Kit Item 1" element
    And I click on PSKU6 in grid "KitItemProductsAddGrid"
    And I click "Kit Item 2 Toggler"
    And I fill "ProductKitForm" with:
      | Kit Item 2 Label            | Kit Item 2 Edited |
      | Kit Item 2 Sort Order       | 2                 |
      | Kit Item 2 Minimum Quantity | 4                 |
      | Kit Item 2 Maximum Quantity | 6                 |
    And I click "Add Product" in "Product Kit Item 2" element
    And I click on PSKU12 in grid "KitItemProductsAddGrid"
    And I click "Add Product" in "Product Kit Item 2" element
    And I click on PSKU13 in grid "KitItemProductsAddGrid"
    And I click "Add Product" in "Product Kit Item 2" element
    And I click on PSKU14 in grid "KitItemProductsAddGrid"
    And I click "Add Product" in "Product Kit Item 2" element
    And I click on PSKU15 in grid "KitItemProductsAddGrid"
    And I save form
    Then I should see "ProductKitForm" validation errors:
      | Kit Item 3 Products | Only simple product can be used in kit options. |
    When I click "Kit Item 2 Toggler"
    Then "ProductKitForm" must contain values:
      | Kit Item 1 Sort Order | 1 |
      | Kit Item 2 Sort Order | 2 |
      | Kit Item 3 Sort Order | 3 |
    When I click "Remove" on row "PSKU9" in grid "Kit Item 3 Products Edit Grid"
    And I click "Yes, Delete"
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check Product Kit on view page
    Then I should see "Kit Item 1 Edited" in the "Product Kit Item 1" element
    And I should see "Kit Item 2 Edited" in the "Product Kit Item 2" element
    And I should see "Kit Item 3" in the "Product Kit Item 3" element
    And I should see "PSKU7 - Product 7 PSKU1 - Product 1 PSKU2 - Product 2 ..." in the "Product Kit Item 1" element
    And I should see "PSKU4 - Product 4 PSKU12 - Product 12 PSKU13 - Product 13 ..." in the "Product Kit Item 2" element
    And I should see "PSKU8 - Product 8 PSKU10 - Product 10 PSKU11 - Product 11 ..." in the "Product Kit Item 3" element
    When I click "Kit Item 1 Toggler"
    Then records in "Kit Item 1 Products Grid" should be 7
    When I click "Kit Item 2 Toggler"
    Then records in "Kit Item 2 Products Grid" should be 5
    When I click "Kit Item 3 Toggler"
    Then records in "Kit Item 3 Products Grid" should be 4

  Scenario: User change unit for existing item
    When I click "Edit"
    And I fill "ProductKitForm" with:
      | Kit Item 1 Product Unit | kg |
    And I save form
    Then I should see "ProductKitForm" validation errors:
      | Kit Item 1 Product Unit | Unit of quantity should be available for all specified products. |
    When I fill "ProductKitForm" with:
      | Kit Item 1 Product Unit | each |
    And I save form
    Then I should see "Product has been saved" flash message

  Scenario: Drag and Drop sorting product kit items
    When I click "Kit Item 1 Toggler"
    And I click "Kit Item 1 Toggler"
    And I drag and drop "Draggable Product5 Row" before "Draggable Product1 Row"
    And I drag and drop "Draggable Product3 Row" before "Draggable Product5 Row"
    Then I should see following grid:
      | SKU   | NAME      |
      | PSKU7 | Product 7 |
      | PSKU3 | Product 3 |
      | PSKU5 | Product 5 |
      | PSKU1 | Product 1 |
      | PSKU2 | Product 2 |
      | PSKU4 | Product 4 |
      | PSKU6 | Product 6 |
    When I drag and drop "Product Kit Item Sortable 2" before "Product Kit Item Sortable 1"
    And I drag and drop "Product Kit Item Sortable 3" before "Product Kit Item Sortable 1"
    And I save and close form
    Then I should see "Kit Item 2 Edited" in the "Product Kit Item 1" element
    And I should see "Kit Item 3" in the "Product Kit Item 2" element
    And I should see "Kit Item 1 Edited" in the "Product Kit Item 3" element

  Scenario: Remove all kits
    When I click "Edit"
    And I click "Kit Item 3 Remove Button"
    And I click "Kit Item 2 Remove Button"
    And I click "Kit Item 1 Remove Button"
    And I save form
    Then I should see "ProductKitForm" validation errors:
      | Kit Items | Product kit should have at least one kit item fully specified. |
    When I fill "ProductKitForm" with:
      | Kit Item 1 Label | Kit Item 1 |
    And I click "Add Product" in "Product Kit Item 1" element
    And I click on PSKU7 in grid "KitItemProductsAddGrid"
    And I save form
    Then I should see "Product has been saved" flash message

  Scenario: Check prevents removal of simple products that relate to kit products
    Given I go to Products/Products
    When I click Delete PSKU7 in grid
    Then I should see "Are you sure you want to delete this Product?"
    And I click "Yes, Delete"
    Then I should see "You do not have permission to perform this action." flash message
    When I click View PSKU7 in grid
    Then I should not see "Delete"
