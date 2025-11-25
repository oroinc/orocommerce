@regression
@fixture-OroShoppingListBundle:product_kits_in_shopping_list_dialog.yml

Feature: Shopping list Save for later in Storefront for product kit

  Scenario: Enable Save For Later Feature
    Given I login as administrator
    And I go to System/Configuration
    And I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And I fill "Shopping List Configuration Form" with:
      | Enable Save For Later Use default | false |
      | Enable Save For Later             | true  |
    And I save setting
    Then I should see "Configuration saved" flash message

  Scenario: Check Shopping List Saved For Later in the Storefront edit page
    Given I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list Product Kit Shopping List
    Then I should see no records in "Frontend Shopping List Saved For Later Line Items Grid" table
    And I should see "Summary 3 Items"
    And I should see "Subtotal $381.00"
    And I should see "Total: $381.00"

    When I click on the first "Frontend Shopping List Line Items Edit Note"
    And I fill in "Shopping List Notes in Popover" with ""
    And I click on "UiPopover Submit Button"
    Then I should see following "Frontend Shopping List Edit Grid" grid:
      | SKU               | Product                                       | Availability | Qty Update All | Price   | Subtotal |
      | product-kit-1     | Product Kit 1                                 | In Stock     | 2 piece        | $134.00 | $268.00  |
      | simple-product-03 | Barcode Scanner: Product 3                    |              | 2 pieces       | $31.00  |          |
      | simple-product-02 | Base Unit: Product 2                          |              | 2 pieces       | $31.00  |          |

      | product-kit-1     | Product Kit 1 Product Kit 1 Line Item 2 Notes | In Stock     | 1 piece        | $41.00  | $41.00   |
      | simple-product-01 | Base Unit: Product 1                          |              | 1 piece        | $31.00  |          |

      | product-kit-1     | Product Kit 1 Product Kit 1 Line Item 3 Notes | In Stock     | 1 piece        | $72.00  | $72.00   |
      | simple-product-03 | Barcode Scanner: Product 3                    |              | 1 piece        | $31.00  |          |
      | simple-product-02 | Base Unit: Product 2                          |              | 1 piece        | $31.00  |          |

  Scenario: Save products for later moves them from list to Saved For Later section
    When I check all visible on page in "Frontend Shopping List Edit Grid"
    And I click "Save For Later" link from mass action dropdown in "Frontend Shopping List Edit Grid"
    Then should see "Save Products For Later Selected products will be saved for later." in confirmation dialogue
    When I click "Yes, Save" in confirmation dialogue
    Then I should see "3 product(s) have been saved for later successfully." flash message
    And I should see "Summary No Items"
    And I should see "Total: $0.00"
    And I should see no records in "Frontend Shopping List Edit Grid" table
    And I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU               | Product                                       | Availability | Qty Update All | Price   | Subtotal |
      | product-kit-1     | Product Kit 1                                 | In Stock     | 2 piece        | $134.00 | $268.00  |
      | simple-product-03 | Barcode Scanner: Product 3                    |              | 2 pieces       | $31.00  |          |
      | simple-product-02 | Base Unit: Product 2                          |              | 2 pieces       | $31.00  |          |

      | product-kit-1     | Product Kit 1 Product Kit 1 Line Item 2 Notes | In Stock     | 1 piece        | $41.00  | $41.00   |
      | simple-product-01 | Base Unit: Product 1                          |              | 1 piece        | $31.00  |          |

      | product-kit-1     | Product Kit 1 Product Kit 1 Line Item 3 Notes | In Stock     | 1 piece        | $72.00  | $72.00   |
      | simple-product-03 | Barcode Scanner: Product 3                    |              | 1 piece        | $31.00  |          |
      | simple-product-02 | Base Unit: Product 2                          |              | 1 piece        | $31.00  |          |

  Scenario: Delete product from Saved For Later
    When I click "Delete" on row "Line Item 3 Notes" in grid "Frontend Shopping List Saved For Later Line Items Grid"
    And I confirm deletion
    Then I should see 'The "Product Kit 1" product was successfully deleted' flash message
    And I should see no records in "Frontend Shopping List Edit Grid" table
    And I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU               | Product                                       | Availability | Qty Update All | Price   | Subtotal |
      | product-kit-1     | Product Kit 1                                 | In Stock     | 2 piece        | $134.00 | $268.00  |
      | simple-product-03 | Barcode Scanner: Product 3                    |              | 2 pieces       | $31.00  |          |
      | simple-product-02 | Base Unit: Product 2                          |              | 2 pieces       | $31.00  |          |

      | product-kit-1     | Product Kit 1 Product Kit 1 Line Item 2 Notes | In Stock     | 1 piece        | $41.00  | $41.00   |
      | simple-product-01 | Base Unit: Product 1                          |              | 1 piece        | $31.00  |          |

  Scenario: Move item from Saved For Later back to Shopping List
    When I click 'Remove From "Saved For Later"' on row "Line Item 2 Notes" in grid "Frontend Shopping List Saved For Later Line Items Grid"
    Then I should see "Are you sure you want to remove this product from \"Saved For Later\"?"
    When I click "Yes, Remove" in confirmation dialogue
    Then I should see following "Frontend Shopping List Edit Grid" grid:
      | SKU               | Product                                       | Availability | Qty     | Price  | Subtotal |
      | product-kit-1     | Product Kit 1 Product Kit 1 Line Item 2 Notes | In Stock     | 1 piece | $41.00 | $41.00   |
      | simple-product-01 | Base Unit: Product 1                          |              | 1 piece | $31.00 |          |
    And I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU               | Product                    | Availability | Qty Update All | Price   | Subtotal |
      | product-kit-1     | Product Kit 1              | In Stock     | 2 piece        | $134.00 | $268.00  |
      | simple-product-03 | Barcode Scanner: Product 3 |              | 2 pieces       | $31.00  |          |
      | simple-product-02 | Base Unit: Product 2       |              | 2 pieces       | $31.00  |          |

  Scenario: Add product notes in Saved For Later section
    When I click 'Add a note' on row "Product Kit 1" in grid "Frontend Shopping List Saved For Later Line Items Grid"
    And I fill in "Shopping List Product Note" with "Add New Note"
    And I click "Add Note" in modal window
    Then I should see "Line item note has been successfully updated" flash message
    And I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU               | Product                    | Availability | Qty Update All | Price   | Subtotal |
      | product-kit-1     | Product Kit 1 Add New Note | In Stock     | 2 piece        | $134.00 | $268.00  |
      | simple-product-03 | Barcode Scanner: Product 3 |              | 2 pieces       | $31.00  |          |
      | simple-product-02 | Base Unit: Product 2       |              | 2 pieces       | $31.00  |          |

  Scenario: Edit quantity of a Saved For Later product
    When I click on the first "Frontend Shopping List Saved For Later Line Items Product Quantity Increment"
    And I click on the first "Frontend Shopping List Saved For Later Line Items Save Changes Button"
    Then I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU               | Product                    | Availability | Qty Update All | Price   | Subtotal |
      | product-kit-1     | Product Kit 1 Add New Note | In Stock     | 3 piece        | $134.00 | $402.00  |
      | simple-product-03 | Barcode Scanner: Product 3 |              | 2 pieces       | $31.00  |          |
      | simple-product-02 | Base Unit: Product 2       |              | 2 pieces       | $31.00  |          |

    When I click on the first "Frontend Shopping List Saved For Later Line Items Product Quantity"
    And I type "5" in "Frontend Shopping List Saved For Later Line Item 1 Product Quantity Input"
    And I click on the first "Frontend Shopping List Saved For Later Line Items Save Changes Button"
    Then I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU               | Product                    | Availability | Qty Update All | Price   | Subtotal |
      | product-kit-1     | Product Kit 1 Add New Note | In Stock     | 5 piece        | $134.00 | $670.00  |
      | simple-product-03 | Barcode Scanner: Product 3 |              | 2 pieces       | $31.00  |          |
      | simple-product-02 | Base Unit: Product 2       |              | 2 pieces       | $31.00  |          |

  Scenario: Configure product-kit in Saved For Later section
    When I click 'Configure' on row "Product Kit 1" in grid "Frontend Shopping List Saved For Later Line Items Grid"
    And I fill "Product Kit Line Item Form" with:
      | Kit Item Line Item 1 Quantity | 1 |
      | Kit Item Line Item 2 Quantity | 1 |
    And I fill "Product Kit Line Item Form" with:
      | Notes | Add New Note From Configure |
    And I fill "Product Kit Line Item Totals Form" with:
      | Quantity | 2 |
    Then I should see "Product Kit Dialog" with elements:
      | Price | Total: $144.00 |
    When I click "Update Product Kit Shopping List" in "Shopping List Button Group in Dialog" element
    Then I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU               | Product                                   | Availability | Qty Update All | Price  | Subtotal |
      | product-kit-1     | Product Kit 1 Add New Note From Configure | In Stock     | 2 piece        | $72.00 | $144.00  |
      | simple-product-03 | Barcode Scanner: Product 3                |              | 1 piece        | $31.00 |          |
      | simple-product-02 | Base Unit: Product 2                      |              | 1 piece        | $31.00 |          |

  Scenario: Add product back to Shopping List and save for later again
    When type "Product Kit 1" in "search"
    And I click "Search Button"
    And I click "View Details" for "product-kit-1" product
    And I click "Configure and Add to Shopping List"
    And I click "Kit Item Line Item 1 Product 1"
    And I click "Kit Item Line Item 2 Product 2"
    And I fill "Product Kit Line Item Form" with:
      | Notes | New Notes |
    Then I should see "Product Kit Dialog" with elements:
      | Price | Total: $72.00 |
    When I click "Add to Product Kit Shopping List" in "Shopping List Button Group in Dialog" element
    Then I should see 'Product kit has been added to "Product Kit Shopping List"' flash message

    When I open page with shopping list Product Kit Shopping List
    Then I should see following "Frontend Shopping List Edit Grid" grid:
      | SKU               | Product                                       | Availability | Qty Update All | Price  | Subtotal |
      | product-kit-1     | Product Kit 1 Product Kit 1 Line Item 2 Notes | In Stock     | 1 piece        | $41.00 | $41.00   |
      | simple-product-01 | Base Unit: Product 1                          |              | 1 piece        | $31.00 |          |

      | product-kit-1     | Product Kit 1 New Notes                       | In Stock     | 1 piece        | $72.00 | $72.00   |
      | simple-product-03 | Barcode Scanner: Product 3                    |              | 1 piece        | $31.00 |          |
      | simple-product-02 | Base Unit: Product 2                          |              | 1 piece        | $31.00 |          |
    And I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU               | Product                                   | Availability | Qty     | Price  | Subtotal |
      | product-kit-1     | Product Kit 1 Add New Note From Configure | In Stock     | 2 piece | $72.00 | $144.00  |
      | simple-product-03 | Barcode Scanner: Product 3                |              | 1 piece | $31.00 |          |
      | simple-product-02 | Base Unit: Product 2                      |              | 1 piece | $31.00 |          |

    When I click 'Save For Later' on row "Product Kit 1 New Notes" in grid "Frontend Shopping List Edit Grid"
    And I click "Yes, Save" in confirmation dialogue
    Then I should see following "Frontend Shopping List Edit Grid" grid:
      | SKU               | Product                                       | Availability | Qty     | Price  | Subtotal |
      | product-kit-1     | Product Kit 1 Product Kit 1 Line Item 2 Notes | In Stock     | 1 piece | $41.00 | $41.00   |
      | simple-product-01 | Base Unit: Product 1                          |              | 1 piece | $31.00 |          |

    And I should see following "Frontend Shopping List Saved For Later Line Items Grid" grid:
      | SKU               | Product                                   | Availability | Qty Update All | Price  | Subtotal |
      | product-kit-1     | Product Kit 1 Add New Note From Configure | In Stock     | 3 piece        | $72.00 | $216.00  |
      | simple-product-03 | Barcode Scanner: Product 3                |              | 1 piece        | $31.00 |          |
      | simple-product-02 | Base Unit: Product 2                      |              | 1 piece        | $31.00 |          |
