@regression
@feature-BB-22730
@fixture-OroSaleBundle:product-kit/existing_quote_with_product_kits_validation__product.yml
@fixture-OroSaleBundle:product-kit/existing_quote_with_product_kits_validation__with_missing_kit_item__quote.yml

Feature: Existing Quote with Product Kits Validation - with Missing Kit Item

  Scenario: Feature background
    Given I login as administrator
    When I go to Sales / Quotes
    Then I should see following grid:
      | Customer User | Internal Status | PO Number |
      | Amanda Cole   | Draft           | PO013     |

  Scenario: Check the line item with a missing mandatory kit item on the Quote view page
    When click view "PO013" in grid
    Then I should see next rows in "Quote Line Items Table" table
      | SKU               | Product                                                    | Quantity     | Price   |
      | simple-product-01 | Simple Product 01                                          | 1 pc or more | $2.00   |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 2] Simple Product 03 | 1 pc or more | $104.69 |

  Scenario: Check the line item with a missing mandatory kit item on the Quote edit page
    When I click "Edit"
    Then "Quote Form" must contains values:
      | Line Item 2 Product         | product-kit-01 - Product Kit 01       |
      | Line Item 2 Quantity        | 1                                     |
      | Line Item 2 Unit            | piece                                 |
      | Line Item 2 Price           | 104.69                                |
      | Line Item 2 Item 1 Product  | simple-product-03 - Simple Product 03 |
      | Line Item 2 Item 1 Quantity | 2                                     |
      # Pre-filled data
      | Line Item 2 Item 2 Product  | simple-product-01 - Simple Product 01 |
      | Line Item 2 Item 2 Quantity | 1                                     |
    And I should see the following options for "Line Item 2 Item 1 Product" select in form "Quote Form":
      | simple-product-03 - Simple Product 03 |
    And I should see the following options for "Line Item 2 Item 2 Product" select in form "Quote Form":
      | simple-product-01 - Simple Product 01 |
      | simple-product-02 - Simple Product 02 |
    And I should see "Optional Item" in the "Quote Form Line Item 2 Kit Item 1 Label" element
    And I should see "Mandatory Item *" in the "Quote Form Line Item 2 Kit Item 2 Label" element

  Scenario: Save Quote and check the view page
    When fill "Quote Form" with:
      | Line Item 2 Item 2 Product  | simple-product-02 - Simple Product 02 |
      | Line Item 2 Item 2 Quantity | 3                                     |
    And I click "Submit"
    And agree that shipping cost may have changed
    Then should see "Quote #Quote1 successfully updated" flash message
    And I should see next rows in "Quote Line Items Table" table
      | SKU               | Product                                                                                                 | Quantity     | Price   |
      | simple-product-01 | Simple Product 01                                                                                       | 1 pc or more | $2.00   |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 2] Simple Product 03 Mandatory Item [piece x 3] Simple Product 02 | 1 pc or more | $104.69 |

