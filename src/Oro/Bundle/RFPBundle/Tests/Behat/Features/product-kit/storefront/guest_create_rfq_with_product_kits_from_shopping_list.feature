@feature-BB-22730
@fixture-OroRFPBundle:product-kit/storefront/rfq_with_product_kits__product.yml

Feature: Guest create RFQ with Product Kits from Shopping List

  Scenario: Feature background
    Given sessions active:
      | Buyer | first_session  |
      | Admin | second_session |

  Scenario: Enable guest RFQ
    Given I proceed as the Admin
    And login as administrator
    When I go to System/Configuration
    And I follow "Commerce/Sales/Request For Quote" on configuration sidebar
    And uncheck "Use default" for "Enable Guest RFQ" field
    And I check "Enable Guest RFQ"
    And I save setting
    Then I should see "Configuration saved" flash message
    When I go to System/Configuration
    And I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And uncheck "Use default" for "Enable Guest Shopping List" field
    And I check "Enable Guest Shopping List"
    And I save setting
    Then I should see "Configuration saved" flash message

  Scenario: Add product kit to shopping list
    Given I proceed as the Buyer
    And I am on the homepage
    When I type "product-kit-01" in "search"
    And I click "Search Button"
    And I click "View Details" for "Product Kit 01" product
    Then I should see an "Configure and Add to Shopping List" element
    When I click "Configure and Add to Shopping List"
    Then I should see "Product Kit Dialog" with elements:
      | Title                | Product Kit 01                       |
      | Kit Item 1 Name      | Optional Item                        |
      | Kit Item 2 Name      | Mandatory Item                       |
      | Kit Item 1 Product 1 | simple-product-03 Product 03 $3.7035 |
      | Kit Item 1 Product 2 | None                                 |
      | Kit Item 2 Product 1 | simple-product-01 Product 01 $1.2345 |
      | Kit Item 2 Product 2 | simple-product-02 Product 02 $2.469  |
      | Price                | Total: $124.69         |
    And "Product Kit Line Item Form" must contain values:
      | Readonly Kit Item Line Item 1 Quantity |   |
      | Kit Item Line Item 2 Quantity          | 1 |
    When I click "Add to Shopping List" in "Shopping List Button Group in Dialog" element
    Then I should see 'Product kit has been added to \"Shopping List\"' flash message

  Scenario: Add another product kit to shopping list
    When I click "Configure and Add to Shopping List"
    And I click "Kit Item Line Item 1 Product 1"
    And I click "Kit Item Line Item 2 Product 2"
    Then I should see "Product Kit Dialog" with elements:
      | Price | Total: $129.63 |
    When I click "Add to Shopping List" in "Shopping List Button Group in Dialog" element
    Then I should see 'Product kit has been added to \"Shopping List\"' flash message

  Scenario: Check Request Quote page
    When I follow "Shopping List" link within flash message "Product kit has been added to \"Shopping list\""
    And click "Request Quote"
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
      | First Name    | Tester                |
      | Last Name     | Testerson             |
      | Email Address | testerson@example.com |
      | Phone Number  | 72 669 62 82          |
      | Company       | Red Fox Tavern        |
      | Role          | CEO                   |
      | PO Number     | PO013                 |
    And I click "Submit Request"
    Then I should see "Request has been saved" flash message
    And I should see "Thank You For Your Request!"
    And email with Subject "Your RFQ has been received." containing the following was sent:
      | To      | testerson@example.com                                                                                                      |
      | Body    | Please see the details of your quote request below                                                                         |
      | Body    | Company: Red Fox Tavern                                                                                                    |
      | Body    | Role: CEO                                                                                                                  |
      | Body    | Phone: 72 669 62 82                                                                                                        |
      | Body    | Email: testerson@example.com                                                                                               |
      | Body    | product-kit-01 Product Kit 01 Mandatory Item 1 pc Simple Product 01 1 piece $124.00                                        |
      | Body    | product-kit-01 Product Kit 01 Optional Item 2 pcs Simple Product 03 Mandatory Item 2 pcs Simple Product 02 1 piece $135.00 |

  Scenario: Check RFQ with Product Kits in the admin area
    Given I proceed as the Admin
    When I go to Sales / Requests For Quote
    Then I should see following grid:
      | Submitted By     | Internal Status | PO Number |
      | Tester Testerson | Open            | PO013     |
    When click view "PO013" in grid
    Then I should see next rows in "Request Line Items Table" table
      | SKU               | Product                                                                                                 | Requested Quantity | Target Price |
      | product-kit-01    | Product Kit 01 Mandatory Item [piece x 1] Simple Product 01                                             | 1 pc               | $124.00      |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 2] Simple Product 03 Mandatory Item [piece x 2] Simple Product 02 | 1 pc               | $130.00      |
