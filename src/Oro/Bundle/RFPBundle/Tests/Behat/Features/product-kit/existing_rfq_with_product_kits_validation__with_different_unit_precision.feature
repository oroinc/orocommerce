@regression
@feature-BB-22730
@fixture-OroRFPBundle:product-kit/existing_rfq_with_product_kits_validation__product.yml
@fixture-OroRFPBundle:product-kit/existing_rfq_with_product_kits_validation__with_different_unit_precision__rfq.yml

Feature: Existing RFQ with Product Kits Validation - with Different Unit Precision

  Scenario: Feature background
    Given I login as administrator
    When I go to Sales / Requests For Quote
    Then I should see following grid:
      | Submitted By | Internal Status | PO Number |
      | Amanda Cole  | Open            | PO013     |

  Scenario: Check the kit item line items with different unit precision
    When click edit "PO013" in grid
    Then "Request Form" must contains values:
      | Line Item 2 Product         | product-kit-01 - Product Kit 01       |
      | Line Item 2 Quantity        | 1                                     |
      | Line Item 2 Unit            | pc                                    |
      | Line Item 2 Target Price    | 104.69                                |
      | Line Item 2 Item 1 Product  | simple-product-03 - Simple Product 03 |
      | Line Item 2 Item 1 Quantity | 1.23                                  |
      | Line Item 2 Item 2 Product  | simple-product-01 - Simple Product 01 |
      | Line Item 2 Item 2 Quantity | 2.345                                 |

  Scenario: Check the unit precision validation error for the kit item line items with different unit precision
    When fill "Request Form" with:
      | Line Item 2 Item 1 Quantity | 1.234  |
      | Line Item 2 Item 2 Quantity | 2.3456 |
    And I save form
    And I focus on "Request Form Line Item 2 Kit Item 2 Label"
    Then I should see "Request Form" validation errors:
      | Line Item 2 Item 1 Quantity | Only whole numbers are allowed for unit "piece" |
      | Line Item 2 Item 2 Quantity | Only whole numbers are allowed for unit "piece" |

  Scenario: Save Request for Quote and check the view page
    When fill "Request Form" with:
      | Line Item 2 Item 1 Quantity | 1 |
      | Line Item 2 Item 2 Quantity | 2 |
    And I save and close form
    Then I should see "Request has been saved" flash message
    And I should see next rows in "Request Line Items Table" table
      | SKU               | Product                                                                                                 | Requested Quantity | Target Price |
      | simple-product-01 | Simple Product 01                                                                                       | 1 pc               | $2.00        |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 1] Simple Product 03 Mandatory Item [piece x 2] Simple Product 01 | 1 pc               | $104.69      |
