@skip
@regression
@feature-BB-21128
@fixture-OroOrderBundle:product-kit/existing_order_with_product_kits_validation__product.yml
@fixture-OroOrderBundle:product-kit/existing_order_with_product_kits_validation__with_missing_product__order.yml

Feature: Existing Order with Product Kits Validation - with Missing Product

  Scenario: Feature Background
    Given I login as administrator
    And go to Sales / Orders
    And click edit "order1" in grid

  Scenario: Check the kit item line items with a missing product
    Given "Order Form" must contains values:
      | Quantity                | 1                     |
      | Price                   | 12.3400               |
      | ProductKitItem1Product  | MP1 - Missing Product |
      | ProductKitItem1Quantity | 1                     |
      | ProductKitItem1Price    | 34.56                 |
      | ProductKitItem2Product  | MP1 - Missing Product |
      | ProductKitItem2Quantity | 1                     |
      | ProductKitItem2Price    | 23.45                 |
    And I should see the following options for "ProductKitItem1Product" select in form "Order Form":
      | MP1 - Missing Product                 |
      | simple-product-03 - Simple Product 03 |
    And I should see the following options for "ProductKitItem2Product" select in form "Order Form":
      | MP1 - Missing Product                 |
      | simple-product-01 - Simple Product 01 |
      | simple-product-02 - Simple Product 02 |

  Scenario: Change the kit item line items with a missing product
    When fill "Order Form" with:
      | Quantity                | 2                                     |
      | ProductKitItem1Product  | MP1 - Missing Product                 |
      | ProductKitItem1Quantity | 3                                     |
      | ProductKitItem1Price    | 35.56                                 |
      | ProductKitItem2Product  | simple-product-02 - Simple Product 02 |
      | ProductKitItem2Quantity | 4                                     |
    And I click "Order Form Line Item 1 Price Overridden"
    And I click "Reset price"
    And I click on empty space
    Then "Order Form" must contains values:
      | Quantity                | 2                                     |
      | Price                   | 240.02                                |
      | ProductKitItem1Product  | MP1 - Missing Product                 |
      | ProductKitItem1Quantity | 3                                     |
      | ProductKitItem1Price    | 35.56                                 |
      | ProductKitItem2Product  | simple-product-02 - Simple Product 02 |
      | ProductKitItem2Quantity | 4                                     |
      | ProductKitItem2Price    | 2.47                                  |
    When I save form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message

  Scenario: Remove line item
    When I click on "Order Form Line Item 1 Remove"
    And I save form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message

  Scenario: Check the kit item line items with disabled product
    Given "Order Form" must contains values:
      | Quantity                | 1                                                |
      | Price                   | 12.3400                                          |
      | ProductKitItem1Product  | simple-product-04 - Simple Product 04 - Disabled |
      | ProductKitItem1Quantity | 1                                                |
      | ProductKitItem1Price    | 34.56                                            |
      | ProductKitItem2Product  | simple-product-04 - Simple Product 04 - Disabled |
      | ProductKitItem2Quantity | 1                                                |
      | ProductKitItem2Price    | 23.45                                            |
    And I should see the following options for "ProductKitItem1Product" select in form "Order Form":
      | simple-product-04 - Simple Product 04 - Disabled |
      | simple-product-03 - Simple Product 03            |
    And I should see the "Order Product Kit Item Line Item Product Ghost Option 1" element in "ProductKitItem1Product" select in form "Order Form"
    And I should see the following options for "ProductKitItem2Product" select in form "Order Form":
      | simple-product-04 - Simple Product 04 - Disabled |
      | simple-product-01 - Simple Product 01            |
      | simple-product-02 - Simple Product 02            |
    And I should see the "Order Product Kit Item Line Item Product Ghost Option 1" element in "ProductKitItem2Product" select in form "Order Form"

  Scenario: Change the kit item line items with disabled product
    When fill "Order Form" with:
      | Quantity                | 2                                     |
      | ProductKitItem1Quantity | 2                                     |
      | ProductKitItem1Price    | 37.56                                 |
      | ProductKitItem2Product  | simple-product-02 - Simple Product 02 |
      | ProductKitItem2Quantity | 3                                     |
    And I click "Order Form Line Item 1 Price Overridden"
    And I click "Reset price"
    And I click on empty space
    Then "Order Form" must contains values:
      | Quantity                | 2                                                |
      | Price                   | 205.99                                           |
      | ProductKitItem1Product  | simple-product-04 - Simple Product 04 - Disabled |
      | ProductKitItem1Quantity | 2                                                |
      | ProductKitItem1Price    | 37.56                                            |
      | ProductKitItem2Product  | simple-product-02 - Simple Product 02            |
      | ProductKitItem2Quantity | 3                                                |
      | ProductKitItem2Price    | 2.47                                             |
    When I save form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message
