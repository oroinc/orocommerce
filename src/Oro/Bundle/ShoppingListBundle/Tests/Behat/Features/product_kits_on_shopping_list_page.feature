@feature-BB-21126
@fixture-OroShoppingListBundle:product_kits_on_shopping_list_page.yml

Feature: Product kits on shopping list page

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

  Scenario: Check shopping list view page
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When Buyer is on "Product Kit Shopping List" shopping list
    Then I should see following grid:
      | SKU               | Product                                       | Availability | Qty | Unit   | Price     | Subtotal                   |
      | product-kit-01    | Product Kit 1 Product Kit 1 Line Item 1 Notes | In Stock     | 2   | pieces | $124.6845 | $249.37 -$124.6845 $124.69 |
      | simple-product-03 | Barcode Scanner: Product 3                    |              | 2   | pieces | $37.0368  |                            |
      | simple-product-02 | Base Unit: Product 2                          |              | 2   | pieces | $24.6912  |                            |
      | product-kit-01    | Product Kit 1 Product Kit 1 Line Item 2 Notes | In Stock     | 1   | piece  | $13.5845  | $13.58 -$6.79225 $6.79     |
      | simple-product-01 | Base Unit: Product 1                          |              | 1   | piece  | $12.3456  |                            |
      | product-kit-01    | Product Kit 1                                 | In Stock     | 1   | piece  | $25.9245  | $25.92 -$12.96225 $12.96   |
      | simple-product-02 | Base Unit: Product 2                          |              | 1   | piece  | $24.6912  |                            |
      | simple-product-02 | Product 2                                     | In Stock     | 1   | piece  | $24.6912  | $24.69 -$12.3456 $12.34    |
      | simple-product-04 | Configurable Product 1 Red M                  | In Stock     | 1   | piece  | $49.3824  | $49.38 -$24.6912 $24.69    |
      | simple-product-05 | Configurable Product 1 Green L                | Out of Stock | 2   | pieces | $61.728   | $123.46 -$61.728 $61.73    |
      | simple-product-06 | Configurable Product 1 Blue S                 | Out of Stock | 3   | pieces | $74.0736  | $222.22 -$111.1104 $111.11 |
    And I should see "Summary 7 Items"
    And I should see "Subtotal $708.62"
    And I should see "Discount -$354.31"
    And I should see "Total: $354.31"

  Scenario: Check shopping list edit page
    When I click "Shopping List Actions"
    And click "Edit"
    Then I should see following grid:
      | SKU               | Product                                       | Availability | Qty Update All | Price     | Subtotal                   |
      | product-kit-01    | Product Kit 1 Product Kit 1 Line Item 1 Notes | In Stock     | 2 piece        | $124.6845 | $249.37 -$124.6845 $124.69 |
      | simple-product-03 | Barcode Scanner: Product 3                    |              | 2 pieces       | $37.0368  |                            |
      | simple-product-02 | Base Unit: Product 2                          |              | 2 pieces       | $24.6912  |                            |
      | product-kit-01    | Product Kit 1 Product Kit 1 Line Item 2 Notes | In Stock     | 1 piece        | $13.5845  | $13.58 -$6.79225 $6.79     |
      | simple-product-01 | Base Unit: Product 1                          |              | 1 piece        | $12.3456  |                            |
      | product-kit-01    | Product Kit 1                                 | In Stock     | 1 piece        | $25.9245  | $25.92 -$12.96225 $12.96   |
      | simple-product-02 | Base Unit: Product 2                          |              | 1 piece        | $24.6912  |                            |
      | simple-product-02 | Product 2                                     | In Stock     | 1 piece        | $24.6912  | $24.69 -$12.3456 $12.34    |
      | simple-product-04 | Configurable Product 1 Red M                  | In Stock     | 1 piece        | $49.3824  | $49.38 -$24.6912 $24.69    |
      | simple-product-05 | Configurable Product 1 Green L                | Out of Stock | 2 piece        | $61.728   | $123.46 -$61.728 $61.73    |
      | simple-product-06 | Configurable Product 1 Blue S                 | Out of Stock | 3 piece        | $74.0736  | $222.22 -$111.1104 $111.11 |
    And I should see "Summary 7 Items"
    And I should see "Subtotal $708.62"
    And I should see "Discount -$354.31"
    And I should see "Total: $354.31"

  Scenario: Check SKU filter
    When I filter SKU as contains "kit"
    Then I should see following grid:
      | SKU               | Product                                       | Availability | Qty Update All | Price     | Subtotal                   |
      | product-kit-01    | Product Kit 1 Product Kit 1 Line Item 1 Notes | In Stock     | 2 piece        | $124.6845 | $249.37 -$124.6845 $124.69 |
      | simple-product-03 | Barcode Scanner: Product 3                    |              | 2 pieces       | $37.0368  |                            |
      | simple-product-02 | Base Unit: Product 2                          |              | 2 pieces       | $24.6912  |                            |
      | product-kit-01    | Product Kit 1 Product Kit 1 Line Item 2 Notes | In Stock     | 1 piece        | $13.5845  | $13.58 -$6.79225 $6.79     |
      | simple-product-01 | Base Unit: Product 1                          |              | 1 piece        | $12.3456  |                            |
      | product-kit-01    | Product Kit 1                                 | In Stock     | 1 piece        | $25.9245  | $25.92 -$12.96225 $12.96   |
      | simple-product-02 | Base Unit: Product 2                          |              | 1 piece        | $24.6912  |                            |

    When I reset grid
    And I filter SKU as is any of "product-kit-01,simple-product-02"
    Then I should see following grid:
      | SKU               | Product                                       | Availability | Qty Update All | Price     | Subtotal                   |
      | product-kit-01    | Product Kit 1 Product Kit 1 Line Item 1 Notes | In Stock     | 2 piece        | $124.6845 | $249.37 -$124.6845 $124.69 |
      | simple-product-03 | Barcode Scanner: Product 3                    |              | 2 pieces       | $37.0368  |                            |
      | simple-product-02 | Base Unit: Product 2                          |              | 2 pieces       | $24.6912  |                            |
      | product-kit-01    | Product Kit 1 Product Kit 1 Line Item 2 Notes | In Stock     | 1 piece        | $13.5845  | $13.58 -$6.79225 $6.79     |
      | simple-product-01 | Base Unit: Product 1                          |              | 1 piece        | $12.3456  |                            |
      | product-kit-01    | Product Kit 1                                 | In Stock     | 1 piece        | $25.9245  | $25.92 -$12.96225 $12.96   |
      | simple-product-02 | Base Unit: Product 2                          |              | 1 piece        | $24.6912  |                            |
      | simple-product-02 | Product 2                                     | In Stock     | 1 piece        | $24.6912  | $24.69 -$12.3456 $12.34    |

    When I reset grid
    And I filter SKU as is any of "simple-product-01,simple-product-02,simple-product-06"
    Then I should see following grid:
      | SKU               | Product                                       | Availability | Qty Update All | Price     | Subtotal                   |
      | product-kit-01    | Product Kit 1 Product Kit 1 Line Item 1 Notes | In Stock     | 2 piece        | $124.6845 | $249.37 -$124.6845 $124.69 |
      | simple-product-03 | Barcode Scanner: Product 3                    |              | 2 pieces       | $37.0368  |                            |
      | simple-product-02 | Base Unit: Product 2                          |              | 2 pieces       | $24.6912  |                            |
      | product-kit-01    | Product Kit 1 Product Kit 1 Line Item 2 Notes | In Stock     | 1 piece        | $13.5845  | $13.58 -$6.79225 $6.79     |
      | simple-product-01 | Base Unit: Product 1                          |              | 1 piece        | $12.3456  |                            |
      | product-kit-01    | Product Kit 1                                 | In Stock     | 1 piece        | $25.9245  | $25.92 -$12.96225 $12.96   |
      | simple-product-02 | Base Unit: Product 2                          |              | 1 piece        | $24.6912  |                            |
      | simple-product-02 | Product 2                                     | In Stock     | 1 piece        | $24.6912  | $24.69 -$12.3456 $12.34    |
      | simple-product-06 | Configurable Product 1 Blue S                 | Out of Stock | 3 piece        | $74.0736  | $222.22 -$111.1104 $111.11 |

    When I reset grid
    And I filter SKU as does not contain "kit"
    Then I should see following grid:
      | SKU               | Product                        | Availability | Qty Update All | Price    | Subtotal                   |
      | simple-product-02 | Product 2                      | In Stock     | 1 piece        | $24.6912 | $24.69 -$12.3456 $12.34    |
      | simple-product-04 | Configurable Product 1 Red M   | In Stock     | 1 piece        | $49.3824 | $49.38 -$24.6912 $24.69    |
      | simple-product-05 | Configurable Product 1 Green L | Out of Stock | 2 piece        | $61.728  | $123.46 -$61.728 $61.73    |
      | simple-product-06 | Configurable Product 1 Blue S  | Out of Stock | 3 piece        | $74.0736 | $222.22 -$111.1104 $111.11 |

    When I reset grid
    And I filter SKU as is not any of "simple-product-01,simple-product-02,simple-product-03"
    Then I should see following grid:
      | SKU               | Product                        | Availability | Qty Update All | Price    | Subtotal                   |
      | simple-product-04 | Configurable Product 1 Red M   | In Stock     | 1 piece        | $49.3824 | $49.38 -$24.6912 $24.69    |
      | simple-product-05 | Configurable Product 1 Green L | Out of Stock | 2 piece        | $61.728  | $123.46 -$61.728 $61.73    |
      | simple-product-06 | Configurable Product 1 Blue S  | Out of Stock | 3 piece        | $74.0736 | $222.22 -$111.1104 $111.11 |

    When I reset grid
    And I filter SKU as is not any of "product-kit-01,simple-product-03,simple-product-06"
    Then I should see following grid:
      | SKU               | Product                        | Availability | Qty Update All | Price    | Subtotal                |
      | simple-product-02 | Product 2                      | In Stock     | 1 piece        | $24.6912 | $24.69 -$12.3456 $12.34 |
      | simple-product-04 | Configurable Product 1 Red M   | In Stock     | 1 piece        | $49.3824 | $49.38 -$24.6912 $24.69 |
      | simple-product-05 | Configurable Product 1 Green L | Out of Stock | 2 piece        | $61.728  | $123.46 -$61.728 $61.73 |

  Scenario: Check SKU filter for grouped product variants
    When I reset grid
    And I click "Group Product Variants"
    And I filter SKU as contains "kit"
    Then I should see following grid:
      | SKU               | Product                                       | Availability | Qty Update All | Price     | Subtotal                   |
      | product-kit-01    | Product Kit 1 Product Kit 1 Line Item 1 Notes | In Stock     | 2 piece        | $124.6845 | $249.37 -$124.6845 $124.69 |
      | simple-product-03 | Barcode Scanner: Product 3                    |              | 2 pieces       | $37.0368  |                            |
      | simple-product-02 | Base Unit: Product 2                          |              | 2 pieces       | $24.6912  |                            |
      | product-kit-01    | Product Kit 1 Product Kit 1 Line Item 2 Notes | In Stock     | 1 piece        | $13.5845  | $13.58 -$6.79225 $6.79     |
      | simple-product-01 | Base Unit: Product 1                          |              | 1 piece        | $12.3456  |                            |
      | product-kit-01    | Product Kit 1                                 | In Stock     | 1 piece        | $25.9245  | $25.92 -$12.96225 $12.96   |
      | simple-product-02 | Base Unit: Product 2                          |              | 1 piece        | $24.6912  |                            |

    When I reset grid
    And I click "Group Product Variants"
    And I filter SKU as is any of "product-kit-01,simple-product-02"
    Then I should see following grid:
      | SKU               | Product                                       | Availability | Qty Update All | Price     | Subtotal                   |
      | product-kit-01    | Product Kit 1 Product Kit 1 Line Item 1 Notes | In Stock     | 2 piece        | $124.6845 | $249.37 -$124.6845 $124.69 |
      | simple-product-03 | Barcode Scanner: Product 3                    |              | 2 pieces       | $37.0368  |                            |
      | simple-product-02 | Base Unit: Product 2                          |              | 2 pieces       | $24.6912  |                            |
      | product-kit-01    | Product Kit 1 Product Kit 1 Line Item 2 Notes | In Stock     | 1 piece        | $13.5845  | $13.58 -$6.79225 $6.79     |
      | simple-product-01 | Base Unit: Product 1                          |              | 1 piece        | $12.3456  |                            |
      | product-kit-01    | Product Kit 1                                 | In Stock     | 1 piece        | $25.9245  | $25.92 -$12.96225 $12.96   |
      | simple-product-02 | Base Unit: Product 2                          |              | 1 piece        | $24.6912  |                            |
      | simple-product-02 | Product 2                                     | In Stock     | 1 piece        | $24.6912  | $24.69 -$12.3456 $12.34    |

    When I reset grid
    And I click "Group Product Variants"
    And I filter SKU as is any of "simple-product-01,simple-product-02,simple-product-04"
    Then I should see following grid:
      | SKU               | Product                                       | Availability | Qty Update All | Price     | Subtotal                     |
      | product-kit-01    | Product Kit 1 Product Kit 1 Line Item 1 Notes | In Stock     | 2 piece        | $124.6845 | $249.37 -$124.6845 $124.69   |
      | simple-product-03 | Barcode Scanner: Product 3                    |              | 2 pieces       | $37.0368  |                              |
      | simple-product-02 | Base Unit: Product 2                          |              | 2 pieces       | $24.6912  |                              |
      | product-kit-01    | Product Kit 1 Product Kit 1 Line Item 2 Notes | In Stock     | 1 piece        | $13.5845  | $13.58 -$6.79225 $6.79       |
      | simple-product-01 | Base Unit: Product 1                          |              | 1 piece        | $12.3456  |                              |
      | product-kit-01    | Product Kit 1                                 | In Stock     | 1 piece        | $25.9245  | $25.92 -$12.96225 $12.96     |
      | simple-product-02 | Base Unit: Product 2                          |              | 1 piece        | $24.6912  |                              |
      | simple-product-02 | Product 2                                     | In Stock     | 1 piece        | $24.6912  | $24.69 -$12.3456 $12.34      |
      |                   | Configurable Product 1                        |              | 6 pieces       |           | $395.0596 -$197.5296 $197.53 |
      | simple-product-04 | Red M                                         | In Stock     | 1 piece        | $49.3824  | $49.38 -$24.6912 $24.69      |
      | simple-product-05 | Green L                                       | Out of Stock | 2 piece        | $61.728   | $123.46 -$61.728 $61.73      |
      | simple-product-06 | Blue S                                        | Out of Stock | 3 piece        | $74.0736  | $222.22 -$111.1104 $111.11   |

    When I reset grid
    And I click "Group Product Variants"
    And I filter SKU as is not any of "product-kit-01,simple-product-03"
    Then I should see following grid:
      | SKU               | Product                | Availability | Qty Update All | Price    | Subtotal                     |
      | simple-product-02 | Product 2              | In Stock     | 1 piece        | $24.6912 | $24.69 -$12.3456 $12.34      |
      |                   | Configurable Product 1 |              | 6 pieces       |          | $395.0596 -$197.5296 $197.53 |
      | simple-product-04 | Red M                  | In Stock     | 1 piece        | $49.3824 | $49.38 -$24.6912 $24.69      |
      | simple-product-05 | Green L                | Out of Stock | 2 piece        | $61.728  | $123.46 -$61.728 $61.73      |
      | simple-product-06 | Blue S                 | Out of Stock | 3 piece        | $74.0736 | $222.22 -$111.1104 $111.11   |

    When I reset grid
    And I click "Group Product Variants"
    And I filter SKU as is not any of "simple-product-01,simple-product-02,simple-product-03"
    Then I should see following grid:
      | SKU               | Product                | Availability | Qty Update All | Price    | Subtotal                     |
      |                   | Configurable Product 1 |              | 6 pieces       |          | $395.0596 -$197.5296 $197.53 |
      | simple-product-04 | Red M                  | In Stock     | 1 piece        | $49.3824 | $49.38 -$24.6912 $24.69      |
      | simple-product-05 | Green L                | Out of Stock | 2 piece        | $61.728  | $123.46 -$61.728 $61.73      |
      | simple-product-06 | Blue S                 | Out of Stock | 3 piece        | $74.0736 | $222.22 -$111.1104 $111.11   |

    When I reset grid
    And I click "Group Product Variants"
    And I filter SKU as is not any of "product-kit-01,simple-product-01,simple-product-03,simple-product-04"
    Then I should see following grid:
      | SKU               | Product   | Availability | Qty Update All | Price    | Subtotal                |
      | simple-product-02 | Product 2 | In Stock     | 1 piece        | $24.6912 | $24.69 -$12.3456 $12.34 |

  Scenario: Inline edit product kit line item
    When I reset grid
    And I click on "Shopping List Line Item 4 Quantity"
    And I fill "Shopping List Line Item Form" with:
      | Quantity | 2 |
    And I click "Update All"
    Then I should see following grid containing rows:
      | SKU               | Product                                       | Availability | Qty Update All | Price    | Subtotal                |
      | product-kit-01    | Product Kit 1 Product Kit 1 Line Item 2 Notes | In Stock     | 2 piece        | $13.5845 | $27.17 -$13.5845 $13.59 |
      | simple-product-01 | Base Unit: Product 1                          |              | 1 piece        | $12.3456 |                         |

