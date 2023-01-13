@regression
@ticket-BB-10500
@fixture-OroShoppingListBundle:MatrixForms.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroCheckoutBundle:CheckoutCustomerFixture.yml
@fixture-OroCheckoutBundle:CheckoutProductWithoutPricesFixture.yml
@fixture-OroCheckoutBundle:CheckoutShoppingListFixture.yml
@fixture-OroCheckoutBundle:CheckoutQuoteFixture.yml

Feature: Matrix forms for configurable products in product list, shopping list, RFQ
  In order to quickly add and update specific configurations of a complex product to the shopping list and RFQ
  As a Buyer
  I want to add a complex product to the shopping list via matrix form displayed in the product views

  Scenario: Create sessions
    Given sessions active:
      | User  | first_session  |
      | Admin | second_session |

  Scenario: Prepare product attributes
    Given I proceed as the Admin
    Given I login as administrator

    # Import attributes
    And I go to Products / Product Attributes
    And I click "Import file"
    And I upload "configurable_products_for_matrix_forms/products_attributes.csv" file to "ShoppingListImportFileField"
    And I click "Import file"
    And I reload the page
    And I confirm schema update

    # Update attribute family
    And I go to Products / Product Families
    And I click Edit Attribute Family in grid
    And set Attribute Groups with:
      | Label           | Visible | Attributes |
      | Attribute group | true    | [Attribute 1, Attribute 2, Attribute 3] |
    And I save form
    Then I should see "Successfully updated" flash message

  Scenario: Prepare products
    And I go to Products / Products
    And I click "Import file"
    And I upload "configurable_products_for_matrix_forms/products.csv" file to "ShoppingListImportFileField"
    And I click "Import file"

  Scenario: Prepare product prices
    And I go to Sales/ Price Lists
    And click view "Default Price List" in grid
    And I click "Import file"
    And I upload "configurable_products_for_matrix_forms/products_prices.csv" file to "ShoppingListImportFileField"
    And I click "Import file"

  Scenario: Update translations
    When I go to System / Localization / Translations
    And I filter Key as equal to "oro.frontend.shoppinglist.lineitem.unit.label"
    And I edit "oro.frontend.shoppinglist.lineitem.unit.label" Translated Value as "Unit"

  Scenario: Check prices container on configurable product view is visible only when there are prices
    Given I proceed as the User
    Given I signed in as AmandaRCole@example.org on the store frontend
    And type "CNFA" in "search"
    And click "Search Button"
    And click "View Details" for "CNFA" product
    Then I should see an "One Dimensional Matrix Grid Form" element
    And I should not see an "Default Page Prices" element
    Then type "CNFB" in "search"
    And click "Search Button"
    And click "View Details" for "CNFB" product
    Then I should see an "Matrix Grid Form" element
    And I should see an "Default Page Prices" element
    And I should see "Item 1 $12.00" in the "Default Page Prices" element

  Scenario: Check clear all button and totals container
    Then type "CNFB" in "search"
    And click "Search Button"
    And click "View Details" for "CNFB" product
    Then I should see an "Matrix Grid Form" element
    And I fill "Matrix Grid Form" with:
      |          | Value 21 | Value 22 | Value 23 |
      | Value 11 | 1        | 1        | -        |
      | Value 12 | 1        | -        | 1        |
      | Value 13 |          |          | -        |
      | Value 14 | -        | -        | 1        |
    Then I should see next rows in "Matrix Grid Form" table
      | Value 21 | Value 22 | Value 23 |
      | 1        | 1        | N/A      |
      | 1        | N/A      | 1        |
      |          |          | N/A      |
      | N/A      | N/A      | 1        |
    And I should see "Clear All Button" element inside "Matrix Grid Form Totals" element
    And I should see "Total QTY 5 | Total $60.00" in the "Matrix Grid Form Totals" element
    And I click "Clear All Product Variants"
    Then I should see next rows in "Matrix Grid Form" table
      | Value 21 | Value 22 | Value 23 |
      |          |          | N/A      |
      |          | N/A      |          |
      |          |          | N/A      |
      | N/A      | N/A      |          |
    And I should see "Total QTY 0 | Total $0.00" in the "Matrix Grid Form Totals" element
    Then I fill "Matrix Grid Form" with:
      |          | Value 21 | Value 22 | Value 23 |
      | Value 11 | 1        | 1        | -        |
      | Value 12 | 1        | -        | 1        |
      | Value 13 |          |          | -        |
      | Value 14 | -        | -        | 1        |
    And I focus on "matrix_collection[rows][0][columns][0][quantity]" field and press Enter key
    And I follow "List 2" link within flash message "Shopping list \"List 2\" was updated successfully"
    Given I open page with shopping list List 2
    And I click "Group Similar"
    And I click Edit ConfigurableProductB in grid
    Then I should see an "Matrix Grid Form" element
    And I should see next rows in "Matrix Grid Form" table
      | Value 21 | Value 22 | Value 23 | Qty | Subtotal |
      | 1        | 1        | N/A      | 2   | $24.00   |
      | 1        | N/A      | 1        | 2   | $24.00   |
      |          |          | N/A      | 0   | $0.00    |
      | N/A      | N/A      | 1        | 1   | $12.00   |
    And I should see an "Clear All Button" element
    And I should see "5" in the "Matrix Grid Total Quantity" element
    And I should see "$60.00" in the "Matrix Grid Total Price" element
    And I click "Clear All Product Variants"
    Then I should see next rows in "Matrix Grid Form" table
      | Value 21 | Value 22 | Value 23 |
      |          |          | N/A      |
      |          | N/A      |          |
      |          |          | N/A      |
      | N/A      | N/A      |          |
    And I should see "0" in the "Matrix Grid Total Quantity" element
    And I should see "$0.00" in the "Matrix Grid Total Price" element
    And I click "Accept" in modal window
    And I should see "ConfigurableProductB" in grid
    And I click Edit ConfigurableProductB in grid
    Then I fill "Matrix Grid Form" with:
      |          | Value 21 | Value 22 | Value 23 |
      | Value 11 | 1        | 1        | -        |
      | Value 12 | 1        | -        | 1        |
      | Value 13 |          |          | -        |
      | Value 14 | -        | -        | 1        |
    And I click "Accept" in modal window

    Then type "CNFB" in "search"
    And click "Search Button"
    Then I should see an "Matrix Grid Form" element
    And I should see next rows in "Matrix Grid Form" table
      | Value 21 | Value 22 | Value 23 |
      | 1        | 1        | N/A      |
      | 1        | N/A      | 1        |
      |          |          | N/A      |
      | N/A      | N/A      | 1        |
    And I should see "Clear All Button" element inside "Matrix Grid Form Totals" element
    And I should see "Total QTY 5 | Total $60.00" in the "Matrix Grid Form Totals" element
    And I click "Clear All Product Variants"
    Then I should see next rows in "Matrix Grid Form" table
      | Value 21 | Value 22 | Value 23 |
      |          |          | N/A      |
      |          | N/A      |          |
      |          |          | N/A      |
      | N/A      | N/A      |          |
    And I should see "Total QTY 0 | Total $0.00" in the "Matrix Grid Form Totals" element
    And I am on the homepage
    When I open shopping list widget
    And I click "View Details"
    And I click "Shopping List Actions"
    And click "Delete"
    And click "Yes, delete"
    And I open shopping list widget
    And I click "View Details"
    And I click "Shopping List Actions"
    And click "Delete"
    And click "Yes, delete"
    Then I should see "There are no shopping lists"

