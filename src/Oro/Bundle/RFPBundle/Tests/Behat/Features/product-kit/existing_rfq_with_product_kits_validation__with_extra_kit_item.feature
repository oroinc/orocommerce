@regression
@feature-BB-22730
@fixture-OroRFPBundle:product-kit/existing_rfq_with_product_kits_validation__product.yml
@fixture-OroRFPBundle:product-kit/existing_rfq_with_product_kits_validation__with_extra_kit_item__rfq.yml

Feature: Existing RFQ with Product Kits Validation - with Extra Kit Item

  Scenario: Remove kit item
    Given I login as administrator
    When go to Products/ Products
    And click edit "product-kit-01" in grid
    And I click "Kit Item 2 Remove Button"
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check the line item with an extra mandatory kit item
    When I go to Sales / Requests For Quote
    Then I should see following grid:
      | Submitted By | Internal Status | PO Number |
      | Amanda Cole  | Open            | PO013     |
    When click edit "PO013" in grid
    Then "Request Form" must contains values:
      | Line Item 2 Product         | product-kit-01 - Product Kit 01       |
      | Line Item 2 Quantity        | 1                                     |
      | Line Item 2 Unit            | pc                                    |
      | Line Item 2 Target Price    | 104.69                                |
      | Line Item 2 Item 1 Product  | simple-product-03 - Simple Product 03 |
      | Line Item 2 Item 1 Quantity | 2                                     |
      | Line Item 2 Item 2 Product  | simple-product-01 - Simple Product 01 |
      | Line Item 2 Item 2 Quantity | 3                                     |
    And I should see the following options for "Line Item 2 Item 1 Product" select in form "Request Form":
      | simple-product-03 - Simple Product 03 |
    And I should see the following options for "Line Item 2 Item 2 Product" select in form "Request Form":
      | simple-product-01 - Simple Product 01 |
    And I should not see the following options for "Line Item 2 Item 2 Product" select in form "Request Form":
      | simple-product-02 - Simple Product 02 |
    And I should see the "Request Product Kit Item Line Item Product Ghost Option 1" element in "Line Item 2 Item 2 Product" select in form "Request Form"
    And I should see "Optional Item" in the "Request Form Line Item 2 Kit Item 1 Label" element
    And I should not see "Mandatory Item *" in the "Request Form Line Item 2 Kit Item 2 Label" element
    And I should see "Mandatory Item" in the "Request Form Line Item 2 Kit Item 2 Label" element

  Scenario: Check the min/max quantity validation error for an extra mandatory kit item
    When fill "Request Form" with:
      | Line Item 2 Item 2 Quantity | 11 |
    And I save form
    Then I should see "Request Form" validation errors:
      | Line Item 2 Item 2 Product | Original selection no longer available |
    And I should not see "Request Form" validation errors:
      | Line Item 2 Item 2 Quantity | The quantity should be between 1 and 10 |

  Scenario: Remove extra mandatory kit item line item
    When I clear "Line Item 2 Item 2 Product" field in form "Request Form"
    Then the "Line Item 2 Item 2 Quantity" field should be disabled in form "Request Form"
    When save and close form
    Then I should see "Request has been saved" flash message
    And I should see next rows in "Request Line Items Table" table
      | SKU               | Product                                                    | Requested Quantity | Target Price |
      | simple-product-01 | Simple Product 01                                          | 1 pc               | $2.00        |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 2] Simple Product 03 | 1 pc               | $104.69      |
