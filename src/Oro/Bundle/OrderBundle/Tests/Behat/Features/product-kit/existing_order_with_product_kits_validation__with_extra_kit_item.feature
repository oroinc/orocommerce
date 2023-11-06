@regression
@feature-BB-21128
@fixture-OroOrderBundle:product-kit/existing_order_with_product_kits_validation__product.yml
@fixture-OroOrderBundle:product-kit/existing_order_with_product_kits_validation__with_extra_kit_item__order.yml

Feature: Existing Order with Product Kits Validation - with Extra Kit Item

  Scenario: Feature Background
    Given I login as administrator
    And go to Sales / Orders
    And click edit "order1" in grid

  Scenario: Check the line item with an extra mandatory kit item
    Given "Order Form" must contains values:
      | Quantity                | 1                                     |
      | Price                   | 12.3400                               |
      | ProductKitItem1Product  | simple-product-03 - Simple Product 03 |
      | ProductKitItem1Quantity | 1                                     |
      | ProductKitItem1Price    | 34.56                                 |
      | ProductKitItem2Product  | simple-product-01 - Simple Product 01 |
      | ProductKitItem2Quantity | 1                                     |
      | ProductKitItem2Price    | 23.45                                 |
      | ProductKitItem3Product  | simple-product-03 - Simple Product 03 |
      | ProductKitItem3Quantity | 1                                     |
      | ProductKitItem3Price    | 45.67                                 |
    And I should see the following options for "ProductKitItem1Product" select in form "Order Form":
      | simple-product-03 - Simple Product 03 |
    And I should see the following options for "ProductKitItem2Product" select in form "Order Form":
      | simple-product-01 - Simple Product 01 |
      | simple-product-02 - Simple Product 02 |
    And I should see the following options for "ProductKitItem3Product" select in form "Order Form":
      | simple-product-03 - Simple Product 03 |
    And I should see "Optional Item" in the "Order Form Line Item 1 Kit Item 1 Label" element
    And I should see "Mandatory Item *" in the "Order Form Line Item 1 Kit Item 2 Label" element
    And I should see "Extra Kit Item *" in the "Order Form Line Item 1 Kit Item 3 Label" element

  Scenario: Check the min/max quantity validation error for an extra mandatory kit item
    When fill "Order Form" with:
      | ProductKitItem3Quantity | 4 |
    And I save form
    And I click "Save" in modal window
    Then I should see "Order Form" validation errors:
      | ProductKitItem3Quantity | The quantity should be between 1 and 3 |

  Scenario: Change the line item with an extra mandatory kit item
    When fill "Order Form" with:
      | Quantity                | 2                                     |
      | ProductKitItem1Quantity | 2                                     |
      | ProductKitItem1Price    | 37.56                                 |
      | ProductKitItem2Product  | simple-product-02 - Simple Product 02 |
      | ProductKitItem2Quantity | 3                                     |
      | ProductKitItem3Quantity | 3                                     |
      | ProductKitItem3Price    | 46.67                                 |
    And I click "Order Form Line Item 1 Price Overridden"
    And I click "Reset price"
    And I click on empty space
    Then "Order Form" must contains values:
      | Quantity                | 2                                     |
      | Price                   | 346.00                                |
      | ProductKitItem1Product  | simple-product-03 - Simple Product 03 |
      | ProductKitItem1Quantity | 2                                     |
      | ProductKitItem1Price    | 37.56                                 |
      | ProductKitItem2Product  | simple-product-02 - Simple Product 02 |
      | ProductKitItem2Quantity | 3                                     |
      | ProductKitItem2Price    | 2.47                                  |
      | ProductKitItem3Quantity | 3                                     |
      | ProductKitItem3Price    | 46.67                                 |
    When I save form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message

  Scenario: Remove line item
    When I click on "Order Form Line Item 1 Remove"
    And I save form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message

  Scenario: Check the line item with an extra optional kit item
    Given "Order Form" must contains values:
      | Quantity                | 1                                     |
      | Price                   | 12.3400                               |
      | ProductKitItem1Product  | simple-product-03 - Simple Product 03 |
      | ProductKitItem1Quantity | 1                                     |
      | ProductKitItem1Price    | 34.56                                 |
      | ProductKitItem2Product  | simple-product-01 - Simple Product 01 |
      | ProductKitItem2Quantity | 1                                     |
      | ProductKitItem2Price    | 23.45                                 |
      | ProductKitItem3Product  | simple-product-03 - Simple Product 03 |
      | ProductKitItem3Quantity | 1                                     |
      | ProductKitItem3Price    | 45.67                                 |
    And I should see the following options for "ProductKitItem1Product" select in form "Order Form":
      | simple-product-03 - Simple Product 03 |
    And I should see the following options for "ProductKitItem2Product" select in form "Order Form":
      | simple-product-01 - Simple Product 01 |
      | simple-product-02 - Simple Product 02 |
    And I should see the following options for "ProductKitItem3Product" select in form "Order Form":
      | simple-product-03 - Simple Product 03 |
    And I should see "Optional Item" in the "Order Form Line Item 1 Kit Item 1 Label" element
    And I should see "Mandatory Item *" in the "Order Form Line Item 1 Kit Item 2 Label" element
    And I should see "Extra Optional Kit Item" in the "Order Form Line Item 1 Kit Item 3 Label" element

  Scenario: Check the min/max quantity validation error for an extra optional kit item
    When fill "Order Form" with:
      | ProductKitItem3Quantity | 4 |
    And I save form
    And I click "Save" in modal window
    Then I should see "Order Form" validation errors:
      | ProductKitItem3Quantity | The quantity should be between 1 and 3 |

  Scenario: Change the line item with an extra optional kit item
    When fill "Order Form" with:
      | Quantity                | 2                                     |
      | ProductKitItem1Quantity | 2                                     |
      | ProductKitItem1Price    | 37.56                                 |
      | ProductKitItem2Product  | simple-product-02 - Simple Product 02 |
      | ProductKitItem2Quantity | 3                                     |
      | ProductKitItem3Quantity | 3                                     |
      | ProductKitItem3Price    | 46.67                                 |
    And I click "Order Form Line Item 1 Price Overridden"
    And I click "Reset price"
    And I click on empty space
    Then "Order Form" must contains values:
      | Quantity                | 2                                     |
      | Price                   | 346.00                                |
      | ProductKitItem1Product  | simple-product-03 - Simple Product 03 |
      | ProductKitItem1Quantity | 2                                     |
      | ProductKitItem1Price    | 37.56                                 |
      | ProductKitItem2Product  | simple-product-02 - Simple Product 02 |
      | ProductKitItem2Quantity | 3                                     |
      | ProductKitItem2Price    | 2.47                                  |
      | ProductKitItem3Quantity | 3                                     |
      | ProductKitItem3Price    | 46.67                                 |
    When I save form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message

  Scenario: Remove the extra optional kit item
    When I clear "ProductKitItem3Product" field in form "Order Form"
    And I save form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message
    And I should see "Optional Item" in the "Order Form Line Item 1 Kit Item 1 Label" element
    And I should see "Mandatory Item *" in the "Order Form Line Item 1 Kit Item 2 Label" element
    And I should not see a "Order Form Line Item 1 Kit Item 3 Label" element
