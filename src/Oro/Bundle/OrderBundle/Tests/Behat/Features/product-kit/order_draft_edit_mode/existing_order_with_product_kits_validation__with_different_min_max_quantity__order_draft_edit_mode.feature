@feature-BB-26023-enabled
@regression
@feature-BB-21128
@fixture-OroOrderBundle:product-kit/existing_order_with_product_kits_validation__product.yml
@fixture-OroOrderBundle:product-kit/existing_order_with_product_kits_validation__with_different_min_max_quantity__order.yml

Feature: Existing Order with Product Kits Validation - with Different Min Max Quantity - Order Draft Edit Mode

  Scenario: Enable Order Draft Edit Mode
    Given I set configuration property "oro_order.enable_order_draft_edit_mode" to "1"

  Scenario: Feature Background
    Given I login as administrator
    And go to Sales / Orders
    And click edit "order1" in grid

  Scenario: Check the kit item line items with different min/max quantity
    Given I click "Line Items"
    And I click edit product-kit-01 in "Order Line Item Draft Grid"
    And "Order Line Item Draft Edit Form" must contains values:
      | Quantity                | 1                                     |
      | Price                   | 12.3400                               |
      | ProductKitItem1Product  | simple-product-03 - Simple Product 03 |
      | ProductKitItem1Quantity | 6                                     |
      | ProductKitItem1Price    | 34.56                                 |
      | ProductKitItem2Product  | simple-product-01 - Simple Product 01 |
      | ProductKitItem2Quantity | 11                                    |
      | ProductKitItem2Price    | 23.45                                 |

  Scenario: Check the min/max quantity validation error for the kit item line items with different min/max quantity
    When fill "Order Line Item Draft Edit Form" with:
      | ProductKitItem1Quantity | 5  |
      | ProductKitItem2Quantity | 16 |
    And I click on "Order Line Item Draft Edit Form Save Button"
    Then I should see "Order Line Item Draft Edit Form" validation errors:
      | ProductKitItem1Quantity | The kit item quantity should be between 6 and 9.   |
      | ProductKitItem2Quantity | The kit item quantity should be between 11 and 15. |

  Scenario: Change the kit item line items with different min/max quantity
    When fill "Order Line Item Draft Edit Form" with:
      | Quantity                | 2                                     |
      | ProductKitItem1Quantity | 7                                     |
      | ProductKitItem1Price    | 37.56                                 |
      | ProductKitItem2Product  | simple-product-02 - Simple Product 02 |
      | ProductKitItem2Quantity | 12                                    |
    And I click on "Order Line Item Draft Edit Form Price Overridden"
    And I click "Reset price"
    Then "Order Line Item Draft Edit Form" must contains values:
      | Quantity                | 2                                     |
      | Price                   | 416.02                                |
      | ProductKitItem1Product  | simple-product-03 - Simple Product 03 |
      | ProductKitItem1Quantity | 7                                     |
      | ProductKitItem1Price    | 37.56                                 |
      | ProductKitItem2Product  | simple-product-02 - Simple Product 02 |
      | ProductKitItem2Quantity | 12                                    |
      | ProductKitItem2Price    | 2.47                                  |
    And I click on "Order Line Item Draft Edit Form Save Button"
    When I save form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message
