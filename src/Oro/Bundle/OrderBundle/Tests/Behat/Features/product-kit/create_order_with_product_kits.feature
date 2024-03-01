@feature-BB-21128
@fixture-OroOrderBundle:product-kit/create_order_with_product_kits.yml

Feature: Create Order with Product Kits

  Scenario: Add a product kit line item
    Given I login as administrator
    And go to Sales / Orders
    And click "Create Order"
    And I click "Add Product"
    When I fill "Order Form" with:
      | Customer         | Customer1                                                   |
      | Customer User    | Amanda Cole                                                 |
      | Billing Address  | Test Customer, ORO, 801 Scenic Hwy, HAINES CITY FL US 33844 |
      | Shipping Address | Test Customer, ORO, 801 Scenic Hwy, HAINES CITY FL US 33844 |
    Then "Order Form" must contains values:
      | Customer         | Customer1                                                   |
      | Customer User    | Amanda Cole                                                 |
      | Billing Address  | Test Customer, ORO, 801 Scenic Hwy, HAINES CITY FL US 33844 |
      | Shipping Address | Test Customer, ORO, 801 Scenic Hwy, HAINES CITY FL US 33844 |
    And fill "Order Form" with:
      | Product | product-kit-01 |
    Then I should see "Optional Item" in the "Order Form Line Item 1 Kit Item 1 Label" element
    And I should see "Mandatory Item *" in the "Order Form Line Item 1 Kit Item 2 Label" element
    And the "ProductKitItem1Quantity" field should be disabled in form "Order Form"
    And the "ProductKitItem1Price" field should be disabled in form "Order Form"
    And I should see the following options for "ProductKitItem1Product" select in form "Order Form":
      | simple-product-03 - Simple Product 03 |
    And I should see the following options for "ProductKitItem2Product" select in form "Order Form":
      | simple-product-01 - Simple Product 01 |
      | simple-product-02 - Simple Product 02 |
    And "Order Form" must contains values:
      | Quantity                | 1                                     |
      | Price                   | 124.69                                |
      | ProductKitItem1Product  | None                                  |
      | ProductKitItem1Quantity |                                       |
      | ProductKitItem1Price    |                                       |
      | ProductKitItem2Product  | simple-product-01 - Simple Product 01 |
      | ProductKitItem2Quantity | 1                                     |
      | ProductKitItem2Price    | 1.23                                  |
    And I should see "per pc" in the "Order Form Line Item 1 Kit Item 1 Unit" element
    And I should see "per pc" in the "Order Form Line Item 1 Kit Item 2 Unit" element
    And the "Price" field should be readonly in form "Order Form"
    When I click on "Order Form Line Item 1 Kit Item 1 Quantity Label Tooltip"
    Then I should see "The quantity of product kit item units to be purchased: piece (whole numbers)" in the "Tooltip Popover Content" element
    And I click on empty space

  Scenario: Add one more product kit line item via the entity select popup
    When I click "Add Product"
    And I open select entity popup for field "Product2Dropdown" in form "Order Form"
    And I sort grid by "SKU"
    Then I should see following grid:
      | SKU               | Name              |
      | product-kit-01    | Product Kit 01    |
      | simple-product-01 | Simple Product 01 |
      | simple-product-02 | Simple Product 02 |
      | simple-product-03 | Simple Product 03 |
    When I click on product-kit-01 in grid
    And fill "Order Form" with:
      | Quantity2                | 2                                     |
      | Product2KitItem1Product  | simple-product-03 - Simple Product 03 |
      | Product2KitItem1Quantity | 3                                     |
      | Product2KitItem1Price    | 0.75                                  |
    Then "Order Form" must contains values:
      | Product2                 | product-kit-01 - Product Kit 01       |
      | Quantity2                | 2                                     |
      | Price2                   | 126.94                                |
      | Product2KitItem1Product  | simple-product-03 - Simple Product 03 |
      | Product2KitItem1Quantity | 3                                     |
      | Product2KitItem1Price    | 0.75                                  |
      | Product2KitItem2Product  | simple-product-01 - Simple Product 01 |
      | Product2KitItem2Quantity | 1                                     |
      | Product2KitItem2Price    | 1.23                                  |
    And I should see "$1.2345" in the "Order Form Line Item 2 Kit Item 1 Matched Price" element
    And I should not see a "Order Form Line Item 2 Kit Item 2 Matched Price" element
    And the "Price" field should be readonly in form "Order Form"
    And the "Price2" field should be readonly in form "Order Form"

  Scenario: Check that order line item price is updated when a kit configuration changes
    When I fill "Order Form" with:
      | Product2KitItem1Quantity | 5 |
    Then "Order Form" must contains values:
      | Price2 | 128.44 |
    When I fill "Order Form" with:
      | Product2KitItem1Price | 0.85 |
    Then "Order Form" must contains values:
      | Price2 | 128.94 |
    And the "Price" field should be readonly in form "Order Form"
    And the "Price2" field should be readonly in form "Order Form"

  Scenario: Check order subtotals
    And I see next subtotals for "Backend Order":
      | Subtotal | $382.57 |
      | Total    | $382.57 |

  Scenario: Save order and check the view page
    When click "Calculate Shipping Button"
    And I save and close form
    Then I should see Order with:
      | Subtotal | $382.57 |
    And I should see following "BackendOrderLineItemsGrid" grid:
      | SKU            | Product                                                                                                             | Quantity | Product Unit Code | Price   |
      | product-kit-01 | Product Kit 01 Mandatory Item [piece x 1] $1.23 Simple Product 01                                                   | 1        | piece             | $124.69 |
      | product-kit-01 | Product Kit 01 Optional Item [piece x 5] $0.85 Simple Product 03 Mandatory Item [piece x 1] $1.23 Simple Product 01 | 2        | piece             | $128.94 |
