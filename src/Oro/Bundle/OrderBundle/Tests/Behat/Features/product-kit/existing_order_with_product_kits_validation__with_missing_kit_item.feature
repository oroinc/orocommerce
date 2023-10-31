@regression
@feature-BB-21128
@fixture-OroOrderBundle:product-kit/existing_order_with_product_kits_validation__product.yml
@fixture-OroOrderBundle:product-kit/existing_order_with_product_kits_validation__with_missing_kit_item__order.yml

Feature: Existing Order with Product Kits Validation - with Missing Kit Item

  Scenario: Feature Background
    Given I login as administrator
    And go to Sales / Orders
    And click edit "order1" in grid

  Scenario: Check the line item with a missing mandatory kit item
    Given "Order Form" must contains values:
      | Quantity                | 1                                     |
      | Price                   | 12.3400                               |
      | ProductKitItem1Product  | simple-product-03 - Simple Product 03 |
      | ProductKitItem1Quantity | 1                                     |
      | ProductKitItem1Price    | 34.56                                 |
      | ProductKitItem2Product  | None                                  |
      | ProductKitItem2Quantity |                                       |
      | ProductKitItem2Price    |                                       |
    And I should see the following options for "ProductKitItem1Product" select in form "Order Form":
      | simple-product-03 - Simple Product 03 |
    And I should see the following options for "ProductKitItem2Product" select in form "Order Form":
      | simple-product-01 - Simple Product 01 |
      | simple-product-02 - Simple Product 02 |
    And I should see "Optional Item" in the "Order Form Line Item 1 Kit Item 1 Label" element
    And I should see "Mandatory Item" in the "Order Form Line Item 1 Kit Item 2 Label" element
    And I should not see "Mandatory Item *" in the "Order Form Line Item 1 Kit Item 2 Label" element

  Scenario: Change the line item with a missing mandatory kit item
    When fill "Order Form" with:
      | Quantity                | 2                                     |
      | ProductKitItem1Quantity | 2                                     |
      | ProductKitItem1Price    | 36.56                                 |
      | ProductKitItem2Product  | simple-product-02 - Simple Product 02 |
      | ProductKitItem2Quantity | 3                                     |
    And I click "Order Form Line Item 1 Price Overridden"
    And I click "Reset price"
    And I click on empty space
    Then "Order Form" must contains values:
      | Quantity                | 2                                     |
      | Price                   | 203.99                                |
      | ProductKitItem1Product  | simple-product-03 - Simple Product 03 |
      | ProductKitItem1Quantity | 2                                     |
      | ProductKitItem1Price    | 36.56                                 |
      | ProductKitItem2Product  | simple-product-02 - Simple Product 02 |
      | ProductKitItem2Quantity | 3                                     |
      | ProductKitItem2Price    | 2.47                                  |
    When I save form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message
