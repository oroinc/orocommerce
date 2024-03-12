@regression
@feature-BB-22730
@fixture-OroSaleBundle:product-kit/existing_quote_with_product_kits_validation__product.yml
@fixture-OroSaleBundle:product-kit/existing_quote_with_product_kits_validation__with_different_min_max_quantity__quote.yml

Feature: Existing Quote with Product Kits Validation - with Different Min Max Quantity

  Scenario: Feature background
    Given I login as administrator
    When I go to Sales / Quotes
    Then I should see following grid:
      | Customer User | Internal Status | PO Number |
      | Amanda Cole   | Draft           | PO013     |

  Scenario: Check the kit item line items with different min/max quantity
    When click edit "PO013" in grid
    Then "Quote Form" must contains values:
      | Line Item 2 Product         | product-kit-01 - Product Kit 01       |
      | Line Item 2 Quantity        | 1                                     |
      | Line Item 2 Unit            | piece                                 |
      | Line Item 2 Price           | 104.69                                |
      | Line Item 2 Item 1 Product  | simple-product-03 - Simple Product 03 |
      | Line Item 2 Item 1 Quantity | 6                                     |
      | Line Item 2 Item 2 Product  | simple-product-01 - Simple Product 01 |
      | Line Item 2 Item 2 Quantity | 11                                    |

  Scenario: Check the min/max quantity validation error for the kit item line items with not actual quantity
    When I click "Submit"
    And I focus on "Quote Form Line Item 2 Kit Item 2 Label"
    Then I should see "Quote Form" validation errors:
      | Line Item 2 Item 1 Quantity | The quantity should be between 0 and 5  |
      | Line Item 2 Item 2 Quantity | The quantity should be between 1 and 10 |

  Scenario: Save Quote and check the view page
    When I fill "Quote Form" with:
      | Line Item 2 Item 1 Quantity | 3 |
      | Line Item 2 Item 2 Quantity | 2 |
    And I click "Submit"
    And agree that shipping cost may have changed
    Then should see "Quote #Quote1 successfully updated" flash message
    And I should see next rows in "Quote Line Items Table" table
      | SKU               | Product                                                                                                 | Quantity      | Price   |
      | simple-product-01 | Simple Product 01                                                                                       | 2 pcs or more | $2.00   |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 3] Simple Product 03 Mandatory Item [piece x 2] Simple Product 01 | 1 pc or more  | $104.69 |
