@regression
@feature-BB-22730
@fixture-OroRFPBundle:product-kit/existing_rfq_with_product_kits_validation__product.yml
@fixture-OroRFPBundle:product-kit/existing_rfq_with_product_kits_validation__with_missing_product__rfq.yml

Feature: Existing RFQ with Product Kits Validation - with Missing Product

  Scenario: Remove a product
    Given I login as administrator
    When go to Products/ Products
    And click delete "simple-product-05" in grid
    And I confirm deletion
    Then I should see "Product deleted" flash message

  Scenario: Check the kit item line items with a missing and disabled products on the Request view page
    When I go to Sales / Requests For Quote
    Then I should see following grid:
      | Submitted By | Internal Status | PO Number |
      | Amanda Cole  | Open            | PO013     |
    And click view "PO013" in grid
    Then I should see next rows in "Request Line Items Table" table
      | SKU               | Product                                                                                                            | Requested Quantity | Target Price |
      | simple-product-01 | Simple Product 01                                                                                                  | 1 pc               | $2.00        |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 2] Simple Product 03 Mandatory Item [piece x 3] Simple Product 05 - Deleted  | 1 pc               | $104.69      |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 2] Simple Product 03 Mandatory Item [piece x 3] Simple Product 04 - Disabled | 1 pc               | $100.00      |

  Scenario: Check the line items with a missing and disabled products kit item on the Request edit page
    When I click "Edit"
    Then "Request Form" must contains values:
      | Line Item 2 Product         | product-kit-01 - Product Kit 01                  |
      | Line Item 2 Quantity        | 1                                                |
      | Line Item 2 Unit            | pc                                               |
      | Line Item 2 Target Price    | 104.69                                           |
      | Line Item 2 Item 1 Product  | simple-product-03 - Simple Product 03            |
      | Line Item 2 Item 1 Quantity | 2                                                |
      | Line Item 2 Item 2 Product  | simple-product-05 - Simple Product 05 - Deleted  |
      | Line Item 2 Item 2 Quantity | 3                                                |
      | Line Item 3 Product         | product-kit-01 - Product Kit 01                  |
      | Line Item 3 Quantity        | 1                                                |
      | Line Item 3 Unit            | pc                                               |
      | Line Item 3 Target Price    | 100.00                                           |
      | Line Item 3 Item 1 Product  | simple-product-03 - Simple Product 03            |
      | Line Item 3 Item 1 Quantity | 2                                                |
      | Line Item 3 Item 2 Product  | simple-product-04 - Simple Product 04 - Disabled |
      | Line Item 3 Item 2 Quantity | 3                                                |
    And I should see the following options for "Line Item 2 Item 2 Product" select in form "Request Form":
      | simple-product-05 - Simple Product 05 - Deleted |
      | simple-product-01 - Simple Product 01           |
      | simple-product-02 - Simple Product 02           |
    And I should see the "Request Product Kit Item Line Item Product Ghost Option 1" element in "Line Item 2 Item 2 Product" select in form "Request Form"
    And I should see the following options for "Line Item 3 Item 2 Product" select in form "Request Form":
      | simple-product-04 - Simple Product 04 - Disabled |
      | simple-product-01 - Simple Product 01            |
      | simple-product-02 - Simple Product 02            |
    And I should see the "Request Product Kit Item Line Item Product Ghost Option 1" element in "Line Item 3 Item 2 Product" select in form "Request Form"

  Scenario: Check the validation error for kit item line item with a missing product
    When I save form
    Then I should see "Request Form" validation errors:
      | Line Item 2 Item 2 Product | Original selection no longer available |

  Scenario: Check the validation error for kit item line item with a disabled product
    When fill "Request Form" with:
      | Line Item 2 Item 2 Product | simple-product-02 - Simple Product 02 |
    And I save form
    Then I should see "Request Form" validation errors:
      | Line Item 3 Item 2 Product | The selected product is not enabled |

  Scenario: Save Request for Quote and check the view page
    When fill "Request Form" with:
      | Line Item 3 Item 2 Product | simple-product-02 - Simple Product 02 |
    And I save and close form
    Then I should see "Request has been saved" flash message
    And I should see next rows in "Request Line Items Table" table
      | SKU               | Product                                                                                                 | Requested Quantity | Target Price |
      | simple-product-01 | Simple Product 01                                                                                       | 1 pc               | $2.00        |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 2] Simple Product 03 Mandatory Item [piece x 1] Simple Product 02 | 1 pc               | $104.69      |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 2] Simple Product 03 Mandatory Item [piece x 1] Simple Product 02 | 1 pc               | $100.00      |
