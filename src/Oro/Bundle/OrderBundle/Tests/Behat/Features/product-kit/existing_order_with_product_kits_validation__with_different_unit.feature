@skip
@regression
@feature-BB-21128
@fixture-OroOrderBundle:product-kit/existing_order_with_product_kits_validation__product.yml
@fixture-OroOrderBundle:product-kit/existing_order_with_product_kits_validation__with_different_unit__order.yml

Feature: Existing Order with Product Kits Validation - with Different Unit

  Scenario: Feature Background
    Given I login as administrator
    And go to Sales / Orders
    And click edit "order1" in grid

  Scenario: Check the kit item line items with different unit
    Given "Order Form" must contains values:
      | Quantity                | 1                                     |
      | Price                   | 12.3400                               |
      | ProductKitItem1Product  | simple-product-03 - Simple Product 03 |
      | ProductKitItem1Quantity | 2.22                                  |
      | ProductKitItem1Price    | 34.56                                 |
      | ProductKitItem2Product  | simple-product-01 - Simple Product 01 |
      | ProductKitItem2Quantity | 1                                     |
      | ProductKitItem2Price    | 23.45                                 |
    And I should see "per item" in the "Order Form Line Item 1 Kit Item 1 Unit" element
    And I should see "per ea" in the "Order Form Line Item 1 Kit Item 2 Unit" element

  Scenario: Check the unit precision validation error for the kit item line items with different unit
    When fill "Order Form" with:
      | ProductKitItem1Quantity | 2.222 |
      | ProductKitItem2Quantity | 1.11  |
    And I save form
    And I click "Save" in modal window
    Then I should see "Order Form" validation errors:
      | ProductKitItem1Quantity | Only 2 decimal digits are allowed for unit "item" |
      | ProductKitItem2Quantity | Only whole numbers are allowed for unit "each"    |

  Scenario: Change the kit item line items with different unit
    When fill "Order Form" with:
      | Quantity                | 2                                     |
      | ProductKitItem1Quantity | 2.11                                  |
      | ProductKitItem1Price    | 37.56                                 |
      | ProductKitItem2Product  | simple-product-02 - Simple Product 02 |
      | ProductKitItem2Quantity | 1                                     |
      | ProductKitItem2Price    | 1.47                                  |
    And I click "Order Form Line Item 1 Price Overridden"
    And I click "Reset price"
    And I click on empty space
    Then "Order Form" must contains values:
      | Quantity                | 2                                     |
      | Price                   | 204.18                                |
      | ProductKitItem1Product  | simple-product-03 - Simple Product 03 |
      | ProductKitItem1Quantity | 2.11                                  |
      | ProductKitItem1Price    | 37.56                                 |
      | ProductKitItem2Product  | simple-product-02 - Simple Product 02 |
      | ProductKitItem2Quantity | 1                                     |
      | ProductKitItem2Price    | 1.47                                  |
    When I save form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message

  Scenario: Remove line item
    When I click on "Order Form Line Item 1 Remove"
    And I save form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message

  Scenario: Check the kit item line items with a missing unit
    Given "Order Form" must contains values:
      | Quantity                | 1                                     |
      | Price                   | 12.3400                               |
      | ProductKitItem1Product  | simple-product-03 - Simple Product 03 |
      | ProductKitItem1Quantity | 1                                     |
      | ProductKitItem1Price    | 34.56                                 |
      | ProductKitItem2Product  | simple-product-01 - Simple Product 01 |
      | ProductKitItem2Quantity | 1                                     |
      | ProductKitItem2Price    | 23.45                                 |
    And I should see "per missing_unit" in the "Order Form Line Item 1 Kit Item 1 Unit" element
    And I should see "per missing_unit" in the "Order Form Line Item 1 Kit Item 2 Unit" element

  Scenario: Change the kit item line items with a missing unit
    When fill "Order Form" with:
      | Quantity                | 2                                     |
      | ProductKitItem1Quantity | 2                                     |
      | ProductKitItem1Price    | 37.56                                 |
      | ProductKitItem2Product  | simple-product-02 - Simple Product 02 |
      | ProductKitItem2Quantity | 3                                     |
      | ProductKitItem2Price    | 1.47                                  |
    And I click "Order Form Line Item 1 Price Overridden"
    And I click "Reset price"
    And I click on empty space
    Then "Order Form" must contains values:
      | Quantity                | 2                                     |
      | Price                   | 202.99                                |
      | ProductKitItem1Product  | simple-product-03 - Simple Product 03 |
      | ProductKitItem1Quantity | 2                                     |
      | ProductKitItem1Price    | 37.56                                 |
      | ProductKitItem2Product  | simple-product-02 - Simple Product 02 |
      | ProductKitItem2Quantity | 3                                     |
      | ProductKitItem2Price    | 1.47                                  |
    When I save form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message
