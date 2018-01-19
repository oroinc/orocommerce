# TODO: unskip in BB-13499
@skip
@fixture-OroProductBundle:products_inline_matrix_form.yml
@regression

Feature: Inline matrix for configurable products in product views
  In order to quickly add specific configurations of a complex product to the shopping list
  As a Buyer
  I want to add a complex product to the shopping list via matrix form displayed in the product views

  Scenario: Create sessions
    Given sessions active:
      | User  | first_session  |
      | Admin | second_session |

  Scenario: Prepare product attributes
    Given I proceed as the Admin
    Given I login as administrator

    # Create attribute 1
    And I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | Attribute_1 |
      | Type       | Select      |
    And I click "Continue"
    And set Options with:
      | Label    |
      | Value 11 |
      | Value 12 |
      | Value 13 |
      | Value 14 |
    And I save form
    Then I should see "Attribute was successfully saved" flash message

    # Create attribute 2
    And I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | Attribute_2 |
      | Type       | Select      |
    And I click "Continue"
    And set Options with:
      | Label    |
      | Value 21 |
      | Value 22 |
      | Value 23 |
    And I save form
    Then I should see "Attribute was successfully saved" flash message

    # Create attribute 3
    And I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | Attribute_3 |
      | Type       | Select      |
    And I click "Continue"
    And set Options with:
      | Label    |
      | Value 31 |
      | Value 32 |
    And I save form
    Then I should see "Attribute was successfully saved" flash message

    # Update schema
    And I go to Products / Product Attributes
    And I confirm schema update

    # Update attribute family
    And I go to Products / Product Families
    And I click Edit Attribute Family in grid
    And set Attribute Groups with:
      | Label           | Visible | Attributes |
      | Attribute group | true    | [SKU, Name, Is Featured, New Arrival, Brand, Description, Short Description, Images, Inventory Status, Meta title, Meta description, Meta keywords, Product prices, Attribute_1, Attribute_2, Attribute_3] |
    And I save form
    Then I should see "Successfully updated" flash message

  Scenario: Prepare configurable products

    # Variants for CNF_A
    Given I go to Products / Products
    And filter SKU as is equal to "PROD_A_1"
    And I click Edit PROD_A_1 in grid
    And I fill in product attribute "Attribute_1" with "Value 11"
    And I save form
    Then I should see "Product has been saved" flash message

    And I go to Products / Products
    And filter SKU as is equal to "PROD_A_2"
    And I click Edit PROD_A_2 in grid
    And I fill in product attribute "Attribute_1" with "Value 12"
    And I save form
    Then I should see "Product has been saved" flash message

    And I go to Products / Products
    And filter SKU as is equal to "PROD_A_4"
    And I click Edit PROD_A_4 in grid
    And I fill in product attribute "Attribute_1" with "Value 14"
    And I save form
    Then I should see "Product has been saved" flash message

    # Variants for CNF_B
    And I go to Products / Products
    And filter SKU as is equal to "PROD_B_11"
    And I click Edit PROD_B_11 in grid
    And I fill in product attribute "Attribute_1" with "Value 11"
    And I fill in product attribute "Attribute_2" with "Value 21"
    And I save form
    Then I should see "Product has been saved" flash message

    And I go to Products / Products
    And filter SKU as is equal to "PROD_B_12"
    And I click Edit PROD_B_12 in grid
    And I fill in product attribute "Attribute_1" with "Value 11"
    And I fill in product attribute "Attribute_2" with "Value 22"
    And I save form
    Then I should see "Product has been saved" flash message

    And I go to Products / Products
    And filter SKU as is equal to "PROD_B_21"
    And I click Edit PROD_B_21 in grid
    And I fill in product attribute "Attribute_1" with "Value 12"
    And I fill in product attribute "Attribute_2" with "Value 21"
    And I save form
    Then I should see "Product has been saved" flash message

    And I go to Products / Products
    And filter SKU as is equal to "PROD_B_23"
    And I click Edit PROD_B_23 in grid
    And I fill in product attribute "Attribute_1" with "Value 12"
    And I fill in product attribute "Attribute_2" with "Value 23"
    And I save form
    Then I should see "Product has been saved" flash message

    And I go to Products / Products
    And filter SKU as is equal to "PROD_B_31"
    And I click Edit PROD_B_31 in grid
    And I fill in product attribute "Attribute_1" with "Value 13"
    And I fill in product attribute "Attribute_2" with "Value 21"
    And I save form
    Then I should see "Product has been saved" flash message

    And I go to Products / Products
    And filter SKU as is equal to "PROD_B_32"
    And I click Edit PROD_B_32 in grid
    And I fill in product attribute "Attribute_1" with "Value 13"
    And I fill in product attribute "Attribute_2" with "Value 22"
    And I save form
    Then I should see "Product has been saved" flash message

    And I go to Products / Products
    And filter SKU as is equal to "PROD_B_43"
    And I click Edit PROD_B_43 in grid
    And I fill in product attribute "Attribute_1" with "Value 14"
    And I fill in product attribute "Attribute_2" with "Value 23"
    And I save form
    Then I should see "Product has been saved" flash message

    # Variants for CNF_C
    And I go to Products / Products
    And filter SKU as is equal to "PROD_C_111"
    And I click Edit PROD_C_111 in grid
    And I fill in product attribute "Attribute_1" with "Value 11"
    And I fill in product attribute "Attribute_2" with "Value 21"
    And I fill in product attribute "Attribute_3" with "Value 31"
    And I save form
    Then I should see "Product has been saved" flash message

    And I go to Products / Products
    And filter SKU as is equal to "PROD_C_121"
    And I click Edit PROD_C_121 in grid
    And I fill in product attribute "Attribute_1" with "Value 11"
    And I fill in product attribute "Attribute_2" with "Value 22"
    And I fill in product attribute "Attribute_3" with "Value 31"
    And I save form
    Then I should see "Product has been saved" flash message

    And I go to Products / Products
    And filter SKU as is equal to "PROD_C_231"
    And I click Edit PROD_C_231 in grid
    And I fill in product attribute "Attribute_1" with "Value 12"
    And I fill in product attribute "Attribute_2" with "Value 23"
    And I fill in product attribute "Attribute_3" with "Value 31"
    And I save form
    Then I should see "Product has been saved" flash message

    And I go to Products / Products
    And filter SKU as is equal to "PROD_C_311"
    And I click Edit PROD_C_311 in grid
    And I fill in product attribute "Attribute_1" with "Value 13"
    And I fill in product attribute "Attribute_2" with "Value 21"
    And I fill in product attribute "Attribute_3" with "Value 31"
    And I save form
    Then I should see "Product has been saved" flash message

    And I go to Products / Products
    And filter SKU as is equal to "PROD_C_232"
    And I click Edit PROD_C_232 in grid
    And I fill in product attribute "Attribute_1" with "Value 12"
    And I fill in product attribute "Attribute_2" with "Value 23"
    And I fill in product attribute "Attribute_3" with "Value 32"
    And I save form
    Then I should see "Product has been saved" flash message

  Scenario: Save configurable products with simple products selected
    And I go to Products / Products
    And filter SKU as is equal to "CNF_A"
    And I click Edit CNF_A in grid
    And I should see "No records found"
    And I fill "ProductForm" with:
      | Configurable Attributes | [Attribute_1] |
    And I check PROD_A_1 and PROD_A_2 in grid
    And I check PROD_A_4 record in grid
    And I save form
    Then I should see "Product has been saved" flash message

    And I go to Products / Products
    And filter SKU as is equal to "CNF_B"
    And I click Edit CNF_B in grid
    And I should see "No records found"
    And I fill "ProductForm" with:
      | Configurable Attributes | [Attribute_1, Attribute_2] |
    And I check PROD_B_11 and PROD_B_12 in grid
    And I check PROD_B_21 and PROD_B_23 in grid
    And I check PROD_B_31 and PROD_B_32 in grid
    And I check PROD_B_43 record in grid
    And I save form
    Then I should see "Product has been saved" flash message

    And I go to Products / Products
    And filter SKU as is equal to "CNF_C"
    And I click Edit CNF_C in grid
    And I should see "No records found"
    And I fill "ProductForm" with:
      | Configurable Attributes | [Attribute_1, Attribute_2, Attribute_3] |
    And I check PROD_C_111 and PROD_C_121 in grid
    And I check PROD_C_231 and PROD_C_311 in grid
    And I check PROD_C_232 record in grid
    And I save form
    Then I should see "Product has been saved" flash message

  Scenario: Order with single dimensional inline matrix form
    Given I proceed as the User
    Given I signed in as AmandaRCole@example.org on the store frontend
    And type "Configurable Product A" in "search"
    And click "Search Button"
    And click "View Details" for "Configurable Product A" product
    Then I should see an "Matrix Grid Form" element
    And I fill "Matrix Grid Form" with:
      | Value 11 | 1 |
      | Value 14 | 1 |
    And I click "Add to Shopping List"
    Then I should see "Shopping list \"Shopping list\" was updated successfully"

  Scenario: Order with two dimensional inline matrix form
    Given type "Configurable Product B" in "search"
    And click "Search Button"
    And click "View Details" for "Configurable Product B" product
    Then I should see an "Matrix Grid Form" element
    And I fill "Matrix Grid Form" with:
      | Value 11 | 1 |
      | Value 21 | 1 |
      | Value 12 | 1 |
      | Value 32 | 1 |
      | Value 34 | 1 |
    And I click on "Shopping List Dropdown"
    And I click "Create New Shopping List"
    And I fill in "Shopping List Name" with "Product B Shopping List"
    And I click "Create and Add"
    Then I should see "Shopping list \"Product B Shopping List\" was created successfully"

  Scenario: Update Configurable Product B variants
    Given I fill "Matrix Grid Form" with:
      | Value 32 | 3 |
    And I click on "Shopping List Dropdown"
    And I click "Update Product B Shopping List"
    Then I should see "Shopping list \"Product B Shopping List\" was updated successfully"
    And I click "Product B Shopping List"
    Then I should see "Attribute_1: Value 12 Attribute_2: Value 23"

  Scenario: Check product name in shopping list dropdown in front store
    Given I open shopping list widget
    Then I should see "Configurable Product B" in the "ShoppingListWidget" element
    And I should not see "Product C 232" in the "ShoppingListWidget" element

  Scenario: Order with regular variant selectors
    Given type "Configurable Product C" in "search"
    And click "Search Button"
    And click "View Details" for "Configurable Product C" product
    Then I should see an "Configurable Product Shopping List Form" element
    And I fill in "Attribute_1" with "Value 12"
    And I fill in "Attribute_2" with "Value 23"
    And I fill in "Attribute_3" with "Value 32"
    And I click on "Shopping List Dropdown"
    And I click "Create New Shopping List"
    And I fill in "Shopping List Name" with "Product C Shopping List"
    And I click "Create and Add"
    Then I should see "Shopping list \"Product C Shopping List\" was created successfully"
    And I should see "Product has been added to \"Product C Shopping List\""

    And I click "Product C Shopping List"
    Then I should see "Shopping list 2 Items"
    And I should see "Product B Shopping List 5 Items"
    And I should see "Product C Shopping List 1 Item"

  Scenario: Remove Configurable Product A variants from shopping list
    Given type "Configurable Product A" in "search"
    And click "Search Button"
    And click "View Details" for "Configurable Product A" product
    Then I should see an "Matrix Grid Form" element
    And I click on "Shopping List Dropdown"
    And I click "Remove From Shopping List"
    Then I should see "Product has been removed from \"Shopping list\""
    And I click "Shopping list"
    Then I should not see "Shopping list 2 Items"

  Scenario: Check popup matrix form
    Given I proceed as the Admin
    And I go to System/ Configuration
    And I follow "Commerce/Product/Configurable Products" on configuration sidebar
    And uncheck "Use default" for "Display Matrix Form (where applicable)" field
    And I fill in "Display Matrix Form (where applicable)" with "Popup Matrix Form"
    And I save form
    Given I proceed as the User
    Given type "Configurable Product B" in "search"
    And click "Search Button"
    And click "View Details" for "Configurable Product B" product
    Then I should not see an "Matrix Grid Form" element
    And I press "Add to Shopping List"
    Then I should see an "Matrix Grid Form" element

  Scenario: Check matrix form disabled
    Given I proceed as the Admin
    And I go to System/ Configuration
    And I follow "Commerce/Product/Configurable Products" on configuration sidebar
    And uncheck "Use default" for "Display Matrix Form (where applicable)" field
    And I fill in "Display Matrix Form (where applicable)" with "Do Not Display"
    And I save form
    Given I proceed as the User
    And I reload the page
    And type "Configurable Product B" in "search"
    And click "Search Button"
    And click "View Details" for "Configurable Product B" product
    Then I should not see an "Matrix Grid Form" element
    And I should see an "Configurable Product Shopping List Form" element

  Scenario: Check that configurable product doesn't show on grid in select product type
    Given I proceed as the Admin
    And I go to Sales/ Shopping Lists
    And I click "view" on first row in grid
    And click "Add Line Item"
    Then should see an "Add Line Item Popup" element
    And I open select entity popup for field "Product"
    Then there is no "Configurable Product B" in grid
    Then there is no "Configurable Product A" in grid
