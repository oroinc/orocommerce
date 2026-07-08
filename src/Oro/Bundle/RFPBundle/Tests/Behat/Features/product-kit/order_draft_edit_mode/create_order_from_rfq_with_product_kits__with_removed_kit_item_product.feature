@regression
@feature-BB-27392
@fixture-OroRFPBundle:product-kit/existing_rfq_with_product_kits_validation__product.yml
@fixture-OroRFPBundle:product-kit/create_order_from_rfq_with_product_kits__rfq.yml

Feature: Create Order from RFQ with Product Kits - with Removed Kit Item Product

  Scenario: Enable Order Draft Edit Mode
    Given I set configuration property "oro_order.enable_order_draft_edit_mode" to "1"

  Scenario: Remove a product from the product kit item that is referenced by the RFQ
    Given I login as administrator
    When I go to Products / Products
    And click edit "product-kit-01" in grid
    And I click "Kit Item 2 Toggler"
    And I click on the first "Kit Item 2 Product Remove Button"
    And I click "Yes, Delete"
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: The removed product is still listed in the submitted RFQ
    When I go to Sales / Requests For Quote
    And click view "PO013" in grid
    Then I should see next rows in "Request Line Items Table" table
      | SKU               | Product                                                                                                 | Requested Quantity | Target Price |
      | simple-product-01 | Simple Product 01                                                                                       | 1 pc               | $2.00        |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 2] Simple Product 03 Mandatory Item [piece x 3] Simple Product 01 | 1 pc               | $104.69      |

  Scenario: Create Order from the RFQ and check the Create Order form
    When I click on "RFQ Create Order"
    Then "Order Form" must contains values:
      | Customer      | Customer1   |
      | Customer User | Amanda Cole |
    And "Order Line Item Draft Edit Form" must contains values:
      | ProductKitItem1Product | simple-product-03 - Simple Product 03 |
      | ProductKitItem2Product | simple-product-01 - Simple Product 01 |
    And I should see "Order Line Item Draft Edit Form" validation errors:
      | ProductKitItem2Product | The selected product is not allowed |

  Scenario: Replace the missing kit item product and save the Order successfully
    When fill "Order Line Item Draft Edit Form" with:
      | ProductKitItem2Product | simple-product-02 - Simple Product 02 |
    And I click on "Order Line Item Draft Edit Form Save Button"
    Then I should see following "Order Line Item Draft Grid" grid:
      | SKU               | Product                                                                                                               | Quantity | Price   |
      | simple-product-01 | Simple Product 01                                                                                                     | 1 piece  | $1.2345 |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 2] $3.7035 Simple Product 03 Mandatory Item [piece x 1] $2.47 Simple Product 02 | 1 piece  | $133.34 |
    When I save form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message
