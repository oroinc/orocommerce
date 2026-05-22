@feature-BB-21128
@feature-BB-23530
@fixture-OroOrderBundle:product-kit/create_order_with_product_kits.yml

Feature: Create Order with Product Kits

  Scenario: Add a product kit line item
    Given I login as administrator
    And go to Sales / Orders
    And click "Create Order"
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
    And fill "Order Line Item Draft Create Form" with:
      | Product | product-kit-01 |
    And click on "Order Line Item Draft Create Form Add Product"

    When I click Edit "product-kit-01" in grid
    Then I should see "Optional Item" in the "Order Line Item Draft Edit Form Kit Item 1 Label" element
    And I should see "Mandatory Item *" in the "Order Line Item Draft Edit Form Kit Item 2 Label" element
    And the "ProductKitItem1Quantity" field should be disabled in form "Order Line Item Draft Edit Form"
    And the "ProductKitItem1Price" field should be disabled in form "Order Line Item Draft Edit Form"
    And I should see the following options for "ProductKitItem1Product" select in form "Order Line Item Draft Edit Form":
      | simple-product-03 - Simple Product 03 |
    And I should see the following options for "ProductKitItem2Product" select in form "Order Line Item Draft Edit Form":
      | simple-product-01 - Simple Product 01 |
      | simple-product-02 - Simple Product 02 |
    And "Order Line Item Draft Edit Form" must contains values:
      | Quantity                | 1                                     |
      | Price                   | 124.69                                |
      | ProductKitItem1Product  | None                                  |
      | ProductKitItem1Quantity |                                       |
      | ProductKitItem1Price    |                                       |
      | ProductKitItem2Product  | simple-product-01 - Simple Product 01 |
      | ProductKitItem2Quantity | 1                                     |
      | ProductKitItem2Price    | 1.23                                  |
    And I should see "per pc" in the "Order Line Item Draft Edit Form Kit Item 1 Unit" element
    And I should see "per pc" in the "Order Line Item Draft Edit Form Kit Item 2 Unit" element
    And the "Price" field should be readonly in form "Order Line Item Draft Edit Form"
    When I click on "Order Line Item Draft Edit Form Kit Item 1 Quantity Label Tooltip"
    Then I should see "The quantity of product kit item units to be purchased: piece (whole numbers)" in the "Tooltip Popover Content" element
    And I click on empty space

  Scenario: Change currency
    And I should see "Price, $:" in the "Order Line Item Draft Edit Form Kit Item 1 Price Label" element
    And I should see "Price, $:" in the "Order Line Item Draft Edit Form Kit Item 2 Price Label" element
    When I fill "Order Form" with:
      | Currency | Euro (€) |
    Then I should see "Prices for line items may have changed. Please review the prices before saving the order." flash message and I close it
    And I should see "Price, €:" in the "Order Line Item Draft Edit Form Kit Item 1 Price Label" element
    And I should see "Price, €:" in the "Order Line Item Draft Edit Form Kit Item 2 Price Label" element
    And I should not see "Price, $:"
    And I click on "Order Line Item Draft Edit Form Discard Button"
    And I should see an "Order Line Item Tier Prices Hint" element
    When I click on "Order Line Item Tier Prices Hint"
    Then I should see "Price is overridden"
#    The expected value here should be updated after fixing the bug BB-25074,
#    because there are no prices for the Euro currency in this test. Currently, an incorrect price is being displayed

    When I fill "Order Form" with:
      | Currency | US Dollar ($) |
    Then I should see "Prices for line items may have changed. Please review the prices before saving the order." flash message and I close it
    And I should not see an "Order Line Item Tier Prices Hint" element
    And I click Edit "product-kit-01" in grid
    And I should not see "Price, €:"
    And "Order Line Item Draft Edit Form" must contains values:
      | ProductKitItem1Price |      |
      | ProductKitItem2Price | 1.23 |
    And I click on "Order Line Item Draft Edit Form Discard Button"

  Scenario: Add one more product kit line item via the entity select popup
    When I open select entity popup for field "Product" in form "Order Line Item Draft Create Form"
    And I sort "SelectProductsGrid" by "SKU"
    Then I should see following "SelectProductsGrid" grid:
      | SKU               | Name              |
      | product-kit-01    | Product Kit 01    |
      | simple-product-01 | Simple Product 01 |
      | simple-product-02 | Simple Product 02 |
      | simple-product-03 | Simple Product 03 |
    When I click on product-kit-01 in grid "SelectProductsGrid"
    And fill "Order Line Item Draft Create Form" with:
      | Quantity                | 2                                     |
      | ProductKitItem1Product  | simple-product-03 - Simple Product 03 |
      | ProductKitItem1Quantity | 3                                     |
      | ProductKitItem1Price    | 0.75                                  |
    And click on "Order Line Item Draft Create Form Add Product"

    When I click "edit" on second row in grid
    Then "Order Line Item Draft Edit Form" must contains values:
      | Product                 | product-kit-01 - Product Kit 01       |
      | Quantity                | 2                                     |
      | Price                   | 126.94                                |
      | ProductKitItem1Product  | simple-product-03 - Simple Product 03 |
      | ProductKitItem1Quantity | 3                                     |
      | ProductKitItem1Price    | 0.75                                  |
      | ProductKitItem2Product  | simple-product-01 - Simple Product 01 |
      | ProductKitItem2Quantity | 1                                     |
      | ProductKitItem2Price    | 1.23                                  |
    And I should see "$1.2345" in the "Order Line Item Draft Edit Form Kit Item 1 Matched Price" element
    And I should not see a "Order Line Item Draft Edit Form Kit Item 2 Matched Price" element
    And the "Price" field should be readonly in form "Order Line Item Draft Edit Form"

  Scenario: Check that order line item price is updated when a kit configuration changes
    When I fill "Order Line Item Draft Edit Form" with:
      | ProductKitItem1Quantity | 5 |
    Then "Order Line Item Draft Edit Form" must contains values:
      | Price | 128.44 |
    When I fill "Order Line Item Draft Edit Form" with:
      | ProductKitItem1Price | 0.85 |
    Then "Order Line Item Draft Edit Form" must contains values:
      | Price | 128.94 |
    And the "Price" field should be readonly in form "Order Line Item Draft Edit Form"
    And I click on "Order Line Item Draft Edit Form Save Button"

  Scenario: Check order subtotals
    And I see next subtotals for "Backend Order":
      | Subtotal | $382.57 |
      | Total    | $382.57 |

  Scenario: Save order and check the view page
    When click "Calculate Shipping Button"
    And I save and close form
    Then I should see Order with:
      | Created By | John Doe |
    And I should see "Order Total: $382.57"
    And I should see following "BackendOrderLineItemsGrid" grid:
      | SKU            | Product                                                                                                             | Quantity | Product Unit Code | Price   |
      | product-kit-01 | Product Kit 01 Mandatory Item [piece x 1] $1.23 Simple Product 01                                                   | 1        | piece             | $124.69 |
      | product-kit-01 | Product Kit 01 Optional Item [piece x 5] $0.85 Simple Product 03 Mandatory Item [piece x 1] $1.23 Simple Product 01 | 2        | piece             | $128.94 |
