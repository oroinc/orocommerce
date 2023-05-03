@skip
@fixture-OroProductBundle:products_grid.yml

Feature: Product kits management UI
  In order to manage product kits
  As an Administrator
  I want to have ability of adding and edit Product Kits

  Scenario: Logged in as buyer and manager on different window sessions
    Given sessions active:
      | Admin | first_session |

  Scenario: Create Product Kit product type
    Given I proceed as the Admin
    And I login as administrator
    And go to Products/ Products
    When click "Create Product"
    And fill "ProductForm Step One" with:
      | Type | Kit |
    Then I click "Continue"
    Given fill "Create Product Form" with:
      | SKU    | Product-with-kit |
      | Name   | Product with Kit |
      | Status | Enable           |
    And I click "Add More Product Kit"
    And I click "Add Product" in "Product Kit Item 1" element
    And I click on PSKU2 in grid "KitItemProductsAddGrid"
    And I click "Add Product" in "Product Kit Item 1" element
    And I click on PSKU3 in grid "KitItemProductsAddGrid"
    And I click "Add Product" in "Product Kit Item 1" element
    And I click on PSKU4 in grid "KitItemProductsAddGrid"
    And I click "Add Product" in "Product Kit Item 1" element
    And I click on PSKU5 in grid "KitItemProductsAddGrid"
    And I should not see "Kit Item 1"
    And I fill "ProductKitForm" with:
      | Label1     | Kit Item 1 |
      | sortOrder1 | 2          |
    And I should see "Kit Item 1"
    And I save and close form
    Then I should see validation errors:
      | Label2           | This value should not be blank.                             |
      | kitItemProducts2 | Each kit option should have at least one product specified. |
    And I fill "ProductKitForm" with:
      | Label2 | Kit Item 2 |
    And I click "Add Product" in "Product Kit Item 2" element
    And I click on PSKU7 in grid "KitItemProductsAddGrid"
    And I save and close form
    Then I should see "Product has been saved" flash message
    And I click "Product Kit 2 Toggler"
    And records in "KitItemProductsViewGrid 2" should be 4

  Scenario: Check validations
    Given I click "Edit"
    And I fill "ProductKitForm" with:
      | Label2 |  |
    And I click "Remove" on row "PSKU2" in grid "KitItemProductsEditGrid 2"
    And I click "Yes, Delete"
    And I click "Remove" on row "PSKU3" in grid "KitItemProductsEditGrid 2"
    And I click "Yes, Delete"
    And I click "Remove" on row "PSKU4" in grid "KitItemProductsEditGrid 2"
    And I click "Yes, Delete"
    And I click "Remove" on row "PSKU5" in grid "KitItemProductsEditGrid 2"
    And I click "Yes, Delete"
    And I click "Product Kit 2 Toggler"
    And I save and close form
    Then I should see validation errors:
      | Label2           | This value should not be blank.                             |
      | kitItemProducts2 | Each kit option should have at least one product specified. |
    And I fill "ProductKitForm" with:
      | Label2 | Kit Item 1 |
    And I click "Add Product" in "Product Kit Item 2" element
    And I click on PSKU4 in grid "KitItemProductsAddGrid"
    And I save and close form

  Scenario: Add more kit items and edit exist
    Given I click "Edit"
    And I click "Add More Product Kit"
    Then I fill "ProductKitForm" with:
      | Label3           | Kit Item 3 |
      | sortOrder3       | 3          |
      | minimumQuantity3 | 3          |
      | maximumQuantity3 | 6          |
    And I click "Product Kit 3 Toggler"
    And I should see "Minimum Quantity 3"
    And I should see "Maximum Quantity 6"
    And I click "Product Kit 3 Toggler"
    Then I fill "ProductKitForm" with:
      | minimumQuantity3 | string |
      | maximumQuantity3 | string |
    Then I should see validation errors:
      | minimumQuantity3 | This value should be a valid number. |
      | maximumQuantity3 | This value should be a valid number. |
    And I click "Product Kit 3 Toggler"
    And I should see "Minimum Quantity 3"
    And I should see "Maximum Quantity 6"
    And I click "Product Kit 3 Toggler"
    Then I fill "ProductKitForm" with:
      | minimumQuantity3 | 2    |
      | maximumQuantity3 |      |
      | Optional3        | true |
    And I click "Product Kit 3 Toggler"
    And I should see "Minimum Quantity 2"
    And I should see "Maximum Quantity N/A"
    And I should see "Optional Yes"
    Then I click "Product Kit 3 Toggler"
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
    Then I fill "ProductKitForm" with:
      | Label1           | Kit Item 1 Edited |
      | sortOrder1       | 1                 |
      | minimumQuantity1 | 2                 |
      | maximumQuantity1 | 10                |
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
    Then I click "Product Kit 2 Toggler"
    Then I fill "ProductKitForm" with:
      | Label2           | Kit Item 2 Edited |
      | sortOrder2       | 2                 |
      | minimumQuantity2 | 4                 |
      | maximumQuantity2 | 6                 |
    And I click "Add Product" in "Product Kit Item 2" element
    And I click on PSKU12 in grid "KitItemProductsAddGrid"
    And I click "Add Product" in "Product Kit Item 2" element
    And I click on PSKU13 in grid "KitItemProductsAddGrid"
    And I click "Add Product" in "Product Kit Item 2" element
    And I click on PSKU14 in grid "KitItemProductsAddGrid"
    And I click "Add Product" in "Product Kit Item 2" element
    And I click on PSKU15 in grid "KitItemProductsAddGrid"
    And I save form
    Then I should see validation errors:
      | kitItemProducts3 | Only simple product can be used in kit options. |
    And I click "Remove" on row "PSKU9" in grid "KitItemProductsEditGrid 3"
    And I click "Yes, Delete"
    And I save and close form

  Scenario: Check Product Kit on view page
    Then I should see "Kit Item 1 Edited" in the "Product Kit Item 1" element
    And I should see "Kit Item 2 Edited" in the "Product Kit Item 2" element
    And I should see "Kit Item 3" in the "Product Kit Item 3" element
    And I should see "PSKU7 - Product 7 PSKU1 - Product 1 PSKU2 - Product 2 ..." in the "Product Kit Item 1" element
    And I should see "PSKU4 - Product 4 PSKU12 - Product 12 PSKU13 - Product 13 ..." in the "Product Kit Item 2" element
    And I should see "PSKU8 - Product 8 PSKU10 - Product 10 PSKU11 - Product 11 ..." in the "Product Kit Item 3" element
    Then I click "Product Kit 1 Toggler"
    And records in "KitItemProductsViewGrid 1" should be 7
    Then I click "Product Kit 2 Toggler"
    And records in "KitItemProductsViewGrid 2" should be 5
    Then I click "Product Kit 3 Toggler"
    And records in "KitItemProductsViewGrid 3" should be 4

  Scenario: User change unit for existing item
    When I click "Edit"
    Then I fill "ProductKitForm" with:
      | productUnit1 | kg |
    And I save form
    Then I should see validation errors:
      | productUnit1 | Unit of quantity should be available for all specified products. |
    Then I fill "ProductKitForm" with:
      | productUnit1 | each |
    And I save form
    Then I should see "Product has been saved" flash message

  Scenario: Remove all kits
    When I click "Product Kit 3 Remove Button"
    And I click "Product Kit 2 Remove Button"
    And I click "Product Kit 1 Remove Button"
    And I save form
    Then I should see validation errors:
      | productKits | Product kit should have at least one kit item fully specified. |
    And I fill "ProductKitForm" with:
      | Label1 | Kit Item 1 |
    And I click "Add Product" in "Product Kit Item 1" element
    And I click on PSKU2 in grid "KitItemProductsAddGrid"
    And I save form
    Then I should see "Product has been saved" flash message
