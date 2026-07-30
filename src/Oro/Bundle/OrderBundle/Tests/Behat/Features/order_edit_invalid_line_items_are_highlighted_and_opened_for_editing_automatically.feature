@fixture-OroOrderBundle:product-kit/order_with_product_kits_validation.yml

Feature: Order edit invalid line items are highlighted and opened for editing automatically

  Scenario: Feature Background
    Given sessions active:
      | Admin  | first_session  |
      | Admin1 | second_session |

  Scenario: Create an order with a simple product and a product kit line item
    Given I proceed as the Admin
    And I login as administrator
    When go to Sales / Orders
    And click "Create Order"
    And fill "Order Form" with:
      | Customer | Customer1 |
    And fill "Order Line Item Draft Create Form" with:
      | Product | simple-product-02 |
      | Price   | 25.00             |
    And I click "Add Product"

    And fill "Order Line Item Draft Create Form" with:
      | Product                 | product-kit-01                        |
      | Quantity                | 1                                     |
      | ProductKitItem1Product  | simple-product-03 - Simple Product 03 |
      | ProductKitItem1Quantity | 2                                     |
    And fill "Order Line Item Draft Create Form" with:
      | ProductKitItem2Product  | simple-product-01 - Simple Product 01 |
      | ProductKitItem2Quantity | 3                                     |
    And I click "Add Product"

    Then I should see following "Order Line Item Draft Grid" grid:
      | SKU               | Product                                                                                                             | Quantity | Price   |
      | simple-product-02 | Simple Product 02                                                                                                   | 1 piece  | $25.00  |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 2] $1.23 Simple Product 03 Mandatory Item [piece x 3] $1.23 Simple Product 01 | 1 piece  | $129.61 |
    And I should not see an "Order Line Item Draft Edit Form" element
    And simple-product-02 must be first record

  Scenario: Remove a kit item product from product kit configuration
    Given I proceed as the Admin1
    And I login as administrator
    When go to Products / Products
    And click edit "product-kit-01" in grid
    And I click "Kit Item 2 Toggler"
    And I click on the first "Kit Item 2 Product Remove Button"
    And I click "Yes, Delete" in confirmation dialogue
    And I save form
    Then I should see "Product has been saved" flash message

  Scenario: Check that invalid line items are pushed to the top of the line items datagrid and automatically switched to the edit mode
    Given I proceed as the Admin
    When save form
    And I click "Save" in modal window
    Then I should see "One or more line items have errors. Please review the line items and correct errors to proceed." error message

    And I should see "Order Line Item Draft Edit Form" validation errors:
      | ProductKitItem2Product | The selected product is not allowed |
    And I should see an "Order Line Item Draft Edit Form Delete Button" element
    And product-kit-01 must be first record

    And fill "Order Line Item Draft Edit Form" with:
      | ProductKitItem2Product | simple-product-02 - Simple Product 02 |
      | ProductKitItem2Price   | 1.23                                  |
    And I click on "Order Line Item Draft Edit Form Save Button"

    When save form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message
    And I should see following "Order Line Item Draft Grid" grid:
      | SKU               | Product                                                                                                             | Quantity | Price   |
      | simple-product-02 | Simple Product 02                                                                                                   | 1 piece  | $25.00  |
      | product-kit-01    | Product Kit 01 Optional Item [piece x 2] $1.23 Simple Product 03 Mandatory Item [piece x 3] $1.23 Simple Product 02 | 1 piece  | $129.61 |
