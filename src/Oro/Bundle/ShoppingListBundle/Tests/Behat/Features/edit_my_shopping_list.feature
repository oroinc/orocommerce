@regression
@ticket-BB-19141
@fixture-OroShoppingListBundle:MyShoppingListsFixture.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml

Feature: My Shopping List
  In order to allow customers to manage products they want to purchase
  As a Buyer
  I need to be able to manage a shopping list line items

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I enable configuration options:
      | oro_shopping_list.my_shopping_lists_page_enabled         |
      | oro_shopping_list.use_new_layout_for_view_and_edit_pages |
    And I proceed as the Admin
    And I login as administrator
    And I go to System / Localization / Translations
    And I filter Key as equal to "oro.frontend.shoppinglist.lineitem.unit.label"
    And I edit "oro.frontend.shoppinglist.lineitem.unit.label" Translated Value as "Unit"
    And I click "Update Cache"
    Then I should see "Translation Cache has been updated" flash message

  Scenario: Create Color product attribute
    When I go to Products/Product Attributes
    And I click "Create Attribute"
    And fill form with:
      | Field Name | Color  |
      | Type       | Select |
    And click "Continue"
    And set Options with:
      | Label |
      | Red   |
      | Green |
      | Blue  |
    And I save form
    Then I should see "Attribute was successfully saved" flash message

  Scenario: Create Size product attribute
    When I go to Products/Product Attributes
    And I click "Create Attribute"
    And fill form with:
      | Field Name | Size   |
      | Type       | Select |
    And click "Continue"
    And set Options with:
      | Label |
      | S     |
      | M     |
      | L     |
    And I save form
    Then I should see "Attribute was successfully saved" flash message

  Scenario: Update schema
    When I go to Products/Product Attributes
    Then I confirm schema update

  Scenario: Update product family
    When I go to Products/Product Families
    And I click Edit Attribute Family in grid
    And set Attribute Groups with:
      | Label           | Visible | Attributes                                                                                                                                                                       |
      | Attribute group | true    | [SKU, Name, Is Featured, New Arrival, Brand, Description, Short Description, Images, Inventory Status, Meta title, Meta description, Meta keywords, Product prices, Color, Size] |
    And I save form
    Then I should see "Successfully updated" flash message

  Scenario Outline: Prepare simple products
    When I go to Products/Products
    And I filter SKU as is equal to "<SKU>"
    And I click Edit <SKU> in grid
    And I fill in product attribute "Color" with "<Color>"
    And I fill in product attribute "Size" with "<Size>"
    And I save and close form
    Then I should see "Product has been saved" flash message
    Examples:
      | SKU  | Color | Size |
      | BB4  | Red   | M    |
      | BB5  | Green | L    |
      | BB6  | Blue  | S    |
      | BB7  | Red   | M    |
      | BB8  | Green | L    |
      | BB9  | Blue  | S    |
      | BB10 | Red   | M    |
      | BB11 | Green | L    |
      | BB12 | Blue  | S    |
      | BB13 | Green | M    |

  Scenario: Set additional units for product BB6
    When I go to Products/Products
    And I filter SKU as is equal to "BB6"
    And I click Edit BB6 in grid
    And set Additional Unit with:
      | Unit  | Precision | Rate |
      | each  | 1         | 2    |
    And I check "ProductAdditionalSellField" element
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario Outline: Prepare configurable products
    When I go to Products/Products
    And I filter SKU as is equal to "<MainSKU>"
    And I click Edit <MainSKU> in grid
    And I fill "ProductForm" with:
      | Configurable Attributes | [Color, Size] |
    And I check records in grid:
      | <SKU1> |
      | <SKU2> |
      | <SKU3> |
    And I save and close form
    Then I should see "Product has been saved" flash message
    Examples:
      | MainSKU | SKU1 | SKU2 | SKU3 |
      | AA1     | BB4  | BB5  | BB6  |
      | AA3     | BB8  | BB9  | BB10 |

  Scenario: Prepare configurable product for One Dimensional Matrix
    When I go to Products/Products
    And I filter SKU as is equal to "AA2"
    And I click Edit AA2 in grid
    And I fill "ProductForm" with:
      | Configurable Attributes | [Color] |
    And I check records in grid:
      | BB4  |
      | BB13 |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check index page
    When I operate as the Buyer
    And I login as AmandaRCole@example.org buyer
    And I follow "Account"
    And I click "My Shopping Lists"
    Then Page title equals to "My Shopping Lists - My Account"
    And should see following grid:
      | Name            | Subtotal  | Items |
      | Shopping List 3 | $8,818.00 | 32    |
      | Shopping List 1 | $1,581.00 | 3     |

  Scenario: Move empty configurable product to another Shopping List
    When I open shopping list widget
    And I click "Create New List"
    And type "Shopping List 4" in "Shopping List Name"
    And I click "Create"
    Then I should see "Shopping list \"Shopping List 4\" was created successfully"
    When type "AA1" in "search"
    And click "Search Button"
    Then I should see an "Matrix Grid Form" element
    When I click on "Shopping List Dropdown"
    And I click "Create New Shopping List" in "ShoppingListButtonGroupMenu" element
    And I fill in "Shopping List Name" with "Source Shopping List"
    And I click "Create and Add"
    Then I should see "Shopping list \"Source Shopping List\" was updated successfully"
    When I follow "Source Shopping List" link within flash message "Shopping list \"Source Shopping List\" was updated successfully"
    And I click on "First Line Item Row Checkbox"
    And I click "Move to another Shopping List" link from mass action dropdown
    And I click "Filter Toggle" in "UiDialog" element
    And I filter Name as is equal to "Shopping List 4" in "Shopping List Action Move Grid"
    And I click "Shopping List Action Move Radio"
    And I click "Shopping List Action Submit"
    Then I should see "One entity has been moved successfully" flash message
    And I should see "There are no shopping list line items"
    When I open page with shopping list Shopping List 4
    Then I should see following grid:
      | SKU  | Item                   | QtyUpdate All                   | Price  | Subtotal |
      | AA1  | Configurable Product 1 | Click "edit" to select variants |        | N/A      |

  Scenario: Set Default Action
    When I click "Shopping List Actions"
    Then I should see "Set as Default"
    When I click "Set as Default"
    And I click "Yes, set as default"
    Then I should see "Shopping list has been successfully set as default" flash message

  Scenario: Order empty matrix form
    When I click Edit AA1 in grid
    Then I should see an "Matrix Grid Form" element
    And I should see next rows in "Matrix Grid Form" table
      | S   | M   | L   |
      | N/A |     | N/A |
      | N/A | N/A |     |
      |     | N/A | N/A |
    And I should see "0" in the "Matrix Grid Total Quantity" element
    And I should see "$0.00" in the "Matrix Grid Total Price" element
    When I fill "Matrix Grid Form" with:
      |       | S | M | L |
      | Red   | - | 1 | - |
      | Green | - | - | 1 |
      | Blue  | 1 | - | - |
    Then I should see "3" in the "Matrix Grid Total Quantity" element
    And I should see "$33.00" in the "Matrix Grid Total Price" element
    And I should see an "Clear All Button" element
    When I click "Clear All Product Variants"
    And I click "Accept" in modal window
    And I click "Create Order"
    Then I should see "This shopping list contains configurable products with no variations. Proceed to checkout without these products?"
    When I click "Proceed"
    Then I should see "Cannot create order because Shopping List has no items" flash message

  Scenario: Check quantity and unit for empty configurable product
    When I follow "Account"
    And I click "My Shopping Lists"
    And I filter Name as contains "Shopping List 4"
    And I click view Shopping List 4 in grid
    Then I should see following grid:
      | SKU  | Item                   | Qty | Unit                            | Price  | Subtotal |
      | AA1  | Configurable Product 1 |     | Click "edit" to select variants |        | N/A      |

  Scenario: Create request for quote with empty matrix form
    When I click "Shopping List Actions"
    And click "Edit"
    And I click "More Actions"
    And I click "Request Quote"
    Then I should see "Confirmation This shopping list contains configurable products with no variations. Proceed to RFQ without these products?"
    When I click "Proceed"
    Then I should see "Request A Quote"
    And I should see "Products with no quantities have not been added to this request."

  Scenario: Order empty matrix form and a simple product
    When type "CC30" in "search"
    And click "Search Button"
    And I click "Add to Shopping List" for "CC30" product
    And I follow "Shopping List 4" link within flash message "Product has been added to \"Shopping List 4\""
    And I click "Create Order"
    Then I should see "Confirmation This shopping list contains configurable products with no variations. Proceed to checkout without these products?"
    When I click "Proceed"
    Then I should see "Some products have not been added to this order." flash message
    And I should see "Checkout"
    And I should see "Product 30"
    And I should not see "Configurable Product 1"

  Scenario: Create request for quote with empty configurable product and a simple product
    When I open page with shopping list Shopping List 4
    And I click "More Actions"
    And I click "Request Quote"
    Then I should see "Confirmation This shopping list contains configurable products with no variations. Proceed to RFQ without these products?"
    When I click "Proceed"
    Then I should see "Request A Quote"
    And I should see "Product 30" in the "RequestAQuoteProducts" element
    And I should not see "Configurable Product 1" in the "RequestAQuoteProducts" element

  Scenario: Update empty matrix form in the shopping list and create order
    When I open page with shopping list Shopping List 4
    Then I should see "2 total records"
    And I should see following grid:
      | SKU  | Item                   |          | QtyUpdate All                   | Price  | Subtotal |
      | AA1  | Configurable Product 1 |          | Click "edit" to select variants |        | N/A      |
      | CC30 | Product 30             | In Stock | 1 pc                            | $31.00 | $31.00   |
    When I click Edit AA1 in grid
    And I fill "Matrix Grid Form" with:
      |       | S | M | L |
      | Red   | - | 1 | - |
      | Green | - | - | 1 |
      | Blue  | 1 | - | - |
    And I click "Accept"
    Then I should see "4 total records"
    And I should see following grid:
      | SKU  | Item                                        |              | QtyUpdate All | Price  | Subtotal            |
      | BB4  | Configurable Product 1 Color: Red Size: M   | In Stock     | 1 item        | $11.00 | $11.00 -$5.50 $5.50 |
      | BB5  | Configurable Product 1 Color: Green Size: L | Out of Stock | 1 item        | $11.00 | $11.00 -$5.50 $5.50 |
      | BB6  | Configurable Product 1 Color: Blue Size: S  | In Stock     | 1 item        | $11.00 | $11.00 -$5.50 $5.50 |
      | CC30 | Product 30                                  | In Stock     | 1 pc          | $31.00 | $31.00              |
    And I should see "Summary 4 Items"
    And I should see "Subtotal $64.00"
    And I should see "Discount -$16.50"
    And I should see "Total $47.50"
    When I click "Create Order"
    Then I should not see "Confirmation This shopping list contains configurable products with no variations. Proceed to checkout without these products?"
    And I should see "Checkout"
    And I should see "Configurable Product 1"
    And I should see "Product 30"

  Scenario: Create request for quote with configurable product
    When I open page with shopping list Shopping List 4
    And I click "More Actions"
    And I click "Request Quote"
    Then I should see "Request A Quote"
    And I should see "Product 30" in the "RequestAQuoteProducts" element
    And I should not see "Configurable Product 1" in the "RequestAQuoteProducts" element
    And I should see "Product 30" in the "RequestAQuoteProducts" element
    And I should see "Product 4" in the "RequestAQuoteProducts" element
    And I should see "Product 5" in the "RequestAQuoteProducts" element
    And I should see "Product 6" in the "RequestAQuoteProducts" element

  Scenario: Matrix form with single attribute
    When type "AA2" in "search"
    And click "Search Button"
    And I click "List View"
    Then I should see "One Dimensional Matrix Grid Form" for "Configurable Product 2" product
    When I fill "One Dimensional Matrix Grid Form" with:
      | Red | Green | Blue |
      | 1   | 1     | -    |
    And I click on "Shopping List Dropdown"
    And I click "Create New Shopping List" in "ShoppingListButtonGroupMenu" element
    And I fill in "Shopping List Name" with "Shopping List 5"
    And I click "Create and Add"
    Then I should see "Shopping list \"Shopping List 5\" was updated successfully"
    When I follow "Shopping List 5" link within flash message "Shopping list \"Shopping List 5\" was updated successfully"
    Then I should see following grid:
      | SKU  | Item                                |              | QtyUpdate All | Price  | Subtotal              |
      | BB4  | Configurable Product 2 Color: Red   | In Stock     | 1 item        | $11.00 | $11.00 -$5.50 $5.50   |
      | BB13 | Configurable Product 2 Color: Green | Out of Stock | 1 item        | $23.00 | $23.00 -$11.50 $11.50 |
    When I click "Group similar"
    And I click Edit Configurable Product 2 in grid
    Then I should see an "One Dimensional Matrix Grid Form" element
    And I should see next rows in "One Dimensional Matrix Grid Form" table
      | Red | Green | Blue |
      | 1   | 1     | N/A  |
    When I fill "One Dimensional Matrix Grid Form" with:
      | Red | Green | Blue |
      | 2   | 2     | -    |
    And I click "Accept"
    Then I should see following grid:
      | SKU  | Item                   |              | QtyUpdate All | Price  | Subtotal              |
      |      | Configurable Product 2 |              | 4 items       |        | $68.00 -$34.00 $34.00 |
      | BB4  | Color: Red             | In Stock     | 2 item        | $11.00 | $22.00 -$11.00 $11.00 |
      | BB13 | Color: Green           | Out of Stock | 2 item        | $23.00 | $46.00 -$23.00 $23.00 |

  Scenario: Inline edit quantity and unit
    When I open page with shopping list Shopping List 4
    Then I should see following grid:
      | SKU  | Item                                        |              | QtyUpdate All | Price  | Subtotal            |
      | BB4  | Configurable Product 1 Color: Red Size: M   | In Stock     | 1 item        | $11.00 | $11.00 -$5.50 $5.50 |
      | BB5  | Configurable Product 1 Color: Green Size: L | Out of Stock | 1 item        | $11.00 | $11.00 -$5.50 $5.50 |
      | BB6  | Configurable Product 1 Color: Blue Size: S  | In Stock     | 1 item        | $11.00 | $11.00 -$5.50 $5.50 |
      | CC30 | Product 30                                  | In Stock     | 1 pc          | $31.00 | $31.00              |
    When I click "Group similar"
    Then I should see following grid:
      | SKU  | Item                   |              | QtyUpdate All | Price  | Subtotal              |
      |      | Configurable Product 1 |              | 3 items       |        | $33.00 -$16.50 $16.50 |
      | BB4  | Color: Red Size: M     | In Stock     | 1 item        | $11.00 | $11.00 -$5.50 $5.50   |
      | BB5  | Color: Green Size: L   | Out of Stock | 1 item        | $11.00 | $11.00 -$5.50 $5.50   |
      | BB6  | Color: Blue Size: S    | In Stock     | 1 item        | $11.00 | $11.00 -$5.50 $5.50   |
      | CC30 | Product 30             | In Stock     | 1 pc          | $31.00 | $31.00                |
    When I click on "Shopping List Inline Line Item 4 Quantity"
    Then the "Shopping List Inline Line Item 4 Quantity Input" field element should contain "1"
    And the "Shopping List Inline Line Item 4 Unit Select" field element should contain "item"
    When I type "10" in "Shopping List Inline Line Item 4 Quantity Input"
    And I fill "Inline Line Item Edit Form" with:
      | Unit | ea |
    And I click on "Shopping List Inline Line Item 4 Save Changes Button"
    Then I should see following grid:
      | SKU  | Item                                       |              | QtyUpdate All | Price  | Subtotal              |
      | BB6  | Configurable Product 1 Color: Blue Size: S | In Stock     | 10 ea         | N/A    | N/A                   |
      |      | Configurable Product 1                     |              | 2 items       |        | $22.00 -$11.00 $11.00 |
      | BB4  | Color: Red Size: M                         | In Stock     | 1 item        | $11.00 | $11.00 -$5.50 $5.50   |
      | BB5  | Color: Green Size: L                       | Out of Stock | 1 item        | $11.00 | $11.00 -$5.50 $5.50   |
      | CC30 | Product 30                                 | In Stock     | 1 pc          | $31.00 | $31.00                |
    And I should see "Summary 4 Items"
    And I should see "Subtotal $53.00"
    And I should see "Discount -$11.00"
    And I should see "Total $42.00"

  Scenario: Update all
    When I click on "Shopping List Inline Line Item 1 Quantity"
    Then the "Shopping List Inline Line Item 1 Quantity Input" field element should contain "10"
    And the "Shopping List Inline Line Item 1 Unit Select" field element should contain "each"
    When I type "1" in "Shopping List Inline Line Item 1 Quantity Input"
    And I fill "Inline Line Item Edit Form" with:
      | Unit | item |
    And I click on "Shopping List Inline Line Item 4 Quantity"
    And I type "10" in "Shopping List Inline Line Item 4 Quantity Input"
    And I click "Update All"
    Then I should see following grid:
      | SKU  | Item                   |              | QtyUpdate All | Price  | Subtotal               |
      |      | Configurable Product 1 |              | 12 items      |        | $132.00 -$66.00 $66.00 |
      | BB4  | Color: Red Size: M     | In Stock     | 1 item        | $11.00 | $11.00 -$5.50 $5.50    |
      | BB5  | Color: Green Size: L   | Out of Stock | 10 item       | $11.00 | $110.00 -$55.00 $55.00 |
      | BB6  | Color: Blue Size: S    | In Stock     | 1 item        | $11.00 | $11.00 -$5.50 $5.50    |
      | CC30 | Product 30             | In Stock     | 1 pc          | $31.00 | $31.00                 |
    And I should see "Summary 4 Items"
    And I should see "Subtotal $163.00"
    And I should see "Discount -$66.00"
    And I should see "Total $97.00"
