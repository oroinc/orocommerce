@regression
@feature-BB-22730
@fixture-OroRFPBundle:product-kit/existing_rfq_with_product_kits_validation__product.yml
@fixture-OroRFPBundle:product-kit/existing_rfq_with_product_kits_validation__with_different_unit__rfq.yml

Feature: Existing RFQ with Product Kits Validation - with Different Unit

  Scenario: Feature Background
    Given I login as administrator
    When I go to Sales / Requests For Quote
    Then I should see following grid:
      | Submitted By | Internal Status | PO Number |
      | Amanda Cole  | Open            | PO013     |

  Scenario: Check the kit item line items with different unit on the Request view page
    When click view "PO013" in grid
    Then I should see next rows in "Request Line Items Table" table
      | SKU               | Product                                                                                               | Requested Quantity | Target Price |
      | simple-product-01 | Simple Product 01                                                                                     | 1 pc               | $2.00        |
      | product-kit-01    | Product Kit 01 Optional Item [each x 2] Simple Product 03 Mandatory Item [each x 3] Simple Product 01 | 1 pc               | $104.69      |

  Scenario: Check validation errors for the kit item line items with different unit precision
    When I click "Edit"
    And fill "Request Form" with:
      | Line Item 2 Item 1 Quantity | 1.234  |
      | Line Item 2 Item 2 Quantity | 2.3456 |
    And I save form
    And I focus on "Request Form Line Item 2 Kit Item 2 Label"
    Then I should see "Request Form" validation errors:
      | Line Item 2 Item 1 Quantity | Only whole numbers are allowed for unit "piece" |
      | Line Item 2 Item 2 Quantity | Only whole numbers are allowed for unit "piece" |

  Scenario: Save Request for Quote and check the view page
    When fill "Request Form" with:
      | Line Item 2 Item 1 Quantity | 2 |
      | Line Item 2 Item 2 Quantity | 3 |
    And I save and close form
    Then I should see "Request has been saved" flash message
    And I should see next rows in "Request Line Items Table" table
      | SKU               | Product                                                                                                 | Requested Quantity | Target Price |
      | simple-product-01 | Simple Product 01                                                                                       | 1 pc               | $2.00        |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 2] Simple Product 03 Mandatory Item [piece x 3] Simple Product 01 | 1 pc               | $104.69      |
