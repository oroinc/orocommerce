@feature-BB-22730
@fixture-OroRFPBundle:product-kit/storefront/rfq_with_product_kits__product.yml

Feature: Create RFQ with Product Kits

  Scenario: Feature background
    Given sessions active:
      | Buyer | first_session  |
      | Admin | second_session |

  Scenario: Create RFQ from scratch
    Given I continue as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I click "Account Dropdown"
    And I click "Requests For Quote"
    And I click "New Quote"
    And fill form with:
      | PO Number | PO013 |

  Scenario: Check Product Kit in the Product select dialog
    When I open select entity popup for field "Line Item Product" in form "Frontend Request Form"
    And I sort grid by "SKU"
    Then I should see following grid:
      | SKU               | Name              |
      | product-kit-01    | Product Kit 01    |
      | simple-product-01 | Simple Product 01 |
      | simple-product-02 | Simple Product 02 |
      | simple-product-03 | Simple Product 03 |

  Scenario: Check Product Kit configuration dialog
    When I click on product-kit-01 in grid
    Then I should see "Product Kit Dialog" with elements:
      | Title                | Product Kit 01                       |
      | Kit Item 1 Name      | Optional Item                        |
      | Kit Item 2 Name      | Mandatory Item                       |
      | Kit Item 1 Product 1 | simple-product-03 Product 03 $3.7035 |
      | Kit Item 1 Product 2 | None                                 |
      | Kit Item 2 Product 1 | simple-product-01 Product 01 $1.2345 |
      | Kit Item 2 Product 2 | simple-product-02 Product 02 $2.469  |
      | Price                | Price as configured: $124.6867       |
      | okButton             | Save                                 |
    And "RFQ Product Kit Line Item Form" must contain values:
      | Readonly Kit Item Line Item 1 Quantity |   |
      | Kit Item Line Item 2 Quantity          | 1 |
    And "RFQ Product Kit Line Item Totals Form" must contain values:
      | Readonly Quantity | 1     |
      | Readonly Unit     | piece |

  Scenario: Add Product Kit to RFQ
    When I click "Save"
    And fill "Frontstore RFQ Line Item Form1" with:
      | Target Price | 124 |
    And click "Update Line Item"
    Then I should see "product-kit-01 - Product Kit 01 Mandatory Item 1 piece simple-product-01 - Simple Product 01 QTY: 1 piece Target Price $124.00 Listed Price: $124.6867" in the "RFQ Products List Line Item 1" element

  Scenario: Add another Product Kit configuration to RFQ
    When I click "Add Another Product"
    And fill "Frontend Request Form" with:
      | Line Item 2 Product | product-kit-01 - Product Kit 01 |
    Then I should see "Product Kit Dialog" with elements:
      | Title                | Product Kit 01                       |
      | Kit Item 1 Name      | Optional Item                        |
      | Kit Item 2 Name      | Mandatory Item                       |
      | Kit Item 1 Product 1 | simple-product-03 Product 03 $3.7035 |
      | Kit Item 1 Product 2 | None                                 |
      | Kit Item 2 Product 1 | simple-product-01 Product 01 $1.2345 |
      | Kit Item 2 Product 2 | simple-product-02 Product 02 $2.469  |
      | Price                | Price as configured: $124.6867       |
      | okButton             | Save                                 |
    And "RFQ Product Kit Line Item Form" must contain values:
      | Readonly Kit Item Line Item 1 Quantity |   |
      | Kit Item Line Item 2 Quantity          | 1 |
    And "RFQ Product Kit Line Item Totals Form" must contain values:
      | Readonly Quantity | 1     |
      | Readonly Unit     | piece |

    When I click "RFQ Kit Item Line Item 1 Product 1"
    Then "RFQ Product Kit Line Item Form" must contain values:
      | Kit Item Line Item 1 Quantity | 1 |
    And I should see "Product Kit Dialog" with elements:
      | Price | Price as configured: $128.3867 |
    When I click "RFQ Kit Item Line Item 2 Product 2"
    Then I should see "Product Kit Dialog" with elements:
      | Price | Price as configured: $129.6267 |

    When I click "Save"
    And fill "Frontstore RFQ Line Item Form2" with:
      | Target Price | 130 |
    And click "Update Line Item"
    Then I should see "product-kit-01 - Product Kit 01 Optional Item 1 piece simple-product-03 - Simple Product 03 Mandatory Item 1 piece simple-product-02 - Simple Product 02 QTY: 1 piece Target Price $130.00 Listed Price: $129.6267" in the "RFQ Products List Line Item 2" element

    Scenario: Update Product Kit configuration
      When click on "Edit Request Product Line Item 2"
      And click on "RFQ Kit Item Line Item 2 Configure Button"
      And I fill "RFQ Product Kit Line Item Form" with:
        | Kit Item Line Item 1 Quantity | 2 |
        | Kit Item Line Item 2 Quantity | 2 |
      Then I should see "Product Kit Dialog" with elements:
        | Price | Price as configured: $135.8067 |
      When I click "Save"
      And click "Update Line Item"
      Then I should see "product-kit-01 - Product Kit 01 Optional Item 2 piece simple-product-03 - Simple Product 03 Mandatory Item 2 piece simple-product-02 - Simple Product 02 QTY: 1 piece Target Price $130.00 Listed Price: $135.8067" in the "RFQ Products List Line Item 2" element

  Scenario: Submit the Request
    When I click "Submit Request"
    Then I should see "Request has been saved" flash message
    And email with Subject "New RFP from Amanda Cole" containing the following was sent:
      | Body | Amanda Cole created new RFP |

  Scenario: Check submitted request
    Then I should see RFQ with data:
      | First Name    | Amanda                  |
      | Last Name     | Cole                    |
      | Email Address | AmandaRCole@example.org |
      | PO Number	  | PO013                   |
    And I should see next rows in "Storefront Request Line Items Table" table
      | Item                                                                                                                     | Requested Quantity | Target Price |
      | Product Kit 01 Item #: product-kit-01 Mandatory Item 1 piece Simple Product 01                                           | 1 pc               | $124.00      |
      | Product Kit 01 Item #: product-kit-01 Optional Item 2 pieces Simple Product 03 Mandatory Item 2 pieces Simple Product 02 | 1 pc               | $130.00      |

  Scenario: Check RFQ with Product Kits in the admin area
    Given I proceed as the Admin
    And I login as administrator
    When I go to Sales / Requests For Quote
    Then I should see following grid:
      | Submitted By | Internal Status | PO Number |
      | Amanda Cole  | Open            | PO013     |
    And click view "PO013" in grid
    Then I should see next rows in "Request Line Items Table" table
      | SKU               | Product                                                                                                 | Requested Quantity | Target Price |
      | product-kit-01    | Product Kit 01 Mandatory Item [piece x 1] Simple Product 01                                             | 1 pc               | $124.00      |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 2] Simple Product 03 Mandatory Item [piece x 2] Simple Product 02 | 1 pc               | $130.00      |
