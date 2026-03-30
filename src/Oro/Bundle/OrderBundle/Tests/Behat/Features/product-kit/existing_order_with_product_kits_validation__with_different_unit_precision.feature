@regression
@feature-BB-21128
@fixture-OroOrderBundle:product-kit/existing_order_with_product_kits_validation__product.yml
@fixture-OroOrderBundle:product-kit/existing_order_with_product_kits_validation__with_different_unit_precision__order.yml

Feature: Existing Order with Product Kits Validation - with Different Unit Precision

  Scenario: Feature Background
    Given I login as administrator
    And go to Sales / Orders
    And click edit "order1" in grid

  Scenario: Check the kit item line items with different unit precision
    Given I click "Line Items"
    And I click on the first "Edit Line Item Button"
    And "Order Form" must contains values:
      | Quantity                | 1                                     |
      | Price                   | 12.3400                               |
      | ProductKitItem1Product  | simple-product-03 - Simple Product 03 |
      | ProductKitItem1Quantity | 2.345                                 |
      | ProductKitItem1Price    | 34.56                                 |
      | ProductKitItem2Product  | simple-product-01 - Simple Product 01 |
      | ProductKitItem2Quantity | 1.23                                  |
      | ProductKitItem2Price    | 23.45                                 |

  Scenario: Check the unit precision validation error for the kit item line items with different unit precision
    When fill "Order Form" with:
      | ProductKitItem1Quantity | 2.3456 |
    And I click on the first "Order Edit Save Changes"
    Then I should see "Order Form" validation errors:
      | ProductKitItem1Quantity | Only 3 decimal digits are allowed for unit "piece" |
    When fill "Order Form" with:
      | ProductKitItem2Quantity | 1.234  |
    And I click on the first "Order Edit Save Changes"
    Then I should see "Order Form" validation errors:
      | ProductKitItem2Quantity | Only 2 decimal digits are allowed for unit "piece" |

  Scenario: Change the kit item line items with different unit precision
    When fill "Order Form" with:
      | Quantity                | 2     |
      | ProductKitItem1Quantity | 3.345 |
    And fill "Order Form" with:
      | ProductKitItem1Price    | 37.56 |
    And fill "Order Form" with:
      | ProductKitItem2Quantity | 2.23  |
    And I click "Order Form Line Item 1 Kit Item 2 Price Overridden"
    And I click "Reset price"
    And I click on empty space
    And I click "Order Form Line Item 1 Price Overridden"
    And I click "Reset price"
    And I click on empty space
    Then "Order Form" must contains values:
      | Quantity                | 2                                     |
      | Price                   | 251.84                                |
      | ProductKitItem1Product  | simple-product-03 - Simple Product 03 |
      | ProductKitItem1Quantity | 3.345                                 |
      | ProductKitItem1Price    | 37.56                                 |
      | ProductKitItem2Product  | simple-product-01 - Simple Product 01 |
      | ProductKitItem2Quantity | 2.23                                  |
      | ProductKitItem2Price    | 1.23                                  |
    And I click on the first "Order Edit Save Changes"
    When I save form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message
