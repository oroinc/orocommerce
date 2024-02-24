@regression
@feature-BB-22730
@fixture-OroRFPBundle:product-kit/storefront/rfq_with_product_kits__product.yml

Feature: Create RFQ with Product Kits Validation - with Different Unit

  Scenario: Feature background
    Given sessions active:
      | Buyer | first_session  |
      | Admin | second_session |

  Scenario: Create RFQ from scratch
    Given I continue as the Buyer
    When I signed in as AmandaRCole@example.org on the store frontend
    And I click "Account Dropdown"
    And I click "Requests For Quote"
    And I click "New Quote"
    And fill form with:
      | PO Number | PO013 |
    And fill "Frontend Request Form" with:
      | Line Item Product | product-kit-01 - Product Kit 01 |
    Then I should see "Product Kit Dialog" with elements:
      | Title                | Product Kit 01                       |
      | Kit Item 1 Name      | Optional Item                        |
      | Kit Item 2 Name      | Mandatory Item                       |
      | Kit Item 1 Product 1 | simple-product-03 Product 03 $3.7035 |
      | Kit Item 1 Product 2 | None                                 |
      | Kit Item 2 Product 1 | simple-product-01 Product 01 $1.2345 |
      | Kit Item 2 Product 2 | simple-product-02 Product 02 $2.469  |
      | okButton             | Save                                 |

  Scenario: Add Product Kit Line Item to the RFQ
    When click "Save"
    Then I should see "product-kit-01 - Product Kit 01 Mandatory Item 1 piece simple-product-01 - Simple Product 01" in the "RFQ Products List Line Item 1" element
    When fill "Frontstore RFQ Line Item Form1" with:
      | Quantity | 1 |
    And click "Update Line Item"
    Then I should see "product-kit-01 - Product Kit 01 Mandatory Item 1 piece simple-product-01 - Simple Product 01 QTY: 1 piece" in the "RFQ Products List Line Item 1" element

  Scenario: Update Product Kit unit
    Given I proceed as the Admin
    When I login as administrator
    And go to Products / Products
    And click "Edit" on row "product-kit-01" in grid
    And I fill "ProductForm" with:
      | PrimaryUnit  | each |
      | Price Unit 1 | each |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Try to submit the Request
    Given I continue as the Buyer
    When I click "Submit Request"
    Then I should not see "Request has been saved" flash message
    And I should see "Frontstore RFQ Line Item Form1" validation errors:
      | Unit | The product unit does not exist for the product. |

  Scenario: Try to submit the Request (Unit is automatically updated to each)
    When click "Update Line Item"
    Then I should see "product-kit-01 - Product Kit 01 Mandatory Item 1 piece simple-product-01 - Simple Product 01" in the "RFQ Products List Line Item 1" element
    When I click "Submit Request"
    Then I should see "Request has been saved" flash message

  Scenario: Check RFQ with Product Kits in the admin area
    Given I proceed as the Admin
    When I go to Sales / Requests For Quote
    Then I should see following grid:
      | Submitted By | Internal Status | PO Number |
      | Amanda Cole  | Open            | PO013     |
    When click view "PO013" in grid
    Then I should see next rows in "Request Line Items Table" table
      | SKU            | Product                                                     | Requested Quantity |
      | product-kit-01 | Product Kit 01 Mandatory Item [piece x 1] Simple Product 01 | 1 ea               |
