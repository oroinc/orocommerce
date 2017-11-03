@fixture-OroProductBundle:configurable_products.yml
@regression
@ticket-BB-10500

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
    And I fill form with:
      | Label      | Attribute 1 |
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
    And I fill form with:
      | Label      | Attribute 2 |
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
    And I fill form with:
      | Label      | Attribute 3 |
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
      | Attribute group | true    | [SKU, Name, Is Featured, New Arrival, Brand, Description, Short Description, Images, Inventory Status, Meta title, Meta description, Meta keywords, Product prices, Attribute 1, Attribute 2, Attribute 3] |
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
      | Configurable Attributes | [Attribute 1] |
    And I check PROD_A_1 and PROD_A_2 in grid
    And I check PROD_A_4 record in grid
    And I save form
    Then I should see "Product has been saved" flash message

    And I go to Products / Products
    And filter SKU as is equal to "CNF_B"
    And I click Edit CNF_B in grid
    And I should see "No records found"
    And I fill "ProductForm" with:
      | Configurable Attributes | [Attribute 1, Attribute 2] |
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
      | Configurable Attributes | [Attribute 1, Attribute 2, Attribute 3] |
    And I check PROD_C_111 and PROD_C_121 in grid
    And I check PROD_C_231 and PROD_C_311 in grid
    And I check PROD_C_232 record in grid
    And I save form
    Then I should see "Product has been saved" flash message

  Scenario: Matrix form with single attribute
    Given I proceed as the User
    Given I signed in as AmandaRCole@example.org on the store frontend
    And type "Configurable Product A" in "search"
    And click "Search Button"
    And click "View Details" for "Configurable Product A" product
    Then I should see an "Matrix Grid Form" element
    And I fill "Matrix Grid Form" with:
      | Value 11 | Value 12 | Value 13 | Value 14 |
      | 1        | -        | -        | 1        |
    And I click "Add to Shopping List"
    Then I should see "Shopping list \"Shopping list\" was updated successfully"
    And I click "Shopping list"
    Then I should see an "Matrix Grid Form" element
    And I should see next rows in "Matrix Grid Form" table
      | Value 11 | Value 12 | Value 13 | Value 14 |
      | 1        |          |          | 1        |
    And I fill "Matrix Grid Form" with:
      | Value 11 | Value 12 | Value 13 | Value 14 |
      | -        | 2        | -        |          |
    And I click "Update"
    And I click "Configurable Product A"
    Then I should see an "Matrix Grid Form" element
    And I should see next rows in "Matrix Grid Form" table
      | Value 11 | Value 12 | Value 13 | Value 14 |
      | 1        | 2        |          |          |
    And I click on "Shopping List Dropdown"
    And I click "Remove From Shopping List"
    Then I should see "Product has been removed from \"Shopping list\""
    And I click "Shopping list"
    Then I should see "The Shopping List is empty"

  Scenario: Matrix form with two attributes
    Given type "Configurable Product B" in "search"
    And click "Search Button"
    And click "View Details" for "Configurable Product B" product
    Then I should see an "Matrix Grid Form" element
    And I fill "Matrix Grid Form" with:
      |          | Value 21 | Value 22 | Value 23 |
      | Value 11 | 1        | 1        | -        |
      | Value 12 | 1        | -        | 1        |
      | Value 13 |          |          | -        |
      | Value 14 | -        | -        | 1        |
    And I click "Add to Shopping List"
    Then I should see "Shopping list \"Shopping list\" was updated successfully"
    And I click "Shopping list"
    Then I should see an "Matrix Grid Form" element
    And I should see next rows in "Matrix Grid Form" table
      | Value 21 | Value 22 | Value 23 |
      | 1        | 1        |          |
      | 1        |          | 1        |
      |          |          |          |
      |          |          | 1        |
    And I click "Remove Line Item"
    And I click "Yes, Delete"
    Then I should see "The Shopping List is empty"

  Scenario: Matrix form with tree attributes
    Given type "Configurable Product C" in "search"
    And click "Search Button"
    And click "View Details" for "Configurable Product C" product
    Then I should see an "Configurable Product Shopping List Form" element
    And I fill in "Attribute 1" with "Value 12"
    And I fill in "Attribute 2" with "Value 23"
    And I fill in "Attribute 3" with "Value 32"
    And I click "Add to Shopping List"
    Then I should see "Product has been added to \"Shopping list\""
    And I click "Shopping list"
    #next 6 lines related to @ticket-BB-10500
    And I should see text matching "Attribute 1: Value 12"
    And I should see text matching "Attribute 2: Value 23"
    And I should see text matching "Attribute 3: Value 32"
    And I should not see text matching "Attribute_1"
    And I should not see text matching "Attribute_2"
    And I should not see text matching "Attribute_3"
    And I click "Delete"
    And I click "Yes, Delete"
    Then I should see "You do not have available Shopping Lists"

  Scenario: Disabled matrix form in Shopping List View
    Given I proceed as the Admin
    And I go to System/ Configuration
    And I follow "Commerce/Product/Configurable Products" on configuration sidebar
    And uncheck "Use default" for "Display Options In Shopping Lists" field
    And I fill in "Display Options In Shopping Lists" with "Group Single Products"
    And I save form
    Given I proceed as the User
    And type "Configurable Product B" in "search"
    And click "Search Button"
    And click "View Details" for "Configurable Product B" product
    Then I should see an "Matrix Grid Form" element
    And I fill "Matrix Grid Form" with:
      |          | Value 21 | Value 22 | Value 23 |
      | Value 11 | 1        |          | -        |
      | Value 12 |          | -        | 1        |
      | Value 13 |          |          | -        |
      | Value 14 | -        | -        |          |
    And I click "Add to Shopping List"
    Then I should see "Shopping list \"Shopping list\" was updated successfully"
    And I click "Shopping list"
    Then I should not see an "Matrix Grid Form" element
    And I should see text matching "Attribute 1: Value 11"
    And I should see text matching "Attribute 2: Value 21"
    And I should see text matching "Attribute 1: Value 12"
    And I should see text matching "Attribute 2: Value 23"
    And I click "Delete"
    And I click "Yes, Delete"
    Then I should see "You do not have available Shopping Lists"

  Scenario: Enable popup matrix formin Product View
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

  Scenario: Disabled matrix form in Product View
    Given I proceed as the Admin
    And I go to System/ Configuration
    And I follow "Commerce/Product/Configurable Products" on configuration sidebar
    And uncheck "Use default" for "Display Matrix Form (where applicable)" field
    And I fill in "Display Matrix Form (where applicable)" with "Do Not Display"
    And I save form
    Given I proceed as the User
    And type "Configurable Product B" in "search"
    And click "Search Button"
    And click "View Details" for "Configurable Product B" product
    Then I should not see an "Matrix Grid Form" element
    And I should see an "Configurable Product Shopping List Form" element
