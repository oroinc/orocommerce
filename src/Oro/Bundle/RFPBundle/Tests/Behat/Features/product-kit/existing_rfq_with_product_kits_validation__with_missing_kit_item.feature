@regression
@feature-BB-22730
@fixture-OroRFPBundle:product-kit/existing_rfq_with_product_kits_validation__product.yml
@fixture-OroRFPBundle:product-kit/existing_rfq_with_product_kits_validation__with_missing_kit_item__rfq.yml

Feature: Existing RFQ with Product Kits Validation - with Missing Kit Item

  Scenario: Feature background
    Given I login as administrator
    When I go to Sales / Requests For Quote
    Then I should see following grid:
      | Submitted By | Internal Status | PO Number |
      | Amanda Cole  | Open            | PO013     |

  Scenario: Check the line item with a missing mandatory kit item on the Request view page
    When click view "PO013" in grid
    Then I should see next rows in "Request Line Items Table" table
      | SKU               | Product                                                    | Requested Quantity | Target Price |
      | simple-product-01 | Simple Product 01                                          | 1 pc               | $2.00        |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 2] Simple Product 03 | 1 pc               | $104.69      |

  Scenario: Check the line item with a missing mandatory kit item on the Request edit page
    When I click "Edit"
    Then "Request Form" must contains values:
      | Line Item 2 Product         | product-kit-01 - Product Kit 01       |
      | Line Item 2 Quantity        | 1                                     |
      | Line Item 2 Unit            | pc                                    |
      | Line Item 2 Target Price    | 104.69                                |
      | Line Item 2 Item 1 Product  | simple-product-03 - Simple Product 03 |
      | Line Item 2 Item 1 Quantity | 2                                     |
      # Pre-filled data
      | Line Item 2 Item 2 Product  | simple-product-01 - Simple Product 01 |
      | Line Item 2 Item 2 Quantity | 1                                     |
    And I should see the following options for "Line Item 2 Item 1 Product" select in form "Request Form":
      | simple-product-03 - Simple Product 03 |
    And I should see the following options for "Line Item 2 Item 2 Product" select in form "Request Form":
      | simple-product-01 - Simple Product 01 |
      | simple-product-02 - Simple Product 02 |
    And I should see "Optional Item" in the "Request Form Line Item 2 Kit Item 1 Label" element
    And I should see "Mandatory Item *" in the "Request Form Line Item 2 Kit Item 2 Label" element

  Scenario: Save Request for Quote and check the view page
    When fill "Request Form" with:
      | Line Item 2 Item 2 Product  | simple-product-02 - Simple Product 02 |
      | Line Item 2 Item 2 Quantity | 3                                     |
    And I save and close form
    Then I should see "Request has been saved" flash message
    And I should see next rows in "Request Line Items Table" table
      | SKU               | Product                                                                                                 | Requested Quantity | Target Price |
      | simple-product-01 | Simple Product 01                                                                                       | 1 pc               | $2.00        |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 2] Simple Product 03 Mandatory Item [piece x 3] Simple Product 02 | 1 pc               | $104.69      |

