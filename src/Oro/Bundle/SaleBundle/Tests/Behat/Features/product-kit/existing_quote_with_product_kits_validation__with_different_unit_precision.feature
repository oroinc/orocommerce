@regression
@feature-BB-22730
@fixture-OroSaleBundle:product-kit/existing_quote_with_product_kits_validation__product.yml
@fixture-OroSaleBundle:product-kit/existing_quote_with_product_kits_validation__with_different_unit_precision__quote.yml

Feature: Existing Quote with Product Kits Validation - with Different Unit Precision

  Scenario: Feature background
    Given I login as administrator
    When I go to Sales / Quotes
    Then I should see following grid:
      | Customer User | Internal Status | PO Number |
      | Amanda Cole   | Draft           | PO013     |

  Scenario: Check the kit item line items with different unit precision
    When click edit "PO013" in grid
    Then "Quote Form" must contains values:
      | Line Item 2 Product         | product-kit-01 - Product Kit 01       |
      | Line Item 2 Quantity        | 1                                     |
      | Line Item 2 Unit            | piece                                 |
      | Line Item 2 Price           | 104.69                                |
      | Line Item 2 Item 1 Product  | simple-product-03 - Simple Product 03 |
      | Line Item 2 Item 1 Quantity | 1.23                                  |
      | Line Item 2 Item 2 Product  | simple-product-01 - Simple Product 01 |
      | Line Item 2 Item 2 Quantity | 2.345                                 |

  Scenario: Check the unit precision validation error for the kit item line items with different unit precision
    When fill "Quote Form" with:
      | Line Item 2 Item 1 Quantity | 1.234  |
      | Line Item 2 Item 2 Quantity | 2.3456 |
    And I click "Submit"
    And agree that shipping cost may have changed
    And I focus on "Quote Form Line Item 2 Kit Item 2 Label"
    Then I should see "Quote Form" validation errors:
      | Line Item 2 Item 1 Quantity | Only whole numbers are allowed for unit "piece" |
      | Line Item 2 Item 2 Quantity | Only whole numbers are allowed for unit "piece" |

  Scenario: Save Quote and check the view page
    When fill "Quote Form" with:
      | Line Item 2 Item 1 Quantity | 1 |
      | Line Item 2 Item 2 Quantity | 2 |
    And I click "Submit"
    And agree that shipping cost may have changed
    Then should see "Quote #Quote1 successfully updated" flash message
    And I should see next rows in "Quote Line Items Table" table
      | SKU               | Product                                                                                                 | Quantity     | Price   |
      | simple-product-01 | Simple Product 01                                                                                       | 1 pc or more | $2.00   |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 1] Simple Product 03 Mandatory Item [piece x 2] Simple Product 01 | 1 pc or more | $104.69 |