#  TODO: Uncomment after BB-13368 is fixed
#  Scenario: Check product view is working after changing to No Matrix Form for guest user
#    Given I click "Sign Out"
#    And I proceed as the Admin
#    And I go to System/ Configuration
#    And I follow "Commerce/Sales/Shopping List" on configuration sidebar
#    And uncheck "Use default" for "Enable Guest Shopping List" field
#    And check "Enable Guest Shopping List"
#    And I save form
#    And I proceed as the User
#    And type "CNFB" in "search"
#    And click "Search Button"
#    And click "View Details" for "CNFB" product
#    Then I should see an "Matrix Grid Form" element
#    And I proceed as the Admin
#    And I go to System/ Configuration
#    And I follow "Commerce/Product/Configurable Products" on configuration sidebar
#    And uncheck "Use default" for "Product Views" field
#    And I fill in "Product Views" with "No Matrix Form"
#    And I save form
#    And I should see "Configuration saved" flash message
#    And I proceed as the User
#    And I reload the page
#    Then I should not see "Error occurred during layout update. Please contact system administrator."
#    And I should not see an "Configurable Product Shopping List Form" element
#    And I proceed as the Admin
#    And I go to System/ Configuration
#    And I follow "Commerce/Product/Configurable Products" on configuration sidebar
#    And check "Use default" for "Product Views" field
#    And I save form
#    And I should see "Configuration saved" flash message
#    And I proceed as the User

  Scenario: Check related products are clickable on the configurable product page
    Given I proceed as the Admin
    And I go to System/ Configuration
    And I follow "Commerce/Catalog/Related Items" on configuration sidebar
    And I fill "RelatedProductsConfig" with:
      | Minimum Items Use Default | false |
      | Minimum Items             | 1     |
    And I save form
    Then I go to Products / Products
    And filter SKU as is equal to "CNFB"
    And I click Edit CNFB in grid
    And I click "Select related products"
    And I sort "SelectRelatedProductsGrid" by NAME
    And I select following records in "SelectRelatedProductsGrid" grid:
      | SKU123 |
    And I click "Select products"
    And I save and close form
    And I proceed as the User
    And type "CNFB" in "search"
    And click "Search Button"
    And click "View Details" for "CNFB" product
    And I should see "400-Watt Bulb Work Light" in related products
    Then I click "400-Watt Bulb Work Light"
    And I should see "All Products / 400-Watt Bulb Work Light"

  Scenario: Move empty configurable product to another Shopping List
    When I open shopping list widget
    And I click "Create New List"
    And I click "Create"
    Then I should see "Shopping list \"Shopping List\" was created successfully"
    When type "CNFB" in "search"
    And click "Search Button"
    Then I should see an "Matrix Grid Form" element
    When I click on "Shopping List Dropdown"
    And I click "Create New Shopping List" in "ShoppingListButtonGroupMenu" element
    And I fill in "Shopping List Name" with "Source Shopping List"
    And I click "Create and Add"
    Then I should see "Shopping list \"Source Shopping List\" was updated successfully"
    When I follow "Source Shopping List" link within flash message "Shopping list \"Source Shopping List\" was updated successfully"
    Then I should see following grid:
      | SKU  | Item                 | Qty Update All                   | Price | Subtotal |
      | CNFB | ConfigurableProductB | Click "edit" to select variants  |       |          |
    And I click on "First Line Item Row Checkbox"
    And I click "Move to another Shopping List" link from mass action dropdown
    And I click "Filter Toggle" in "UiDialog" element
    And I filter Name as is equal to "Shopping List" in "Shopping List Action Move Grid"
    And I click "Shopping List Action Move Radio"
    And I click "Shopping List Action Submit"
    Then I should see "One entity has been moved successfully" flash message
    And I should see "There are no shopping list line items"
    When I click "Shopping List Actions"
    And I click "Delete"
    And I click "Yes, delete"
    And I open page with shopping list "Shopping List"
    Then I should see following grid:
      | SKU  | Item                 | Qty Update All                   | Price  | Subtotal |
      | CNFB | ConfigurableProductB | Click "edit" to select variants  |        |          |

  Scenario: Order empty matrix form
    When I click Edit CNFB in grid
    Then I should see an "Matrix Grid Form" element
    And I should see next rows in "Matrix Grid Form" table
      | Value 21 | Value 22 | Value 23 |
      |          |          | N/A      |
      |          | N/A      |          |
      |          |          | N/A      |
      | N/A      | N/A      |          |
    And I should see "0" in the "Matrix Grid Total Quantity" element
    And I should see "$0.00" in the "Matrix Grid Total Price" element
    When I fill "Matrix Grid Form" with:
      |          | Value 21 | Value 22 | Value 23 |
      | Value 11 | 1        | 1        | -        |
      | Value 12 | 1        | -        | 1        |
      | Value 13 |          |          | -        |
      | Value 14 | -        | -        | 1        |
    Then I should see "5" in the "Matrix Grid Total Quantity" element
    And I should see "$60.00" in the "Matrix Grid Total Price" element
    And I should see an "Clear All Button" element
    When I click "Clear All Product Variants"
    And I click "Accept" in modal window
    And I click "Create Order"
    Then I should see "This shopping list contains configurable products with no variations. Proceed to checkout without these products?"
    When I click "Proceed"
    Then I should see "Cannot create order because Shopping List has no items" flash message

  Scenario: Check quantity and unit for empty configurable product
    When I follow "Account"
    And I click on "Shopping Lists Navigation Link"
    And I click view Shopping List in grid
    Then I should see following grid:
      | SKU  | Item                 | Qty | Unit                            | Price  | Subtotal |
      | CNFB | ConfigurableProductB |     | Click "edit" to select variants |        |          |

  Scenario: Create request for quote with empty matrix form
    When I click "Shopping List Actions"
    And click "Edit"
    And I click "More Actions"
    And I click "Request Quote"
    Then I should see "Confirmation This shopping list contains configurable products with no variations. Proceed to RFQ without these products?"
    When I click "Proceed"
    Then I should see "Products with no quantities have not been added to this request."
    And I should see "Request A Quote"

  Scenario: Order empty matrix form and a simple product
    Given type "SKU123" in "search"
    And click "Search Button"
    And I click "Add to Shopping List" for "SKU123" product
    And I follow "Shopping List" link within flash message "Product has been added to \"Shopping List\""
    Given I click "Create Order"
    Then I should see "Confirmation This shopping list contains configurable products with no variations. Proceed to checkout without these products?"
    And I click "Proceed"
    Then I should see "Some products have not been added to this order." flash message
    And I should see "Checkout"
    And I should see "400-Watt Bulb Work Light"
    And I should not see "ConfigurableProductB"

  Scenario: Create request for quote with empty configurable product and a simple product
    Given I open shopping list widget
    And I click "View Details"
    And I click "More Actions"
    And I click "Request Quote"
    Then I should see "Confirmation This shopping list contains configurable products with no variations. Proceed to RFQ without these products?"
    And I click "Proceed"
    Then I should see "Request A Quote"
    And I should see "400-Watt Bulb Work Light" in the "RequestAQuoteProducts" element
    And I should not see "ConfigurableProductB" in the "RequestAQuoteProducts" element

  Scenario: Update empty matrix form in the shopping list and create order
    Given I open shopping list widget
    And I click "View Details"
    And I click Edit CNFB in grid
    And I fill "Matrix Grid Form" with:
      |          | Value 21 | Value 22 | Value 23 |
      | Value 11 | 1        | 1        | -        |
      | Value 12 | 1        | -        | 1        |
      | Value 13 |          |          | -        |
      | Value 14 | -        | -        | 1        |
    And I click "Accept"
    Then I should see following grid:
      | SKU       | Item                                                             |          | Qty Update All | Price  | Subtotal |
      | SKU123    | 400-Watt Bulb Work Light                                         | In Stock | 5 item         | $2.00  | $10.00   |
      | PROD_B_11 | ConfigurableProductB Attribute 1: Value 11 Attribute 2: Value 21 | In Stock | 1 item         | $12.00 | $12.00   |
      | PROD_B_12 | ConfigurableProductB Attribute 1: Value 11 Attribute 2: Value 22 | In Stock | 1 item         | $12.00 | $12.00   |
      | PROD_B_21 | ConfigurableProductB Attribute 1: Value 12 Attribute 2: Value 21 | In Stock | 1 item         | $12.00 | $12.00   |
      | PROD_B_23 | ConfigurableProductB Attribute 1: Value 12 Attribute 2: Value 23 | In Stock | 1 item         | $12.00 | $12.00   |
      | PROD_B_43 | ConfigurableProductB Attribute 1: Value 14 Attribute 2: Value 23 | In Stock | 1 item         | $12.00 | $12.00   |
    When I click "Create Order"
    Then I should not see "Confirmation This shopping list contains configurable products with no variations. Proceed to checkout without these products?"
    And I should see "Checkout"
    And I should see "ConfigurableProductB"

  Scenario: Create request for quote with configurable product
    Given I open shopping list widget
    When I click "View Details"
    And I click "More Actions"
    And I click "Request Quote"
    Then I should see "Request A Quote"
    And I should see "400-Watt Bulb Work Light" in the "RequestAQuoteProducts" element
    And I should see "Product B 11" in the "RequestAQuoteProducts" element
    And I should see "Product B 12" in the "RequestAQuoteProducts" element
    And I should see "Product B 21" in the "RequestAQuoteProducts" element
    And I should see "Product B 23" in the "RequestAQuoteProducts" element
    And I should see "Product B 43" in the "RequestAQuoteProducts" element
    And I should not see "ConfigurableProductB" in the "RequestAQuoteProducts" element
    When I open shopping list widget
    And I click "View Details"
    And I click "Group Similar"
    And I click "Remove Line Item"
    And I click "Yes, Delete"
    And I click "Remove Line Item"
    And I click "Yes, Delete"
    Then I should see "There are no shopping list line items"

  Scenario: Empty matrix form disabled
    Given I proceed as the Admin
    And I go to System/ Configuration
    And I follow "Commerce/Product/Configurable Products" on configuration sidebar
    And uncheck "Use default" for "Allow to add empty products" field
    And I uncheck "Allow to add empty products"
    And I save form
    Given I proceed as the User
    And type "CNFB" in "search"
    And click "Search Button"
    Then I should see an "Matrix Grid Form" element
    And I should see next rows in "Matrix Grid Form" table
      | Value 21 | Value 22 | Value 23 |
      |          |          | N/A      |
      |          | N/A      |          |
      |          |          | N/A      |
      | N/A      | N/A      |          |
    And I click "Add to Shopping List" for "CNFB" product
    Then I should see "Please provide at least one value before adding the product to your shopping list"
    Then I fill "Matrix Grid Form" with:
      |          | Value 21 | Value 22 | Value 23 |
      | Value 11 | 1        | 1        | -        |
      | Value 12 | 1        | -        | 1        |
      | Value 13 |          |          | -        |
      | Value 14 | -        | -        | 1        |
    And I click "Add to Shopping List" for "CNFB" product
    And I follow "Shopping List" link within flash message "Shopping list \"Shopping List\" was updated successfully"
    And I click "Group Similar"
    And I click Edit ConfigurableProductB in grid
    Then I should see an "Matrix Grid Form" element
    And I should see next rows in "Matrix Grid Form" table
      | Value 21 | Value 22 | Value 23 |
      | 1        | 1        | N/A      |
      | 1        | N/A      | 1        |
      |          |          | N/A      |
      | N/A      | N/A      | 1        |
    When I click "Accept" in modal window
    And I click "Create Order"
    Then I should not see "Confirmation This shopping list contains configurable products with no variations. Proceed to checkout without these products?"
    And I should see "Checkout"
    And I should see "ConfigurableProductB"
    When I open shopping list widget
    And I click "View Details"
    And I click "Group Similar"
    And I click "Remove Line Item"
    And I click "Yes, Delete"
    Then I should see "There are no shopping list line items"
    Given I proceed as the Admin
    And I go to System/ Configuration
    And I follow "Commerce/Product/Configurable Products" on configuration sidebar
    And check "Use default" for "Allow to add empty products" field
    And I save form

  Scenario: Matrix form with single attribute
    Given I proceed as the User
    And type "ConfigurableProductA" in "search"
    And click "Search Button"
    And I click "Catalog Switcher Toggle"
    And I click "List View"
    Then I should see "One Dimensional Matrix Grid Form" for "CNFA" product
    When I click "Catalog Switcher Toggle"
    And I click "No Image View"
    Then I should see "One Dimensional Matrix Grid Form" for "CNFA" product
    And click "View Details" for "CNFA" product
    Then I should see an "One Dimensional Matrix Grid Form" element
    And I fill "One Dimensional Matrix Grid Form" with:
      | Value 11 | Value 12 | Value 13 | Value 14 |
      | 1        | -        | -        | 1        |
    And I click "Add to Shopping List" in "ShoppingListButtonGroup" element
    And I follow "Shopping List" link within flash message "Shopping list \"Shopping List\" was updated successfully"
    And I click "Group Similar"
    And I click Edit ConfigurableProductA in grid
    Then I should see an "One Dimensional Matrix Grid Form" element
    And I should see next rows in "One Dimensional Matrix Grid Form" table
      | Value 11 | Value 12 | Value 13 | Value 14 |
      | 1        |          | N/A      | 1        |
    And I fill "One Dimensional Matrix Grid Form" with:
      | Value 11 | Value 12 | Value 13 | Value 14 |
      | -        | 2        | -        |          |
    And I click "Accept"
    And I click "ConfigurableProductA"
    Then I should see an "One Dimensional Matrix Grid Form" element
    And I should see next rows in "One Dimensional Matrix Grid Form" table
      | Value 11 | Value 12 | Value 13 | Value 14 |
      | 1        | 2        | N/A      |          |
    And type "CNFA" in "search"
    And click "Search Button"
    And I click "Catalog Switcher Toggle"
    And I click "List View"
    Then I should see an "One Dimensional Matrix Grid Form" element
    And I should see next rows in "One Dimensional Matrix Grid Form" table
      | Value 11 | Value 12 | Value 13 | Value 14 |
      | 1        | 2        | N/A      |          |
    When I click "Catalog Switcher Toggle"
    And I click "No Image View"
    Then I should see an "One Dimensional Matrix Grid Form" element
    And I should see next rows in "One Dimensional Matrix Grid Form" table
      | Value 11 | Value 12 | Value 13 | Value 14 |
      | 1        | 2        | N/A      |          |
    When I click on "Shopping List Dropdown"
    And I click "Remove From Shopping List" in "ShoppingListButtonGroupMenu" element
    And I follow "Shopping List" link within flash message "Product has been removed from \"Shopping List\""
    Then I should see "There are no shopping list line items"

  Scenario: Matrix form with two attributes
    Given type "ConfigurableProductB" in "search"
    And click "Search Button"
    And I click "Catalog Switcher Toggle"
    And I click "List View"
    Then I should see "Matrix Grid Form" for "CNFB" product
    When I click "Catalog Switcher Toggle"
    And I click "No Image View"
    Then I should see "Matrix Grid Form" for "CNFB" product
    And click "View Details" for "CNFB" product
    Then I should see an "Matrix Grid Form" element
    And I fill "Matrix Grid Form" with:
      |          | Value 21 | Value 22 | Value 23 |
      | Value 11 | 1        | 1        | -        |
      | Value 12 | 1        | -        | 1        |
      | Value 13 |          |          | -        |
      | Value 14 | -        | -        | 1        |
    And I click on "Shopping List Dropdown"
    And I click "Add to Shopping List" in "ShoppingListButtonGroupMenu" element
    And I follow "Shopping List" link within flash message "Shopping list \"Shopping List\" was updated successfully"
    And I click "Group Similar"
    And I click Edit ConfigurableProductB in grid
    Then I should see an "Matrix Grid Form" element
    And I should see next rows in "Matrix Grid Form" table
      | Value 21 | Value 22 | Value 23 |
      | 1        | 1        | N/A      |
      | 1        | N/A      | 1        |
      |          |          | N/A      |
      | N/A      | N/A      | 1        |
    When I click "Cancel"
    And I click "Remove Line Item"
    And I click "Yes, Delete"
    Then I should see "There are no shopping list line items"

  Scenario: Matrix form with three attributes
    Given type "ConfigurableProductC" in "search"
    And click "Search Button"
    And I click "Catalog Switcher Toggle"
    And I click "List View"
    Then I should not see "Matrix Grid Form" for "CNFC" product
    When I click "Catalog Switcher Toggle"
    And I click "No Image View"
    Then I should not see "Matrix Grid Form" for "CNFC" product
    And click "View Details" for "CNFC" product
    Then I should see an "Configurable Product Shopping List Form" element
    And I fill in "Attribute 1" with "Value 12"
    And I fill in "Attribute 2" with "Value 23"
    And I fill in "Attribute 3" with "Value 32"
    And I click "Add to Shopping List" in "ShoppingListButtonGroup" element
    And I follow "Shopping List" link within flash message "Product has been added to \"Shopping List\""
    #next 6 lines related to @ticket-BB-10500
    And I should see text matching "Attribute 1: Value 12"
    And I should see text matching "Attribute 2: Value 23"
    And I should see text matching "Attribute 3: Value 32"
    And I should not see text matching "Attribute_1"
    And I should not see text matching "Attribute_2"
    And I should not see text matching "Attribute_3"
    When I click "Shopping List Actions"
    And I click "Delete"
    And I click "Yes, delete"
    Then Page title equals to "Shopping Lists - My Account"
    And I should see "There are no shopping lists"

  Scenario: Disabled matrix form in Product List View
    Given I proceed as the Admin
    And I go to System/ Configuration
    And I follow "Commerce/Product/Configurable Products" on configuration sidebar
    And uncheck "Use default" for "Product Listings" field
    And I fill in "Product Listings" with "No Matrix Form"
    And I save form
    Given I proceed as the User
    And type "ConfigurableProduct" in "search"
    And click "Search Button"
    And I click "Catalog Switcher Toggle"
    And I click "List View"
    Then I should not see "Matrix Grid Form" for "CNFA" product
    And I should not see "Matrix Grid Form" for "CNFB" product
    And I should not see "Matrix Grid Form" for "CNFC" product
    When I click "Catalog Switcher Toggle"
    And I click "No Image View"
    Then I should not see "Matrix Grid Form" for "CNFA" product
    And I should not see "Matrix Grid Form" for "CNFB" product
    And I should not see "Matrix Grid Form" for "CNFC" product
    Then I proceed as the Admin
    And I go to System/ Configuration
    And I follow "Commerce/Product/Configurable Products" on configuration sidebar
    And check "Use default" for "Product Listings" field
    And I save form

  Scenario: Popup matrix form in Product List View and Shopping Lists
    Given I proceed as the Admin
    And I go to System/ Configuration
    And I follow "Commerce/Product/Configurable Products" on configuration sidebar
    And uncheck "Use default" for "Product Listings" field
    And I fill in "Product Listings" with "Popup Matrix Form"
    And I save form
    Given I proceed as the User
    When I click "Account"
    And I click on "Shopping Lists Navigation Link"
    Then I should see "There are no shopping lists"
    When type "CNFB" in "search"
    And click "Search Button"
    When I click "Catalog Switcher Toggle"
    And I click "Gallery View"
    Then I should see "Add to Shopping List" for "CNFB" product
    And I should not see an "Matrix Grid Form" element
    And I click "Add to Shopping List" for "CNFB" product
    Then I should see an "Matrix Grid Form" element
    And I click "Close" in modal window
    And I click "Catalog Switcher Toggle"
    And I click "List View"
    Then I should see "Add to Shopping List" for "CNFB" product
    And I should not see an "Matrix Grid Form" element
    And I click "Add to Shopping List" for "CNFB" product
    Then I should see an "Matrix Grid Form" element
    # Check popup close button and product name in popup title
    And I should see "ConfigurableProductB Item #: CNFB" in the "Matrix Grid Popup" element
    And I click "Close" in modal window
    Then I should not see an "Matrix Grid Popup" element
    # Check opening popup matrix form doesn't create empty shopping list
    And I should see "No Shopping Lists"
    And I click "Add to Shopping List" for "CNFB" product
    And I fill "Matrix Grid Form" with:
      |          | Value 21 | Value 22 | Value 23 |
      | Value 11 | 1        | 1        | -        |
      | Value 12 | 1        | -        | 1        |
      | Value 13 |          |          | -        |
      | Value 14 | -        | -        | 1        |
    And I click "Add to Shopping List" in modal window
    And I follow "Shopping List" link within flash message "Shopping list \"Shopping List\" was updated successfully"
    Then I should not see an "Matrix Grid Form" element
    When I click "Group Similar"
    And I click Edit ConfigurableProductB in grid
    # Check popup close button and product name in popup title
    Then I should see "Edit \"ConfigurableProductB\" in \"Shopping List\"" in the "UiDialog Title" element
    When I click "Cancel" in modal window
    Then I should not see an "Matrix Grid Popup" element
    When I click Edit ConfigurableProductB in grid
    Then I should see next rows in "Matrix Grid Form" table
      | Value 21 | Value 22 | Value 23 |
      | 1        | 1        | N/A      |
      | 1        | N/A      | 1        |
      |          |          | N/A      |
      | N/A      | N/A      | 1        |
    When I fill "Matrix Grid Form" with:
      |          | Value 21 | Value 22 | Value 23 |
      | Value 11 | -        | -        | -        |
      | Value 12 | -        | -        | 3        |
      | Value 13 | -        | -        | -        |
      | Value 14 | -        | -        | -        |
    And I click "Accept" in modal window
    Then I should see "Shopping list \"Shopping List\" was updated successfully"
    When type "CNFB" in "search"
    And click "Search Button"
    Then I should see "Update Shopping List" for "CNFB" product
    When I click "Update Shopping List" in "ShoppingListButtonGroup" element
    Then I should see next rows in "Matrix Grid Form" table
      | Value 21 | Value 22 | Value 23 |
      | 1        | 1        | N/A      |
      | 1        | N/A      | 3        |
      |          |          | N/A      |
      | N/A      | N/A      | 1        |
    When I fill "Matrix Grid Form" with:
      |          | Value 21 | Value 22 | Value 23 |
      | Value 11 | 5        | -        | -        |
      | Value 12 | -        | -        | -        |
      | Value 13 | -        | -        | -        |
      | Value 14 | -        | -        | -        |
    And I click "Update Shopping List" in modal window
    And I follow "Shopping List" link within flash message "Shopping list \"Shopping List\" was updated successfully"
    And I click "Group Similar"
    And I click Edit ConfigurableProductB in grid
    Then I should see next rows in "Matrix Grid Form" table
      | Value 21 | Value 22 | Value 23 |
      | 5        | 1        | N/A      |
      | 1        | N/A      | 3        |
      |          |          | N/A      |
      | N/A      | N/A      | 1        |
    When I click "Cancel"
    And I click "Remove Line Item"
    And I click "Yes, Delete"
    Then I should see "There are no shopping list line items"
    When I click "Shopping List Actions"
    And I click "Delete"
    And I click "Yes, delete"
    Then I should see "There are no shopping lists"
    Then I proceed as the Admin
    And I go to System/ Configuration
    And I follow "Commerce/Product/Configurable Products" on configuration sidebar
    And check "Use default" for "Product Listings" field
    And I save form

  Scenario: Enable popup matrix form in Product View
    Given I proceed as the Admin
    And I go to System/ Configuration
    And I follow "Commerce/Product/Configurable Products" on configuration sidebar
    And uncheck "Use default" for "Product Views" field
    And I fill in "Product Views" with "Popup Matrix Form"
    And I save form
    Given I proceed as the User
    Given type "ConfigurableProductB" in "search"
    And click "Search Button"
    And click "View Details" for "CNFB" product
    Then I should not see an "Matrix Grid Form" element
    And I click "Add to Shopping List" in "Product General Information" element
    Then I should see an "Matrix Grid Form" element
    # Check popup close button and product name in popup title
    And I should see "ConfigurableProductB Item #: CNFB" in the "Matrix Grid Popup" element
    And I click "Close" in modal window
    Then I should not see an "Matrix Grid Popup" element
    # Check opening popup matrix form doesn't create empty shopping list
    When I click "Account"
    And I click on "Shopping Lists Navigation Link"
    Then I should see "There are no shopping lists"
    And I reload the page

  Scenario: Disabled matrix form in Product View
    Given I proceed as the Admin
    And I go to System/ Configuration
    And I follow "Commerce/Product/Configurable Products" on configuration sidebar
    And uncheck "Use default" for "Product Views" field
    And I fill in "Product Views" with "No Matrix Form"
    And I save form
    Given I proceed as the User
    And type "ConfigurableProductB" in "search"
    And click "Search Button"
    And click "View Details" for "CNFB" product
    Then I should not see an "Matrix Grid Form" element
    And I should see an "Configurable Product Shopping List Form" element

