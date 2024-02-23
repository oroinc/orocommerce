@regression
@feature-BB-22730
@fixture-OroRFPBundle:product-kit/existing_rfq_with_product_kits_validation__product.yml
@fixture-OroRFPBundle:product-kit/existing_rfq_with_product_kits_validation__with_different_min_max_quantity__rfq.yml

Feature: Existing RFQ with Product Kits Validation - with Different Min Max Quantity

  Scenario: Feature background
    Given I login as administrator
    When I go to Sales / Requests For Quote
    Then I should see following grid:
      | Submitted By | Internal Status | PO Number |
      | Amanda Cole  | Open            | PO013     |

  Scenario: Check the kit item line items with different min/max quantity
    When click edit "PO013" in grid
    Then "Request Form" must contains values:
      | Line Item 2 Product         | product-kit-01 - Product Kit 01       |
      | Line Item 2 Quantity        | 1                                     |
      | Line Item 2 Unit            | pc                                    |
      | Line Item 2 Target Price    | 104.69                                |
      | Line Item 2 Item 1 Product  | simple-product-03 - Simple Product 03 |
      | Line Item 2 Item 1 Quantity | 6                                     |
      | Line Item 2 Item 2 Product  | simple-product-01 - Simple Product 01 |
      | Line Item 2 Item 2 Quantity | 11                                    |

  Scenario: Check the min/max quantity validation error for the kit item line items with not actual quantity
    When I save form
    Then I should see "Request Form" validation errors:
      | Line Item 2 Item 1 Quantity | The quantity should be between 0 and 5  |
      | Line Item 2 Item 2 Quantity | The quantity should be between 1 and 10 |

  Scenario: Save Request for Quote and check the view page
    When I fill "Request Form" with:
      | Line Item 2 Item 1 Quantity | 3 |
      | Line Item 2 Item 2 Quantity | 2 |
    And save and close form
    Then should see "Request has been saved" flash message
    And I should see next rows in "Request Line Items Table" table
      | SKU               | Product                                                                                                 | Requested Quantity | Target Price |
      | simple-product-01 | Simple Product 01                                                                                       | 1 pc               | $2.00        |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 3] Simple Product 03 Mandatory Item [piece x 2] Simple Product 01 | 1 pc               | $104.69      |
