@regression
@fixture-OroShoppingListBundle:product_kits_on_shopping_list_page.yml
@fixture-OroShoppingListBundle:products_with_different_names.yml

Feature: Shopping list Save for later in Storefront filters and sorters

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

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
      | simple-product-04 | Red   | M    |
      | simple-product-05 | Green | L    |
      | simple-product-06 | Blue  | S    |

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
      | MainSKU                 | SKU1              | SKU2              | SKU3              |
      | configurable-product-01 | simple-product-04 | simple-product-05 | simple-product-06 |

  Scenario: Enable Save For Later Feature
    When I go to System/Configuration
    And I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And I fill "Shopping List Configuration Form" with:
      | Enable Save For Later Use default | false |
      | Enable Save For Later             | true  |
    And I save setting
    Then I should see "Configuration saved" flash message

  Scenario: Add simple products to Shopping List
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When type "Handheld Flashlight" in "search"
    And I click "Search Button"
    And I click "Add to Product Kit Shopping List" for "psku1" product
    Then I should see "Product has been added to " flash message and I close it
    When I click "Add to Product Kit Shopping List" for "psku2" product
    Then I should see "Product has been added to " flash message and I close it

  Scenario: Check Shopping List Saved For Later in the Storefront edit page and add items to Saved for later list
    When I open page with shopping list Product Kit Shopping List
    Then I should see "Summary 9 Items"
    And I should see "Subtotal $728.62"
    And I should see "Discount -$354.31"
    And I should see "Total: $374.31"
    And I should see no records in "Frontend Shopping List Saved For Later Line Items Grid" table

    When I check all visible on page in "Frontend Shopping List Edit Grid"
    And I click "Save For Later" link from mass action dropdown in "Frontend Shopping List Edit Grid"
    Then should see "Save Products For Later Selected products will be saved for later." in confirmation dialogue
    When I click "Yes, Save"
    Then I should see "9 product(s) have been saved for later successfully." flash message and I close it
    And I should see "Summary No Items"
    And I should see "Total: $0.00"
    And I should see no records in "Frontend Shopping List Edit Grid" table

  Scenario: Check sorting in grid
    When sort "Frontend Shopping List Saved For Later Line Items Grid" by "SKU"
    Then I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU               | Product                                       | Availability | Qty Update All | Price     | Subtotal |
      | product-kit-01    | Product Kit 1 Product Kit 1 Line Item 1 Notes | In Stock     | 2 piece        | $124.6845 | $249.37  |
      | simple-product-03 | Barcode Scanner: Product 3                    |              | 2 pieces       | $37.0368  |          |
      | simple-product-02 | Base Unit: Product 2                          |              | 2 pieces       | $24.6912  |          |

      | product-kit-01    | Product Kit 1 Product Kit 1 Line Item 2 Notes | In Stock     | 1 piece        | $13.5845  | $13.58   |
      | simple-product-01 | Base Unit: Product 1                          |              | 1 piece        | $12.3456  |          |

      | product-kit-01    | Product Kit 1                                 | In Stock     | 1 piece        | $25.9245  | $25.92   |
      | simple-product-02 | Base Unit: Product 2                          |              | 1 piece        | $24.6912  |          |

      | psku1             | Industrial Steel Handheld Flashlight          | In Stock     | 1 each         | $10.00    | $10.00   |
      | psku2             | Handheld Flashlight                           | In Stock     | 1 each         | $10.00    | $10.00   |
      | simple-product-02 | Product 2                                     | In Stock     | 1 piece        | $24.6912  | $24.69   |
      | simple-product-04 | Configurable Product 1 Red M                  | In Stock     | 1 piece        | $49.3824  | $49.38   |
      | simple-product-05 | Configurable Product 1 Green L                | Out of Stock | 2 piece        | $61.728   | $123.46  |
      | simple-product-06 | Configurable Product 1 Blue S                 | Out of Stock | 3 piece        | $74.0736  | $222.22  |

    When I sort "Frontend Shopping List Saved For Later Line Items Grid" by "SKU" again
    Then I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU               | Product                                       | Availability | Qty Update All | Price     | Subtotal |
      | simple-product-06 | Configurable Product 1 Blue S                 | Out of Stock | 3 piece        | $74.0736  | $222.22  |
      | simple-product-05 | Configurable Product 1 Green L                | Out of Stock | 2 piece        | $61.728   | $123.46  |
      | simple-product-04 | Configurable Product 1 Red M                  | In Stock     | 1 piece        | $49.3824  | $49.38   |
      | simple-product-02 | Product 2                                     | In Stock     | 1 piece        | $24.6912  | $24.69   |
      | psku2             | Handheld Flashlight                           | In Stock     | 1 each         | $10.00    | $10.00   |
      | psku1             | Industrial Steel Handheld Flashlight          | In Stock     | 1 each         | $10.00    | $10.00   |

      | product-kit-01    | Product Kit 1                                 | In Stock     | 1 piece        | $25.9245  | $25.92   |
      | simple-product-02 | Base Unit: Product 2                          |              | 1 piece        | $24.6912  |          |

      | product-kit-01    | Product Kit 1 Product Kit 1 Line Item 2 Notes | In Stock     | 1 piece        | $13.5845  | $13.58   |
      | simple-product-01 | Base Unit: Product 1                          |              | 1 piece        | $12.3456  |          |

      | product-kit-01    | Product Kit 1 Product Kit 1 Line Item 1 Notes | In Stock     | 2 piece        | $124.6845 | $249.37  |
      | simple-product-03 | Barcode Scanner: Product 3                    |              | 2 pieces       | $37.0368  |          |
      | simple-product-02 | Base Unit: Product 2                          |              | 2 pieces       | $24.6912  |          |

  Scenario: Check filters in grid
    When I set filter SKU as contains "simple-product-02" in "Frontend Shopping List Saved For Later Line Items Grid" grid
    Then I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU               | Product                                       | Availability | Qty Update All | Price     | Subtotal |

      | simple-product-02 | Product 2                                     | In Stock     | 1 piece        | $24.6912  | $24.69   |

      | product-kit-01    | Product Kit 1                                 | In Stock     | 1 piece        | $25.9245  | $25.92   |
      | simple-product-02 | Base Unit: Product 2                          |              | 1 piece        | $24.6912  |          |

      | product-kit-01    | Product Kit 1 Product Kit 1 Line Item 1 Notes | In Stock     | 2 piece        | $124.6845 | $249.37  |
      | simple-product-03 | Barcode Scanner: Product 3                    |              | 2 pieces       | $37.0368  |          |
      | simple-product-02 | Base Unit: Product 2                          |              | 2 pieces       | $24.6912  |          |
    And I reset "SKU" filter in "Frontend Shopping List Saved For Later Line Items GridFilters" sidebar

    When I check "Out of Stock" in Availability filter in "Frontend Shopping List Saved For Later Line Items Grid"
    Then I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU               | Product                        | Availability | Qty Update All | Price    | Subtotal |
      | simple-product-06 | Configurable Product 1 Blue S  | Out of Stock | 3 piece        | $74.0736 | $222.22  |
      | simple-product-05 | Configurable Product 1 Green L | Out of Stock | 2 piece        | $61.728  | $123.46  |
    And I reset "Frontend Shopping List Saved For Later Line Items Grid" grid

    When  I set filter Quantity as equals "3" in "Frontend Shopping List Saved For Later Line Items Grid"
    Then I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU               | Product                       | Availability | Qty Update All | Price    | Subtotal |
      | simple-product-06 | Configurable Product 1 Blue S | Out of Stock | 3 piece        | $74.0736 | $222.22  |
    And I reset "Quantity" filter in "Frontend Shopping List Saved For Later Line Items GridFilters" sidebar

    When I check "each" in Unit filter in "Frontend Shopping List Saved For Later Line Items Grid"
    Then I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU   | Product                              | Availability | Qty Update All | Price  | Subtotal |
      | psku1 | Industrial Steel Handheld Flashlight | In Stock     | 1 each         | $10.00 | $10.00   |
      | psku2 | Handheld Flashlight                  | In Stock     | 1 each         | $10.00 | $10.00   |

  Scenario: Duplicate Shopping List with Saved For Later with different types of products
    When I click 'Remove From "Saved For Later"' on row "psku2" in grid "Frontend Shopping List Saved For Later Line Items Grid"
    And I click "Yes, Remove"
    Then I should see "The \"Handheld Flashlight\" product was removed from \"Saved For Later\"."
    When I scroll to top
    And I click "Shopping List Actions"
    And I click "Duplicate"
    And I click "Yes, duplicate"
    Then I should see "The shopping list has been duplicated" flash message and I close it
    And I should see "Product Kit Shopping List (copied "
    And I should see following "Frontend Shopping List Edit Grid" grid:
      | SKU   | Product             | Availability | Qty Update All | Price  | Subtotal |
      | psku2 | Handheld Flashlight | In Stock     | 1 each         | $10.00 | $10.00   |
    When sort "Frontend Shopping List Saved For Later Line Items Grid" by "SKU"
    Then I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU               | Product                                       | Availability | Qty Update All | Price     | Subtotal |
      | product-kit-01    | Product Kit 1 Product Kit 1 Line Item 1 Notes | In Stock     | 2 piece        | $124.6845 | $249.37  |
      | simple-product-03 | Barcode Scanner: Product 3                    |              | 2 pieces       | $37.0368  |          |
      | simple-product-02 | Base Unit: Product 2                          |              | 2 pieces       | $24.6912  |          |

      | product-kit-01    | Product Kit 1 Product Kit 1 Line Item 2 Notes | In Stock     | 1 piece        | $13.5845  | $13.58   |
      | simple-product-01 | Base Unit: Product 1                          |              | 1 piece        | $12.3456  |          |

      | product-kit-01    | Product Kit 1                                 | In Stock     | 1 piece        | $25.9245  | $25.92   |
      | simple-product-02 | Base Unit: Product 2                          |              | 1 piece        | $24.6912  |          |

      | psku1             | Industrial Steel Handheld Flashlight          | In Stock     | 1 each         | $10.00    | $10.00   |
      | simple-product-02 | Product 2                                     | In Stock     | 1 piece        | $24.6912  | $24.69   |
      | simple-product-04 | Configurable Product 1 Red M                  | In Stock     | 1 piece        | $49.3824  | $49.38   |
      | simple-product-05 | Configurable Product 1 Green L                | Out of Stock | 2 piece        | $61.728   | $123.46  |
      | simple-product-06 | Configurable Product 1 Blue S                 | Out of Stock | 3 piece        | $74.0736  | $222.22  |
