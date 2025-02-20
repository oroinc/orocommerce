@feature-BB-22730
@fixture-OroSaleBundle:product-kit/update_quote_with_product_kits.yml

Feature: Update Quote with Product Kits

  Scenario: Add a product kit line item
    Given I login as administrator
    When I go to Sales / Quotes
    Then I should see following grid:
      | Customer User | Internal Status | PO Number |
      | Amanda Cole   | Draft           | PO013     |
    When click edit "PO013" in grid
    And click "Backend Quote Add Product Button"
    And fill "Quote Form" with:
      | Line Item 2 Product  | product-kit-01 |
      | Line Item 2 Quantity | 1              |
      | Line Item 2 Unit     | piece          |
  #   TODO: Should be uncommented after implementation BB-23120 feature
#      | Line Item 2 Price    | 104.69         |
    Then I should see "Optional Item" in the "Quote Form Line Item 2 Kit Item 1 Label" element
    And I should see "Mandatory Item *" in the "Quote Form Line Item 2 Kit Item 2 Label" element
    And the "Line Item 2 Item 1 Quantity" field should be disabled in form "Quote Form"
    And "Quote Form" must contains values:
      | Line Item 2 Product         | product-kit-01 - Product Kit 01       |
      | Line Item 2 Quantity        | 1                                     |
      | Line Item 2 Price           | 124.69                                |
      | Line Item 2 Item 1 Product  | None                                  |
      | Line Item 2 Item 1 Quantity |                                       |
      | Line Item 2 Item 1 Price    |                                       |
      | Line Item 2 Item 2 Product  | simple-product-01 - Simple Product 01 |
      | Line Item 2 Item 2 Quantity | 1                                     |
      | Line Item 2 Item 2 Price    | 1.23                                  |
    And I should see the following options for "Line Item 2 Item 1 Product" select in form "Quote Form":
      | simple-product-03 - Simple Product 03 |
    And I should see the following options for "Line Item 2 Item 2 Product" select in form "Quote Form":
      | simple-product-01 - Simple Product 01 |
      | simple-product-02 - Simple Product 02 |
    And the "LineItemPrice2" field should be readonly in form "Quote Form"
    And I should not see an "Line Item 2 Add Offer Button" element
    And I should see "Line Item 2 Offer 1 Remove Button" button disabled
    When I click on "Quote Form Line Item 2 Kit Item 1 Quantity Label Tooltip"
    Then I should see "The quantity of product kit item units to be purchased: piece (whole numbers)" in the "Tooltip Popover Content" element

  Scenario: Add one more product kit line item via the entity select popup
    When click "Backend Quote Add Product Button"
    And I open select entity popup for field "Line Item 3 Product Dropdown" in form "Quote Form"
    And I sort grid by "SKU"
    Then I should see following grid:
      | SKU               | Name              |
      | product-kit-01    | Product Kit 01    |
      | simple-product-01 | Simple Product 01 |
      | simple-product-02 | Simple Product 02 |
      | simple-product-03 | Simple Product 03 |
    When I click on product-kit-01 in grid
    And fill "Quote Form" with:
      | Line Item 3 Quantity        | 2                                     |
      | Line Item 3 Unit            | piece                                 |
