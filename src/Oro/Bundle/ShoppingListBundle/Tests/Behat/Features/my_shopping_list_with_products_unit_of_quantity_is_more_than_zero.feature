@regression
@ticket-BB-19975
@fixture-OroShoppingListBundle:MyShoppingListsFixture.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml

Feature: My Shopping List with products unit of quantity is more than zero

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    And I login as administrator
    And I go to System / Localization / Translations
    And I filter Key as equal to "oro.frontend.shoppinglist.lineitem.unit.label"
    And I edit "oro.frontend.shoppinglist.lineitem.unit.label" Translated Value as "Unit"

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

  Scenario: Update product family
    Given I go to Products/Product Families
    When I click Edit Attribute Family in grid
    And set Attribute Groups with:
      | Label           | Visible | Attributes    |
      | Attribute group | true    | [Color, Size] |
    And I save form
    Then I should see "Successfully updated" flash message

  Scenario Outline: Prepare simple products
    Given I go to Products/Products
    And I filter SKU as is equal to "<SKU>"
    And I click Edit <SKU> in grid
    When I fill in product attribute "Color" with "<Color>"
    And I fill in product attribute "Size" with "<Size>"
    And I save and close form
    Then I should see "Product has been saved" flash message
    Examples:
      | SKU  | Color | Size |
      | BB04 | Red   | M    |
      | BB05 | Green | L    |
      | BB06 | Blue  | S    |
      | BB07 | Red   | M    |
      | BB08 | Green | L    |
      | BB09 | Blue  | S    |
      | BB10 | Red   | M    |
      | BB11 | Green | L    |
      | BB12 | Blue  | S    |

  Scenario Outline: Prepare configurable products
    Given I go to Products/Products
    And I filter SKU as is equal to "<MainSKU>"
    And I click Edit <MainSKU> in grid
    When I fill "ProductForm" with:
      | Configurable Attributes | [Color, Size] |
    And I check records in grid:
      | <SKU1> |
      | <SKU2> |
      | <SKU3> |
    And I save and close form
    Then I should see "Product has been saved" flash message
    Examples:
      | MainSKU | SKU1 | SKU2 | SKU3 |
      | AA01    | BB04 | BB05 | BB12 |
      | AA02    | BB06 | BB07 | BB11 |
      | AA03    | BB08 | BB09 | BB10 |

  Scenario: Set Precision for product BB04
    When I go to Products/Products
    And I filter SKU as is equal to "BB04"
    And I click Edit BB04 in grid
    And I fill product fields with next data:
      | PrimaryUnit      | item |
      | PrimaryPrecision | 3    |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Set Precision for product BB05
    When I go to Products/Products
    And I filter SKU as is equal to "BB05"
    And I click Edit BB05 in grid
    And I fill product fields with next data:
      | PrimaryUnit      | item |
      | PrimaryPrecision | 5    |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Set Precision for product BB06
    When I go to Products/Products
    And I filter SKU as is equal to "BB06"
    And I click Edit BB06 in grid
    And I fill product fields with next data:
      | PrimaryUnit      | item |
      | PrimaryPrecision | 10   |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Set Precision for product BB12
    When I go to Products/Products
    And I filter SKU as is equal to "BB12"
    And I click Edit BB12 in grid
    And I fill product fields with next data:
      | PrimaryUnit      | item |
      | PrimaryPrecision | 10   |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check the unit of quantity for configurable product at my shopping list edit page and Matrix Form Popup
    Given I operate as the Buyer
    And I login as AmandaRCole@example.org buyer
    And I click "Account Dropdown"
    When I click on "Shopping Lists"
    And I click Edit "Shopping List 3" in grid
    And I click "Group Product Variants"
    When I select 10 from per page list dropdown in "Frontend Customer User Shopping List Edit Grid"
    And I sort grid by "SKU"
    When I click "Configure" on row "Configurable Product 1" in grid
    Then I should see an "Matrix Grid Form" element
    Then I should see next rows in "Matrix Grid Form" table
      | s   | M   | L   |
      | N/A | 3   | N/A |
      | N/A | N/A | 3   |
      | 7   | N/A | N/A |
    And I should see "7" in the "Matrix Grid Column 1 Total Quantity" element
    And I should see "3" in the "Matrix Grid Column 2 Total Quantity" element
    And I should see "3" in the "Matrix Grid Column 3 Total Quantity" element
    And I fill "Matrix Grid Form" with:
      |       | S            | M     | L       |
      | Red   | -            | 4.331 | -       |
      | Green | -            | -     | 5.12345 |
      | BLue  | 7.1234567891 | -     | -       |
    Then I should see next rows in "Matrix Grid Form" table
      | s            | M     | L       |
      | N/A          | 4.331 | N/A     |
      | N/A          | N/A   | 5.12345 |
      | 7.1234567891 | N/A   | N/A     |
    And I should see "7.1234567891" in the "Matrix Grid Column 1 Total Quantity" element
    And I should see "4.331" in the "Matrix Grid Column 2 Total Quantity" element
    And I should see "5.12345" in the "Matrix Grid Column 3 Total Quantity" element
    And I click "Save Changes" in modal window
    And I should see following "Frontend Customer User Shopping List Edit Grid" grid containing rows:
      | SKU  | Product                                     | Availability | Qty Update All      | Price  | Subtotal                                   |
      |      | Configurable Product 1                      |              | 16.5779067891 items |        | $267.83922807465 -$133.91922807465 $133.92 |
      | BB04 | Red M Note 4 text                           | In Stock     | 4.331 item          | $11.00 | $47.64 -$23.8205 $23.82                    |
      | BB05 | Green L Note 5 text                         | Out of Stock | 5.12345 item        | $11.00 | $56.36 -$28.178975 $28.18                  |
      | BB12 | Blue S Note 12 text                         | In Stock     | 7.1234567891 item   | $23.00 | $163.84 -$81.91975307465 $81.92            |
      | BB06 | Configurable Product 2 Blue S Note 6 text   | In Stock     | 3 item              | $11.00 | $33.00 -$16.50 $16.50                      |
      | BB07 | Configurable Product 2 Red M Note 7 text    | Out of Stock | 5 piece             | $17.00 | $85.00                                     |
      | BB11 | Configurable Product 2 Green L Note 11 text | Out of Stock | 7 set               | $19.00 | $133.00                                    |
      |      | Configurable Product 3                      |              | 10 pieces           |        | $170.00                                    |
      | BB08 | Green L Note 8 text                         | In Stock     | 5 piece             | $17.00 | $85.00                                     |
      | BB09 | Blue S Note 9 text                          | Out of Stock | 5 piece             | $17.00 | $85.00                                     |
      | BB10 | Configurable Product 3 Red M Note 10 text   | In Stock     | 7 set               | $19.00 | $133.00                                    |
      | BB13 | Product 13 Note 13 text                     | Out of Stock | 9 item              | $23.00 | $207.00 -$103.50 $103.50                   |
      | BB14 | Product 14 Note 14 text                     | In Stock     | 9 item              | $23.00 | $207.00 -$103.50 $103.50                   |
      | BB15 | Product 15 Note 15 text                     | Out of Stock | 9 item              | $23.00 | $207.00 -$103.50 $103.50                   |
      | BB16 | Product 16 Note 16 text                     | In Stock     | 9 item              | $23.00 | $207.00 -$103.50 $103.50                   |
    And click on "Flash Message Close Button"

  Scenario: Check the unit of quantity for configurable product at my shopping list view page
    And I click "Account Dropdown"
    And I click on "Shopping Lists"
    And I click View "Shopping List 3" in grid
    And I click "Group Product Variants"
    When I select 10 from per page list dropdown in "Frontend Customer User Shopping List View Grid"
    And I sort grid by "SKU"
    Then I should see following "Frontend Customer User Shopping List View Grid" grid:
      | SKU  | Product                                     | Availability | Qty           | Unit   | Price  | Subtotal                                   |
      |      | Configurable Product 1                      |              | 16.5779067891 | items  |        | $267.83922807465 -$133.91922807465 $133.92 |
      | BB04 | Red M Note 4 text                           | In Stock     | 4.331         | items  | $11.00 | $47.64 -$23.8205 $23.82                    |
      | BB05 | Green L Note 5 text                         | Out of Stock | 5.12345       | items  | $11.00 | $56.36 -$28.178975 $28.18                  |
      | BB12 | Blue S Note 12 text                         | In Stock     | 7.1234567891  | items  | $23.00 | $163.84 -$81.91975307465 $81.92            |
      | BB06 | Configurable Product 2 Blue S Note 6 text   | In Stock     | 3             | items  | $11.00 | $33.00 -$16.50 $16.50                      |
      | BB07 | Configurable Product 2 Red M Note 7 text    | Out of Stock | 5             | pieces | $17.00 | $85.00                                     |
      | BB11 | Configurable Product 2 Green L Note 11 text | Out of Stock | 7             | sets   | $19.00 | $133.00                                    |
      |      | Configurable Product 3                      |              | 10            | pieces |        | $170.00                                    |
      | BB08 | Green L Note 8 text                         | In Stock     | 5             | pieces | $17.00 | $85.00                                     |
      | BB09 | Blue S Note 9 text                          | Out of Stock | 5             | pieces | $17.00 | $85.00                                     |
      | BB10 | Configurable Product 3 Red M Note 10 text   | In Stock     | 7             | sets   | $19.00 | $133.00                                    |
      | BB13 | Product 13 Note 13 text                     | Out of Stock | 9             | items  | $23.00 | $207.00 -$103.50 $103.50                   |
      | BB14 | Product 14 Note 14 text                     | In Stock     | 9             | items  | $23.00 | $207.00 -$103.50 $103.50                   |
      | BB15 | Product 15 Note 15 text                     | Out of Stock | 9             | items  | $23.00 | $207.00 -$103.50 $103.50                   |
      | BB16 | Product 16 Note 16 text                     | In Stock     | 9             | items  | $23.00 | $207.00 -$103.50 $103.50                   |

  Scenario: Removing a configurable product when editing it in the dialog
    When I click "Shopping List Actions"
    And I click "Edit"
    And I click "Group Product Variants"
    And I select 10 from per page list dropdown in "Frontend Customer User Shopping List Edit Grid"
    And I sort grid by "SKU"
    And I click "Configure" on row "Configurable Product 1" in grid
    And I click "Remove From Shopping List"
    And I should see "Are you sure you want to delete this product?"
    And click "Yes, Delete"
    Then I should see 'The "Configurable Product 1" product was successfully deleted' flash message
    And I should not see "Configurable Product 1"
    And I should see following "Frontend Customer User Shopping List Edit Grid" grid:
      | SKU  | Product                                     |
      | BB06 | Configurable Product 2 Blue S Note 6 text   |
      | BB07 | Configurable Product 2 Red M Note 7 text    |
      | BB11 | Configurable Product 2 Green L Note 11 text |
      |      | Configurable Product 3                      |
      | BB08 | Green L Note 8 text                         |
      | BB09 | Blue S Note 9 text                          |
      | BB10 | Configurable Product 3 Red M Note 10 text   |
      | BB13 | Product 13 Note 13 text                     |
      | BB14 | Product 14 Note 14 text                     |
      | BB15 | Product 15 Note 15 text                     |
      | BB16 | Product 16 Note 16 text                     |
      | BB17 | Product 17 Note 17 text                     |
