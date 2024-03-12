@feature-BB-22730
@fixture-OroRFPBundle:product-kit/update_rfq_with_product_kits.yml

Feature: Update RFQ with Product Kits

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
      | Line Item 2 Product      | product-kit-01 |
      | Line Item 2 Quantity     | 1              |
      | Line Item 2 Unit         | piece          |
      | Line Item 2 Target Price | 104.69         |
    Then I should see "Optional Item" in the "Request Form Line Item 2 Kit Item 1 Label" element
    And I should see "Mandatory Item *" in the "Request Form Line Item 2 Kit Item 2 Label" element
    And the "Line Item 2 Item 1 Quantity" field should be disabled in form "Request Form"
    And "Request Form" must contains values:
      | Line Item 2 Product         | product-kit-01 - Product Kit 01       |
      | Line Item 2 Quantity        | 1                                     |
      | Line Item 2 Target Price    | 104.69                                |
      | Line Item 2 Item 1 Product  | None                                  |
      | Line Item 2 Item 1 Quantity |                                       |
      | Line Item 2 Item 2 Product  | simple-product-01 - Simple Product 01 |
      | Line Item 2 Item 2 Quantity | 1                                     |
    And I should see the following options for "Line Item 2 Item 1 Product" select in form "Request Form":
      | simple-product-03 - Simple Product 03 |
    And I should see the following options for "Line Item 2 Item 2 Product" select in form "Request Form":
      | simple-product-01 - Simple Product 01 |
      | simple-product-02 - Simple Product 02 |
    When I click on "Request Form Line Item 2 Kit Item 1 Quantity Label Tooltip"
    Then I should see "The quantity of product kit item units to be purchased: piece (whole numbers)" in the "Tooltip Popover Content" element
    And I click on empty space

  Scenario: Add one more product kit line item via the entity select popup
    When click "Add Another Product"
    And I open select entity popup for field "Line Item 3 Product Dropdown" in form "Request Form"
    And I sort grid by "SKU"
    Then I should see following grid:
      | SKU               | Name              |
      | product-kit-01    | Product Kit 01    |
      | simple-product-01 | Simple Product 01 |
      | simple-product-02 | Simple Product 02 |
      | simple-product-03 | Simple Product 03 |
    When I click on product-kit-01 in grid
    And I click "Line Item 3 Add Another Line"
    And fill "Request Form" with:
      | Line Item 3 Quantity        | 2                                     |
      | Line Item 3 Unit            | piece                                 |
      | Line Item 3 Target Price    | 106.94                                |
      | Line Item 3 Item 1 Product  | simple-product-03 - Simple Product 03 |
      | Line Item 3 Item 1 Quantity | 3                                     |
    Then "Request Form" must contains values:
      | Line Item 3 Product         | product-kit-01 - Product Kit 01       |
      | Line Item 3 Quantity        | 2                                     |
      | Line Item 3 Target Price    | 106.94                                |
      | Line Item 3 Item 1 Product  | simple-product-03 - Simple Product 03 |
      | Line Item 3 Item 1 Quantity | 3                                     |
      | Line Item 3 Item 2 Product  | simple-product-01 - Simple Product 01 |
      | Line Item 3 Item 2 Quantity | 1                                     |

  Scenario: Save Request for Quote and check the view page
    When save and close form
    Then should see "Request has been saved" flash message
    And I should see Request with:
      | Submitted By    | Amanda Cole |
      | PO Number       | PO013       |
      | Internal Status | Open        |
    And I should see next rows in "Request Line Items Table" table
      | SKU               | Product                                                                                                 | Requested Quantity | Target Price |
      | simple-product-01 | Simple Product 01                                                                                       | 1 pc               | $2.00        |
      | product-kit-01    | Product Kit 01 Mandatory Item [piece x 1] Simple Product 01                                             | 1 pc               | $104.69      |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 3] Simple Product 03 Mandatory Item [piece x 1] Simple Product 01 | 2 pcs              | $106.94      |

  Scenario: Change product of a kit item line item
    When I click "Edit"
    And I fill "Request Form" with:
      | Line Item 2 Item 2 Product | simple-product-02 - Simple Product 02 |
    Then "Request Form" must contains values:
      | Line Item 2 Item 2 Quantity | 1 |
    When save and close form
    Then I should see "Request has been saved" flash message
    And I should see next rows in "Request Line Items Table" table
      | SKU               | Product                                                                                                 | Requested Quantity | Target Price |
      | simple-product-01 | Simple Product 01                                                                                       | 1 pc               | $2.00        |
      | product-kit-01    | Product Kit 01 Mandatory Item [piece x 1] Simple Product 02                                             | 1 pc               | $104.69      |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 3] Simple Product 03 Mandatory Item [piece x 1] Simple Product 01 | 2 pcs              | $106.94      |

  Scenario: Change quantity of a kit item line item
    When I click "Edit"
    And I fill "Request Form" with:
      | Line Item 2 Item 2 Quantity | 2 |
    Then "Request Form" must contains values:
      | Line Item 2 Item 2 Quantity | 2 |
    When save and close form
    Then I should see "Request has been saved" flash message
    And I should see next rows in "Request Line Items Table" table
      | SKU               | Product                                                                                                 | Requested Quantity | Target Price |
      | simple-product-01 | Simple Product 01                                                                                       | 1 pc               | $2.00        |
      | product-kit-01    | Product Kit 01 Mandatory Item [piece x 2] Simple Product 02                                             | 1 pc               | $104.69      |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 3] Simple Product 03 Mandatory Item [piece x 1] Simple Product 01 | 2 pcs              | $106.94      |

  Scenario: Remove optional kit item line item
    When I click "Edit"
    And I clear "Line Item 3 Item 1 Product" field in form "Request Form"
    Then the "Line Item 3 Item 1 Quantity" field should be disabled in form "Request Form"
    When save and close form
    Then I should see "Request has been saved" flash message
    And I should see next rows in "Request Line Items Table" table
      | SKU               | Product                                                     | Requested Quantity | Target Price |
      | simple-product-01 | Simple Product 01                                           | 1 pc               | $2.00        |
      | product-kit-01    | Product Kit 01 Mandatory Item [piece x 2] Simple Product 02 | 1 pc               | $104.69      |
      | product-kit-01    | Product Kit 01 Mandatory Item [piece x 1] Simple Product 01 | 2 pcs              | $106.94      |

  Scenario: Remove a line item
    When I click "Edit"
    And I click on "Request Form Line Item 3 Remove"
    And save and close form
    Then I should see "Request has been saved" flash message
    And I should see next rows in "Request Line Items Table" table
      | SKU               | Product                                                     | Requested Quantity | Target Price |
      | simple-product-01 | Simple Product 01                                           | 1 pc               | $2.00        |
      | product-kit-01    | Product Kit 01 Mandatory Item [piece x 2] Simple Product 02 | 1 pc               | $104.69      |
