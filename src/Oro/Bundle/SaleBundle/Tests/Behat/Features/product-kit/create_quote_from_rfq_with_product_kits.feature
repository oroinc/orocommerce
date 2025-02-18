@regression
@feature-BB-22730
@feature-BB-24496
@fixture-OroSaleBundle:product-kit/existing_quote_with_product_kits_validation__product.yml
@fixture-OroSaleBundle:product-kit/create_quote_from_rfq_with_product_kits__rfq.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroCheckoutBundle:Shipping.yml

Feature: Create Quote from RFQ with Product Kits

  Scenario: Feature Background
    Given I login as administrator
    When I go to Sales / Requests For Quote
    Then I should see following grid:
      | Submitted By | Internal Status | PO Number |
      | Amanda Cole  | Open            | PO013     |
    When click view "PO013" in grid
    Then I should see next rows in "Request Line Items Table" table
      | SKU               | Product                                                                                                 | Requested Quantity | Target Price |
      | simple-product-01 | Simple Product 01                                                                                       | 1 pc               | $2.00        |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 2] Simple Product 03 Mandatory Item [piece x 3] Simple Product 01 | 1 pc               | $104.69      |

  Scenario: Create Quote
    When I click "Create Quote"
    Then "Quote Form" must contains values:
      | Customer                    | Customer1                             |
      | Customer User               | Amanda Cole                           |
      | LineItemProduct             | simple-product-01 - Simple Product 01 |
      | LineItemQuantity            | 1                                     |
      | LineItemPrice               | 2.0000                                |
      | Line Item 2 Product         | product-kit-01 - Product Kit 01       |
      | Line Item 2 Quantity        | 1                                     |
      | Line Item 2 Price           | 134.5667                              |
      | Line Item 2 Item 1 Product  | simple-product-03 - Simple Product 03 |
      | Line Item 2 Item 1 Quantity | 2                                     |
      | Line Item 2 Item 1 Price    | 3.70                                  |
      | Line Item 2 Item 2 Product  | simple-product-01 - Simple Product 01 |
      | Line Item 2 Item 2 Quantity | 3                                     |
      | Line Item 2 Item 2 Price    | 1.23                                  |
    And I should not see an "Line Item 2 Add Offer Button" element
    And I should see "Line Item 2 Offer 1 Remove Button" button disabled
    And the "LineItemPrice2" field should be readonly in form "Quote Form"

  Scenario: Check that Quote can be saved
    When I save and close form
    Then I should see "Quote has been saved" flash message
    And I should see Quote with:
      | Customer        | Customer1   |
      | Customer User   | Amanda Cole |
      | PO Number       | PO013       |
      | Internal Status | Draft       |
    And I should see next rows in "Quote Line Items Table" table
      | SKU               | Product                                                                                                 | Quantity     | Price     |
      | simple-product-01 | Simple Product 01                                                                                       | 1 pc or more | $2.00     |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 2] Simple Product 03 Mandatory Item [piece x 3] Simple Product 01 | 1 pc or more | $134.5667 |