# From inline_matrix_for_configurable_products_in_product_views.feature
  Scenario: Order with single dimensional inline matrix form
    Given I proceed as the Admin
    And I go to System/ Configuration
    And I follow "Commerce/Product/Configurable Products" on configuration sidebar
    And check "Use default" for "Product Views" field
    And I save form
    And I proceed as the User
    And type "CNFA" in "search"
    And click "Search Button"
    And click "View Details" for "CNFA" product
    When I should see an "One Dimensional Matrix Grid Form" element
    And I fill "One Dimensional Matrix Grid Form" with:
      | Value 11 | Value 12 | Value 13 | Value 14 |
      | 1        | -        | -        | 1        |
    And I click "Add to Shopping List" in "ShoppingListButtonGroup" element
    Then I should see "Shopping list \"Shopping List\" was updated successfully"

  Scenario: Order with two dimensional inline matrix form
    Given type "CNFB" in "search"
    And click "Search Button"
    And click "View Details" for "CNFB" product
    When I should see an "Matrix Grid Form" element
    And I fill "Matrix Grid Form" with:
      |          | Value 21 | Value 22 | Value 23 |
      | Value 11 | 1        | -        | -        |
      | Value 12 | 1        | -        | -        |
      | Value 13 | -        | 1        | -        |
      | Value 14 | -        | -        | 1        |
    And I click on "Shopping List Dropdown"
    And I click "Create New Shopping List" in "ShoppingListButtonGroupMenu" element
    And I fill in "Shopping List Name" with "Product B Shopping List"
    And I click "Create and Add"
    Then I should see "Shopping list \"Product B Shopping List\" was updated successfully"

  Scenario: Update ConfigurableProductB variants
    Given I click "Clear All Product Variants"
    And I fill "Matrix Grid Form" with:
      |          | Value 21 | Value 22 | Value 23 |
      | Value 11 | 1        | -        | -        |
      | Value 12 | 4        | -        | -        |
      | Value 13 | -        | 3        | -        |
      | Value 14 | -        | -        | 5        |
    And I click on "Shopping List Dropdown"
    And I click "Update Product B Shopping List" in "ShoppingListButtonGroupMenu" element
    And I should see "Shopping list \"Product B Shopping List\" was updated successfully"
    When I click "Product B Shopping List"
    And I should see an "Matrix Grid Form" element
    Then I should see next rows in "Matrix Grid Form" table
      | Value 21 | Value 22 | Value 23 |
      | 1        |          | N/A      |
      | 4        | N/A      |          |
      |          | 3        | N/A      |
      | N/A      | N/A      | 5        |

  Scenario: Check product name in shopping list dropdown in front store
    Given I open shopping list widget
    When I should see "ConfigurableProductB" in the "ShoppingListWidget" element
    Then I should not see "Product C 232" in the "ShoppingListWidget" element

  Scenario: Order with regular variant selectors
    Given type "CNFC" in "search"
    And click "Search Button"
    And click "View Details" for "CNFC" product
    When I should see an "Configurable Product Shopping List Form" element
    And I fill in "Attribute 1" with "Value 12"
    And I fill in "Attribute 2" with "Value 23"
    And I fill in "Attribute 3" with "Value 32"
    And I click on "Shopping List Dropdown"
    And I click "Create New Shopping List"
    And I fill in "Shopping List Name" with "Product C Shopping List"
    And I click "Create and Add"
    And I should see "Product has been added to \"Product C Shopping List\""
    When I click "Account"
    And I click on "Shopping Lists Navigation Link"
    Then should see following grid:
      | Name                    | Items |
      | Product C Shopping List | 1     |
      | Product B Shopping List | 4     |
      | Shopping List           | 2     |
    And records in grid should be 3

  Scenario: Remove Configurable Product A variants from shopping list
    Given type "CNFA" in "search"
    And click "Search Button"
    And click "View Details" for "CNFA" product
    Then I should see an "One Dimensional Matrix Grid Form" element
    And I click on "Shopping List Dropdown"
    And I click "Remove From Shopping List" in "ShoppingListButtonGroupMenu" element
    Then I should see "Product has been removed from \"Shopping List\""
    When I click "Account"
    And I click on "Shopping Lists Navigation Link"
    Then should see following grid:
      | Name                    | Items |
      | Product C Shopping List | 1     |
      | Product B Shopping List | 4     |
      | Shopping List           | 0     |
    And records in grid should be 3

  Scenario: Check popup matrix form
    Given I proceed as the Admin
    And I go to System/ Configuration
    And I follow "Commerce/Product/Configurable Products" on configuration sidebar
    And uncheck "Use default" for "Product Views" field
    And I fill in "Product Views" with "Popup Matrix Form"
    And I save form
    And I proceed as the User
    When type "CNFB" in "search"
    And click "Search Button"
    And click "View Details" for "CNFB" product
    Then I should not see an "Matrix Grid Form" element
    And I click on "Shopping List Dropdown"
    And I click "Add to Product C Shopping List" in "ShoppingListButtonGroupMenu" element
    Then I should see an "Matrix Grid Form" element

  Scenario: Check matrix form disabled
    Given I proceed as the Admin
    And I fill in "Product Views" with "No Matrix Form"
    And I save form
    And I proceed as the User
    And I reload the page
    When type "CNFB" in "search"
    And click "Search Button"
    And click "View Details" for "CNFB" product
    Then I should not see an "Matrix Grid Form" element
    And I should see an "Configurable Product Shopping List Form" element

  Scenario: Check that configurable product doesn't show on grid in select product type
    Given I proceed as the Admin
    And I go to Sales/ Shopping Lists
    And I click "view" on first row in grid
    And click "Add Line Item"
    Then should see an "Add Line Item Popup" element
    And I open select entity popup for field "Product"
    Then there is no "ConfigurableProductB" in grid
    And there is no "ConfigurableProductA" in grid
