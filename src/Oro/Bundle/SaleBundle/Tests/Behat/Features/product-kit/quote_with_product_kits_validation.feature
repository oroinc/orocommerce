@regression
@feature-BB-22730
@fixture-OroSaleBundle:product-kit/quote_with_product_kits_validation.yml

Feature: Quote with Product Kits Validation

  Scenario: Add a product kit line item
    Given I login as administrator
    When I go to Sales / Quotes
    Then I should see following grid:
      | Customer User | Internal Status | PO Number |
      | Amanda Cole   | Draft           | PO013     |
    When click edit "PO013" in grid
    And click "Backend Quote Add Product Button"
    And fill "Quote Form" with:
      | Line Item 2 Product        | product-kit-01                        |
      | Line Item 2 Quantity       | 1                                     |
      | Line Item 2 Unit           | piece                                 |
#   TODO: Should be uncommented after implementation BB-23120 feature
#      | Line Item 2 Price    | 104.69         |
      | Line Item 2 Item 1 Product | simple-product-03 - Simple Product 03 |
      | Line Item 2 Item 2 Product | simple-product-01 - Simple Product 01 |
    Then "Quote Form" must contains values:
      | Line Item 2 Item 1 Quantity | 2 |
      | Line Item 2 Item 2 Quantity | 3 |
    And the "LineItemPrice2" field should be readonly in form "Quote Form"

  Scenario Outline: Set product kit line item quantity with violation (without page reload)
    When fill "Quote Form" with:
      | Line Item 2 Item 1 Quantity | <Item 1 Quantity> |
      | Line Item 2 Item 2 Quantity | <Item 2 Quantity> |
    Then I should see "Quote Form" validation errors:
      | Line Item 2 Item 1 Quantity | <Item 1 Quantity validation message> |
      | Line Item 2 Item 2 Quantity | <Item 2 Quantity validation message> |
    And the "LineItemPrice2" field should be readonly in form "Quote Form"

    Examples:
      | Item 1 Quantity | Item 2 Quantity | Item 1 Quantity validation message     | Item 2 Quantity validation message      |
      | invalid         | invalid         | The quantity must be a decimal number  | The quantity must be a decimal number   |
      |                 |                 | The quantity should be greater than 0  | The quantity should be greater than 0   |
      | 0               | 0               | The quantity should be greater than 0  | The quantity should be greater than 0   |
      | 6               | 11              | The quantity should be between 2 and 5 | The quantity should be between 3 and 10 |
      | 1               | 2               | The quantity should be between 2 and 5 | The quantity should be between 3 and 10 |

  Scenario Outline: Set product kit line item quantity with violation (with page reload)
    When fill "Quote Form" with:
      | Line Item 2 Item 1 Quantity | <Item 1 Quantity> |
      | Line Item 2 Item 2 Quantity | <Item 2 Quantity> |
    And I wait for 1 seconds
    And I click "Submit"
    And agree that shipping cost may have changed
    And I focus on "Quote Form Line Item 2 Kit Item 2 Label"
    Then I should see "Quote Form" validation errors:
      | Line Item 2 Item 1 Quantity | <Item 1 Quantity validation message> |
      | Line Item 2 Item 2 Quantity | <Item 2 Quantity validation message> |
    And the "LineItemPrice2" field should be readonly in form "Quote Form"

    Examples:
      | Item 1 Quantity | Item 2 Quantity | Item 1 Quantity validation message                | Item 2 Quantity validation message              |
      | 2.45            | 3.34            | Only 1 decimal digit are allowed for unit "piece" | Only whole numbers are allowed for unit "piece" |

  Scenario: Check unit precisions in the Quantity tooltip
    When I click on "Quote Form Line Item 2 Kit Item 1 Quantity Label Tooltip"
    Then I should see "The quantity of product kit item units to be purchased: piece (fractional, 1 decimal digit)" in the "Tooltip Popover Content" element
    When I click on "Quote Form Line Item 2 Kit Item 2 Quantity Label Tooltip"
    Then I should see "The quantity of product kit item units to be purchased: piece (whole numbers)" in the "Tooltip Popover Content" element
