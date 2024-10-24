@feature-BB-22730
@fixture-OroRFPBundle:product-kit/storefront/rfq_with_product_kits__product.yml
@fixture-OroRFPBundle:product-kit/storefront/rfq_with_product_kits__shopping_list.yml

Feature: Create RFQ with Product Kits from Shopping List

  Scenario: Feature background
    Given sessions active:
      | Buyer | first_session  |
      | Admin | second_session |

  Scenario: Add product kit to shopping list
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list "Product Kit Shopping List"
    Then I should see following grid:
      | SKU               | Item                              |          | Qty Update All | Price     | Subtotal  |
      | product-kit-01    | Product Kit 01                    | In Stock | 1 piece        | $124.6867 | $124.69   |
      | simple-product-01 | Mandatory Item: Simple Product 01 |          | 1 piece        | $1.2345   |           |

      | product-kit-01    | Product Kit 01                    | In Stock | 1 piece        | $129.6267 | $129.63   |
      | simple-product-03 | Optional Item: Simple Product 03  |          | 1 piece        | $3.7035   |           |
      | simple-product-02 | Mandatory Item: Simple Product 02 |          | 1 piece        | $2.469    |           |
    And I should see "Summary 2 Items"
    And I should see "Subtotal $254.32"
    And I should see "Total $254.32"

  Scenario: Check Request Quote page
    And I click "Request Quote"
    Then I should see "product-kit-01 - Product Kit 01 Mandatory Item 1 piece simple-product-01 - Simple Product 01 QTY: 1 piece Target Price $0.00 Listed Price: $124.6867" in the "RFQ Products List Line Item 1" element
    And I should see "product-kit-01 - Product Kit 01 Optional Item 1 piece simple-product-03 - Simple Product 03 Mandatory Item 1 piece simple-product-02 - Simple Product 02 QTY: 1 piece Target Price $0.00 Listed Price: $129.6267" in the "RFQ Products List Line Item 2" element

  Scenario: Update Product Kit configuration
    When click on "Edit Request Product Line Item 2"
    And click on "RFQ Kit Item Line Item 2 Configure Button"
    And I fill "RFQ Product Kit Line Item Form" with:
      | Kit Item Line Item 1 Quantity | 2 |
      | Kit Item Line Item 2 Quantity | 2 |
    Then I should see "Product Kit Dialog" with elements:
      | Price | Total: $135.8067 |
    When I click "Save"
    And click "Update Line Item"
    Then I should see "product-kit-01 - Product Kit 01 Optional Item 2 piece simple-product-03 - Simple Product 03 Mandatory Item 2 piece simple-product-02 - Simple Product 02 QTY: 1 piece Target Price $0.00 Listed Price: $135.8067" in the "RFQ Products List Line Item 2" element

  Scenario: Update Target Price
    When click on "Edit Request Product Line Item"
    And fill "Frontstore RFQ Line Item Form1" with:
      | Target Price | 124 |
    And click "Update Line Item"
    And click on "Edit Request Product Line Item 2"
    And fill "Frontstore RFQ Line Item Form2" with:
      | Target Price | 130 |
    And click "Update Line Item"
    Then I should see "product-kit-01 - Product Kit 01 Mandatory Item 1 piece simple-product-01 - Simple Product 01 QTY: 1 piece Target Price $124.00 Listed Price: $124.6867" in the "RFQ Products List Line Item 1" element
    And I should see "product-kit-01 - Product Kit 01 Optional Item 2 piece simple-product-03 - Simple Product 03 Mandatory Item 2 piece simple-product-02 - Simple Product 02 QTY: 1 piece Target Price $130.00 Listed Price: $135.8067" in the "RFQ Products List Line Item 2" element

  Scenario: Create RFQ with Product Kits
    When I fill form with:
      | PO Number | PO013 |
    And I click "Submit Request"
    Then I should see "Request has been saved" flash message
    And email with Subject "Your RFQ has been received." containing the following was sent:
      | To      | AmandaRCole@example.org                                                                                                    |
      | Body    | Please see the details of your quote request below                                                                         |
      | Body    | Phone: 72 669 62 82                                                                                                        |
      | Body    | Email: AmandaRCole@example.org                                                                                             |
      | Body    | product-kit-01 Product Kit 01 Mandatory Item 1 pc Simple Product 01 1 piece $124.00                                        |
      | Body    | product-kit-01 Product Kit 01 Optional Item 2 pcs Simple Product 03 Mandatory Item 2 pcs Simple Product 02 1 piece $135.00 |

  Scenario: Check submitted request
    Then I should see RFQ with data:
      | Contact Person    | Amanda Cole             |
      | Email Address     | AmandaRCole@example.org |
      | PO Number         | PO013                   |
    And I should see next rows in "Storefront Request Line Items Table" table
      | Item                                                                                                                     | Requested Quantity | Target Price |
      | Product Kit 01 Item #: product-kit-01 Mandatory Item 1 piece Simple Product 01                                           | 1 pc               | $124.00      |
      | Product Kit 01 Item #: product-kit-01 Optional Item 2 pieces Simple Product 03 Mandatory Item 2 pieces Simple Product 02 | 1 pc               | $130.00      |

  Scenario: Check RFQ with Product Kits in the admin area
    Given I proceed as the Admin
    And login as administrator
    When I go to Sales / Requests For Quote
    Then I should see following grid:
      | Submitted By | Internal Status | PO Number |
      | Amanda Cole  | Open            | PO013     |
    When click view "PO013" in grid
    Then I should see next rows in "Request Line Items Table" table
      | SKU               | Product                                                                                                 | Requested Quantity | Target Price |
      | product-kit-01    | Product Kit 01 Mandatory Item [piece x 1] Simple Product 01                                             | 1 pc               | $124.00      |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 2] Simple Product 03 Mandatory Item [piece x 2] Simple Product 02 | 1 pc               | $130.00      |
