@regression
@fixture-OroShoppingListBundle:configurable_on_shopping_list_page.yml

Feature: Shopping list Save for later in Storefront for configurable products

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Guest | second_session |

  Scenario: Create Color product attribute
    Given I proceed as the Admin
    And I login as administrator
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
      | XS    |
      | S     |
      | M     |
      | L     |
    And I save form
    Then I should see "Attribute was successfully saved" flash message

  Scenario: Update product family
    When I go to Products/Product Families
    And I click Edit Default in grid
    And set Attribute Groups with:
      | Label           | Visible | Attributes    |
      | Attribute group | true    | [Color, Size] |
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
      | SKU               | Color | Size |
      | simple-product-01 | Red   | XS   |
      | simple-product-02 | Red   | M    |
      | simple-product-03 | Green | L    |
      | simple-product-04 | Blue  | S    |

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
      | <SKU4> |
    And I save and close form
    Then I should see "Product has been saved" flash message

    Examples:
      | MainSKU                 | SKU1              | SKU2              | SKU3              | SKU4              |
      | configurable-product-01 | simple-product-01 | simple-product-02 | simple-product-03 | simple-product-04 |

  Scenario: Enable Save For Later Feature
    When I go to System/Configuration
    And I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And I fill "Shopping List Configuration Form" with:
      | Enable Save For Later Use default | false |
      | Enable Save For Later             | true  |
    And I save setting
    Then I should see "Configuration saved" flash message

  Scenario: Check Shopping List Saved For Later in the Storefront edit page
    Given I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list Shopping List
    Then I should see no records in "Frontend Shopping List Saved For Later Line Items Grid" table
    And I should see "Summary 4 Items"
    And I should see "Subtotal $259.26"
    And I should see "Total: $259.26"
    And I should see following "Frontend Shopping List Edit Grid" grid:
      | SKU               | Product                        | Availability | Qty Update All | Price    | Subtotal |
      | simple-product-01 | Configurable Product 1 Red XS  | In Stock     | 1 piece        | $12.3456 | $12.35   |
      | simple-product-02 | Configurable Product 1 Red M   | In Stock     | 1 piece        | $24.6912 | $24.69   |
      | simple-product-03 | Configurable Product 1 Green L | Out of Stock | 2 piece        | $37.0368 | $74.07   |
      | simple-product-04 | Configurable Product 1 Blue S  | Out of Stock | 3 piece        | $49.3824 | $148.15  |

  Scenario: Save products for later moves them from list to Saved For Later section
    When I check all visible on page in "Frontend Shopping List Edit Grid"
    And I click "Save For Later" link from mass action dropdown in "Frontend Shopping List Edit Grid"
    Then should see "Save Products For Later Selected products will be saved for later." in confirmation dialogue
    When I click "Yes, Save" in confirmation dialogue
    Then I should see "4 product(s) have been saved for later successfully." flash message
    And I should see "Summary No Items"
    And I should see "Total: $0.00"
    And I should see no records in "Frontend Shopping List Edit Grid" table
    And I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU               | Product                        | Availability | Qty Update All | Price    | Subtotal |
      | simple-product-01 | Configurable Product 1 Red XS  | In Stock     | 1 piece        | $12.3456 | $12.35   |
      | simple-product-02 | Configurable Product 1 Red M   | In Stock     | 1 piece        | $24.6912 | $24.69   |
      | simple-product-03 | Configurable Product 1 Green L | Out of Stock | 2 piece        | $37.0368 | $74.07   |
      | simple-product-04 | Configurable Product 1 Blue S  | Out of Stock | 3 piece        | $49.3824 | $148.15  |

  Scenario: Delete product from Saved For Later
    When I click "Delete" on row "simple-product-04" in grid "Frontend Shopping List Saved For Later Line Items Grid"
    And I confirm deletion
    Then I should see 'Configurable Product 1" product was successfully deleted' flash message
    And I should see no records in "Frontend Shopping List Edit Grid" table
    And I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU               | Product                        | Availability | Qty Update All | Price    | Subtotal |
      | simple-product-01 | Configurable Product 1 Red XS  | In Stock     | 1 piece        | $12.3456 | $12.35   |
      | simple-product-02 | Configurable Product 1 Red M   | In Stock     | 1 piece        | $24.6912 | $24.69   |
      | simple-product-03 | Configurable Product 1 Green L | Out of Stock | 2 piece        | $37.0368 | $74.07   |

  Scenario: Move item from Saved For Later back to Shopping List
    When I click 'Remove From "Saved For Later"' on row "simple-product-03" in grid "Frontend Shopping List Saved For Later Line Items Grid"
    Then I should see "Are you sure you want to remove this product from \"Saved For Later\"?"
    When I click "Yes, Remove" in confirmation dialogue
    Then I should see following "Frontend Shopping List Edit Grid" grid:
      | SKU               | Product                        | Availability | Qty     | Price    | Subtotal |
      | simple-product-03 | Configurable Product 1 Green L | Out of Stock | 2 piece | $37.0368 | $74.07   |
    And I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU               | Product                       | Availability | Qty Update All | Price    | Subtotal |
      | simple-product-01 | Configurable Product 1 Red XS | In Stock     | 1 piece        | $12.3456 | $12.35   |
      | simple-product-02 | Configurable Product 1 Red M  | In Stock     | 1 piece        | $24.6912 | $24.69   |
    And I should see "Summary 1 Item"
    And I should see "Subtotal $74.07"
    And I should see "Total: $74.07"

  Scenario: Add product notes in Saved For Later section
    When I click 'Add a note' on row "simple-product-02" in grid "Frontend Shopping List Saved For Later Line Items Grid"
    And I fill in "Shopping List Product Note" with "New Note"
    And I click "Add Note" in modal window
    Then I should see "Line item note has been successfully updated" flash message
    And I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU               | Product                               | Availability | Qty Update All | Price    | Subtotal |
      | simple-product-01 | Configurable Product 1 Red XS         | In Stock     | 1 piece        | $12.3456 | $12.35   |
      | simple-product-02 | Configurable Product 1 Red M New Note | In Stock     | 1 piece        | $24.6912 | $24.69   |

  Scenario: Edit quantity of a Saved For Later product
    When I click on the first "Frontend Shopping List Saved For Later Line Items Product Quantity Increment"
    And I click on the first "Frontend Shopping List Saved For Later Line Items Save Changes Button"
    Then I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU               | Product                               | Availability | Qty Update All | Price    | Subtotal |
      | simple-product-01 | Configurable Product 1 Red XS         | In Stock     | 2 piece        | $12.3456 | $24.69   |
      | simple-product-02 | Configurable Product 1 Red M New Note | In Stock     | 1 piece        | $24.6912 | $24.69   |

    When I click on the first "Frontend Shopping List Saved For Later Line Items Product Quantity"
    And I type "1" in "Frontend Shopping List Saved For Later Line Item 1 Product Quantity Input"
    And I click on the first "Frontend Shopping List Saved For Later Line Items Save Changes Button"
    Then I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU               | Product                               | Availability | Qty Update All | Price    | Subtotal |
      | simple-product-01 | Configurable Product 1 Red XS         | In Stock     | 1 piece        | $12.3456 | $12.35   |
      | simple-product-02 | Configurable Product 1 Red M New Note | In Stock     | 1 piece        | $24.6912 | $24.69   |
    And I should see "Summary 1 Item"
    And I should see "Subtotal $74.07"
    And I should see "Total: $74.07"

  Scenario: Configure configurable product in Saved For Later section
    When I click "Group Product Variants"
    And I click "Configure" on row "Configurable Product 1" in grid
    Then I should see an "Matrix Grid Form" element
    When I fill "Matrix Grid Form" with:
      |       | XS | S | M | L |
      | Red   | 1  | - | 1 | - |
      | Green | -  | - | - | 1 |
      | Blue  | -  | 1 | - | - |
    And I click "Save Changes" in modal window
    Then I should see following "Frontend Shopping List Edit Grid" grid:
      | SKU               | Product                        | Availability | Qty     | Price    | Subtotal |
      | simple-product-03 | Configurable Product 1 Green L | Out of Stock | 2 piece | $37.0368 | $74.07   |
    And I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU               | Product                | Availability | Qty Update All | Price    | Subtotal |
      |                   | Configurable Product 1 |              | 4 pieces       |          | $123.46  |
      | simple-product-01 | Red XS                 | In Stock     | 1 piece        | $12.3456 | $12.35   |
      | simple-product-02 | Red M New Note         | In Stock     | 1 piece        | $24.6912 | $24.69   |
      | simple-product-03 | Green L                | Out of Stock | 1 piece        | $37.0368 | $37.04   |
      | simple-product-04 | Blue S                 | Out of Stock | 1 piece        | $49.3824 | $49.38   |
    And I should see "Summary 1 Item"
    And I should see "Subtotal $74.07"
    And I should see "Total: $74.07"

  Scenario: Add product back to Shopping List and save for later again
    When type "Configurable Product 1" in "search"
    And I click "Search Button"
    And click "View Details" for "Configurable Product 1" product
    And I fill "Matrix Grid Form" with:
      |       | XS | S | M | L |
      | Red   | -  | - | 1 | - |
      | Green | -  | - | - | 1 |
      | Blue  | -  | - | - | - |
    And click "Update Shopping List"
    Then I should see 'Shopping list "Shopping list" was updated successfully' flash message and I close it

    When I open page with shopping list Shopping List
    Then I should see following "Frontend Shopping List Edit Grid" grid:
      | SKU               | Product                        | Availability | Qty Update All | Price    | Subtotal |
      | simple-product-03 | Configurable Product 1 Green L | Out of Stock | 1 piece        | $37.0368 | $37.04   |
      | simple-product-02 | Configurable Product 1 Red M   | In Stock     | 1 piece        | $24.6912 | $24.69   |
    And I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU               | Product                               | Availability | Qty     | Price    | Subtotal |
      | simple-product-01 | Configurable Product 1 Red XS         | In Stock     | 1 piece | $12.3456 | $12.35   |
      | simple-product-02 | Configurable Product 1 Red M New Note | In Stock     | 1 piece | $24.6912 | $24.69   |
      | simple-product-03 | Configurable Product 1 Green L        | Out of Stock | 1 piece | $37.0368 | $37.04   |
      | simple-product-04 | Configurable Product 1 Blue S         | Out of Stock | 1 piece | $49.3824 | $49.38   |
    And I should see "Summary 2 Items"
    And I should see "Subtotal $61.73"
    And I should see "Total: $61.73"

    When I click 'Save For Later' on row "simple-product-02" in grid "Frontend Shopping List Edit Grid"
    And I click "Yes, Save" in confirmation dialogue
    And I click 'Save For Later' on row "simple-product-03" in grid "Frontend Shopping List Edit Grid"
    And I click "Yes, Save" in confirmation dialogue
    Then I should see no records in "Frontend Shopping List Edit Grid" table
    And I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU               | Product                               | Availability | Qty Update All | Price    | Subtotal |
      | simple-product-01 | Configurable Product 1 Red XS         | In Stock     | 1 piece        | $12.3456 | $12.35   |
      | simple-product-02 | Configurable Product 1 Red M New Note | In Stock     | 2 piece        | $24.6912 | $49.38   |
      | simple-product-03 | Configurable Product 1 Green L        | Out of Stock | 2 piece        | $37.0368 | $74.07   |
      | simple-product-04 | Configurable Product 1 Blue S         | Out of Stock | 1 piece        | $49.3824 | $49.38   |

  Scenario: Add empty Configurable Product to Shopping List and save for later
    When type "Configurable Product 1" in "search"
    And I click "Search Button"
    And click "View Details" for "Configurable Product 1" product
    And I fill "Matrix Grid Form" with:
      |       | XS | S | M | L |
      | Red   | -  | - | - | - |
      | Green | -  | - | - | - |
      | Blue  | -  | - | - | - |
    And click "Add to Shopping List"
    Then I should see 'Shopping list "Shopping list" was updated successfully' flash message and I close it

    When I open page with shopping list Shopping List
    Then I should see following "Frontend Shopping List Edit Grid" grid:
      | SKU                                                                           | Product                | Qty Update All  |
      | configurable-product-01                                                       | Configurable Product 1 | Select Variants |
      | Please select product variants before placing an order or requesting a quote. |                        |                 |
    And I should see "Summary 1 Item"
    And I should see "Total: $0.00"

    When I click "Select Variants"
    And I fill "Matrix Grid Form" with:
      |       | XS | S | M | L |
      | Red   | -  | - | 1 | - |
      | Green | -  | - | - | - |
      | Blue  | -  | - | - | - |
    And I click "Save Changes"
    Then I should see following "Frontend Shopping List Edit Grid" grid:
      | SKU               | Product                      | Availability | Qty Update All | Price    | Subtotal |
      | simple-product-02 | Configurable Product 1 Red M | In Stock     | 1 piece        | $24.6912 | $24.69   |
    And I should see "Summary 1 Item"
    And I should see "Subtotal $24.69"
    And I should see "Total: $24.69"

    When I click 'Save For Later' on row "simple-product-02" in grid "Frontend Shopping List Edit Grid"
    And I click "Yes, Save" in confirmation dialogue
    Then I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU               | Product                               | Availability | Qty Update All | Price    | Subtotal |
      | simple-product-01 | Configurable Product 1 Red XS         | In Stock     | 1 piece        | $12.3456 | $12.35   |
      | simple-product-02 | Configurable Product 1 Red M New Note | In Stock     | 3 piece        | $24.6912 | $74.07   |
      | simple-product-03 | Configurable Product 1 Green L        | Out of Stock | 2 piece        | $37.0368 | $74.07   |
      | simple-product-04 | Configurable Product 1 Blue S         | Out of Stock | 1 piece        | $49.3824 | $49.38   |
