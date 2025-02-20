@regression
@feature-BB-22730
@fixture-OroRFPBundle:product-kit/rfq_with_product_kits_validation.yml

Feature: RFQ with Product Kits Validation

  Scenario: Add a product kit line item
    Given I login as administrator
    When I go to Sales / Requests For Quote
    Then I should see following grid:
      | Submitted By | Internal Status | PO Number |
      | Amanda Cole  | Open            | PO013     |
    When click edit "PO013" in grid
    And click "Add Another Product"
    And I click "Line Item 2 Add Another Line"
    And fill "Request Form" with:
      | Line Item 2 Product         | product-kit-01                        |
      | Line Item 2 Quantity        | 1                                     |
      | Line Item 2 Unit            | piece                                 |
      | Line Item 2 Target Price    | 104.69                                |
      | Line Item 2 Item 1 Product  | simple-product-03 - Simple Product 03 |
      | Line Item 2 Item 2 Product  | simple-product-01 - Simple Product 01 |
    Then "Request Form" must contains values:
      | Line Item 2 Item 1 Quantity | 2 |
      | Line Item 2 Item 2 Quantity | 3 |
    And I should not see an "Line Item 2 Add Another Line" element

  Scenario Outline: Set product kit line item quantity with violation
    When fill "Request Form" with:
      | Line Item 2 Item 1 Quantity | <Item 1 Quantity> |
      | Line Item 2 Item 2 Quantity | <Item 2 Quantity> |
    And I save form
    And I focus on "Request Form Line Item 2 Kit Item 2 Label"
    Then I should see "Request Form" validation errors:
      | Line Item 2 Item 1 Quantity | <Item 1 Quantity validation message> |
      | Line Item 2 Item 2 Quantity | <Item 2 Quantity validation message> |

    Examples:
      | Item 1 Quantity | Item 2 Quantity | Item 1 Quantity validation message                | Item 2 Quantity validation message              |
      | invalid         | invalid         | The quantity must be a decimal number             | The quantity must be a decimal number           |
      |                 |                 | The quantity should be greater than 0             | The quantity should be greater than 0           |
      | 0               | 0               | The quantity should be greater than 0             | The quantity should be greater than 0           |
      | 6               | 11              | The quantity should be between 2 and 5            | The quantity should be between 3 and 10         |
      | 1               | 2               | The quantity should be between 2 and 5            | The quantity should be between 3 and 10         |
      | 2.45            | 3.34            | Only 1 decimal digit are allowed for unit "piece" | Only whole numbers are allowed for unit "piece" |

  Scenario: Check unit precisions in the Quantity tooltip
    When I click on "Request Form Line Item 2 Kit Item 1 Quantity Label Tooltip"
    Then I should see "The quantity of product kit item units to be purchased: piece (fractional, 1 decimal digit)" in the "Tooltip Popover Content" element
    And I click on empty space
    When I click on "Request Form Line Item 2 Kit Item 2 Quantity Label Tooltip"
    Then I should see "The quantity of product kit item units to be purchased: piece (whole numbers)" in the "Tooltip Popover Content" element
    And I click on empty space