#   TODO: Should be uncommented after implementation BB-23120 feature
#      | Line Item 3 Price           | 106.94                                |
      | Line Item 3 Item 1 Product  | simple-product-03 - Simple Product 03 |
      | Line Item 3 Item 1 Quantity | 3                                     |
    Then "Quote Form" must contains values:
      | Line Item 3 Product         | product-kit-01 - Product Kit 01       |
      | Line Item 3 Quantity        | 2                                     |
      | Line Item 3 Price           | 128.38                                |
      | Line Item 3 Item 1 Product  | simple-product-03 - Simple Product 03 |
      | Line Item 3 Item 1 Quantity | 3                                     |
      | Line Item 3 Item 1 Price    | 1.23                                  |
      | Line Item 3 Item 2 Product  | simple-product-01 - Simple Product 01 |
      | Line Item 3 Item 2 Quantity | 1                                     |
      | Line Item 3 Item 2 Price    | 1.23                                  |
    And I should not see an "Line Item 3 Add Offer Button" element
    And I should see "Line Item 3 Offer 1 Remove Button" button disabled
    And the "LineItemPrice2" field should be readonly in form "Quote Form"
    And the "LineItemPrice3" field should be readonly in form "Quote Form"

  Scenario: Change offer currency for line item with kit product
    When I fill "Quote Form" with:
      | LineItemCurrency3 | € |
    Then I should see "Price, $:" in the "Quote Form Line Item 2 Kit Item 1 Price Label" element
    And I should see "Price, $:" in the "Quote Form Line Item 2 Kit Item 2 Price Label" element
    And I should see "Price, €:" in the "Quote Form Line Item 3 Kit Item 1 Price Label" element
    And I should see "Price, €:" in the "Quote Form Line Item 3 Kit Item 2 Price Label" element
    Then "Quote Form" must contains values:
      | Line Item 2 Product         | product-kit-01 - Product Kit 01       |
      | Line Item 2 Quantity        | 1                                     |
      | Line Item 2 Price           | 124.69                                |
      | Line Item 2 Item 1 Product  | None                                  |
      | Line Item 2 Item 1 Quantity |                                       |
      | Line Item 2 Item 1 Price    |                                       |
      | Line Item 2 Item 2 Product  | simple-product-01 - Simple Product 01 |
      | Line Item 2 Item 2 Quantity | 1                                     |
      | Line Item 2 Item 2 Price    | 1.23                                  |
      | Line Item 3 Product         | product-kit-01 - Product Kit 01       |
      | Line Item 3 Quantity        | 2                                     |
      | Line Item 3 Price           |                                       |
      | Line Item 3 Item 1 Product  | simple-product-03 - Simple Product 03 |
      | Line Item 3 Item 1 Quantity | 3                                     |
      | Line Item 3 Item 1 Price    |                                       |
      | Line Item 3 Item 2 Product  | simple-product-01 - Simple Product 01 |
      | Line Item 3 Item 2 Quantity | 1                                     |
      | Line Item 3 Item 2 Price    |                                       |
    When I fill "Quote Form" with:
      | LineItemCurrency3 | $ |
    Then I should see "Price, $:"
    And I should not see "Price, €:"

  Scenario: Change simple product on kit product
    When I click "Backend Quote Add Product Button"
    And fill "Quote Form" with:
      | Line Item 4 Product  | Simple Product 02 |
      | Line Item 4 Quantity | 1                 |
    And I click "Line Item 4 Add Offer Button"
    And I click "Line Item 4 Add Offer Button"
    Then I should see "Line Item 4 Offer 1 Remove Button" button enabled
    And fill "Quote Form" with:
      | Line Item 4 Price  | 1 |
      | Line Item 4 Price2 | 2 |
      | Line Item 4 Price3 | 3 |
    When fill "Quote Form" with:
      | Line Item 4 Product  | product-kit-01 |
    Then I should see "Line Item 4 Offer 1 Remove Button" button disabled
    And I should not see an "Line Item 4 Add Offer Button" element
    And I should not see an "Line Item 4 Price2" element
    And I click "Quote Form Line Item 4 Remove"

  Scenario: Save Quote and check the view page
    When I click "Submit"
    And agree that shipping cost may have changed
    Then should see "Quote #Quote1 successfully updated" flash message
    And I should see Quote with:
      | Customer User   | Amanda Cole |
      | PO Number       | PO013       |
      | Internal Status | Draft       |
    And I should see next rows in "Quote Line Items Table" table
      | SKU               | Product                                                                                                 | Quantity      | Price   |
      | simple-product-01 | Simple Product 01                                                                                       | 1 pc or more  | $2.00   |
      | product-kit-01    | Product Kit 01 Mandatory Item [piece x 1] Simple Product 01                                             | 1 pc or more  | $124.69 |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 3] Simple Product 03 Mandatory Item [piece x 1] Simple Product 01 | 2 pcs or more | $128.38 |

  Scenario: Change product of a kit item line item
    When I click "Edit"
    And I fill "Quote Form" with:
      | Line Item 2 Item 2 Product | simple-product-02 - Simple Product 02 |
    Then "Quote Form" must contains values:
      | Line Item 2 Item 2 Quantity | 1 |
    And the "LineItemPrice2" field should be readonly in form "Quote Form"
    And the "LineItemPrice3" field should be readonly in form "Quote Form"
    When I click "Submit"
    And agree that shipping cost may have changed
    Then should see "Quote #Quote1 successfully updated" flash message
    And I should see next rows in "Quote Line Items Table" table
      | SKU               | Product                                                                                                 | Quantity      | Price   |
      | simple-product-01 | Simple Product 01                                                                                       | 1 pc or more  | $2.00   |
      | product-kit-01    | Product Kit 01 Mandatory Item [piece x 1] Simple Product 02                                             | 1 pc or more  | $124.69 |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 3] Simple Product 03 Mandatory Item [piece x 1] Simple Product 01 | 2 pcs or more | $128.38 |

  Scenario: Change quantity of a kit item line item
    When I click "Edit"
    And I fill "Quote Form" with:
      | Line Item 2 Item 2 Quantity | 2 |
    Then "Quote Form" must contains values:
      | Line Item 2 Item 2 Quantity | 2 |
    And the "LineItemPrice2" field should be readonly in form "Quote Form"
    And the "LineItemPrice3" field should be readonly in form "Quote Form"
    When I click "Submit"
    And agree that shipping cost may have changed
    Then should see "Quote #Quote1 successfully updated" flash message
    And I should see next rows in "Quote Line Items Table" table
      | SKU               | Product                                                                                                 | Quantity      | Price   |
      | simple-product-01 | Simple Product 01                                                                                       | 1 pc or more  | $2.00   |
      | product-kit-01    | Product Kit 01 Mandatory Item [piece x 2] Simple Product 02                                             | 1 pc or more  | $124.69 |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 3] Simple Product 03 Mandatory Item [piece x 1] Simple Product 01 | 2 pcs or more | $128.38 |

  Scenario: Remove optional kit item line item
    When I click "Edit"
    And I clear "Line Item 3 Item 1 Product" field in form "Quote Form"
    Then the "Line Item 3 Item 1 Quantity" field should be disabled in form "Quote Form"
    And the "LineItemPrice2" field should be readonly in form "Quote Form"
    And the "LineItemPrice3" field should be readonly in form "Quote Form"
    When I click "Submit"
    And agree that shipping cost may have changed
    Then should see "Quote #Quote1 successfully updated" flash message
    And I should see next rows in "Quote Line Items Table" table
      | SKU               | Product                                                     | Quantity      | Price   |
      | simple-product-01 | Simple Product 01                                           | 1 pc or more  | $2.00   |
      | product-kit-01    | Product Kit 01 Mandatory Item [piece x 2] Simple Product 02 | 1 pc or more  | $124.69 |
      | product-kit-01    | Product Kit 01 Mandatory Item [piece x 1] Simple Product 01 | 2 pcs or more | $128.38 |

  Scenario: Remove a line item
    When I click "Edit"
    And I click on "Quote Form Line Item 3 Remove"
    And the "LineItemPrice2" field should be readonly in form "Quote Form"
    And I click "Submit"
    And agree that shipping cost may have changed
    Then should see "Quote #Quote1 successfully updated" flash message
    And I should see next rows in "Quote Line Items Table" table
      | SKU               | Product                                                     | Quantity     | Price   |
      | simple-product-01 | Simple Product 01                                           | 1 pc or more | $2.00   |
      | product-kit-01    | Product Kit 01 Mandatory Item [piece x 2] Simple Product 02 | 1 pc or more | $124.69 |
