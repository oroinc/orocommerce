@regression
@feature-BB-22738
@fixture-OroProductBundle:quick_order_form_with_product_kits__product.yml

Feature: Get Order from Quick order form with Product Kits

  Scenario: Product Kit can be selected in Quick Order Form
    Given I login as AmandaRCole@example.org buyer
    When I click "Quick Order"
    And I fill "Quick Add Copy Paste Form" with:
      | Paste your order | product-kit-01 3\nsimple-product-01 2 |
    And I click "Verify Order"
    Then "Quick Order Form" must contains values:
      | SKU1      | PRODUCT-KIT-01 - Product Kit 01       |
      | QTY1      | 3                                     |
      | UNIT1     | piece                                 |
      | SUBTOTAL1 | N/A                                   |
      | SKU2      | SIMPLE-PRODUCT-01 - Simple Product 01 |
      | QTY2      | 2                                     |
      | UNIT2     | piece                                 |
      | SUBTOTAL2 | $2.469                                |
    And I should see a "Get Quote Button" element

  Scenario: Check Request Quote page
    When I click on "Get Quote Button"
    Then I should see "product-kit-01 - Product Kit 01 QTY: 3 piece Target Price $0.00 Listed Price: $123.4567" in the "RFQ Products List Line Item 1" element
    And I should see "simple-product-01 - Simple Product 01 QTY: 2 piece Target Price $0.00 Listed Price: $1.2345" in the "RFQ Products List Line Item 2" element

  Scenario: Try to submit the Request
    When I click "Submit Request"
    Then I should not see "Request has been saved" flash message
    And I should see "product-kit-01 - Product Kit 01 Mandatory Item 1 piece Selection Required The selected kit configuration is not valid. Please modify or remove it." in the "RFQ Products List Line Item 1" element
    And I should see "simple-product-01 - Simple Product 01 QTY: 2 piece Target Price $0.00 Listed Price: $1.2345" in the "RFQ Products List Line Item 2" element

  Scenario: Configure Product Kit
    When click on "RFQ Kit Item Line Item 1 Configure Button"
    Then I should see "Product Kit Dialog" with elements:
      | Title            | Product Kit 01           |
      | Kit Item 1 Name  | Optional Item            |
      | Kit Item 2 Name  | Mandatory Item           |
      | Error 1          | Selection required       |
      | Price            | Price as configured: N/A |
      | okButton         | Save                     |
    When I click "RFQ Kit Item Line Item 2 Product 1"
    And click "Save"
    And click "Update Line Item"
    Then I should see "product-kit-01 - Product Kit 01 Mandatory Item 1 piece simple-product-01 - Simple Product 01" in the "RFQ Products List Line Item 1" element
    And I should see "simple-product-01 - Simple Product 01 QTY: 2 piece Target Price $0.00 Listed Price: $1.2345" in the "RFQ Products List Line Item 2" element

  Scenario: Create RFQ with Product Kit
    When I fill form with:
      | PO Number | PO013 |
    And I click "Submit Request"
    Then I should see "Request has been saved" flash message
