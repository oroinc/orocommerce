@feature-BB-26023-enabled
@regression
@feature-BB-21128
@fixture-OroOrderBundle:product-kit/existing_order_with_product_kits_validation__product.yml
@fixture-OroOrderBundle:product-kit/existing_order_with_product_kits_validation__with_different_unit__order.yml

Feature: Existing Order with Product Kits Validation - with Different Unit - Order Draft Edit Mode

  Scenario: Enable Order Draft Edit Mode
    Given I set configuration property "oro_order.enable_order_draft_edit_mode" to "1"

  Scenario: Feature Background
    Given I login as administrator
    And go to Sales / Orders
    And click edit "order1" in grid

  Scenario: Check the kit item line items with different unit
    Given I click "Line Items"
    And I click edit product-kit-01 in "Order Line Item Draft Grid"
    And "Order Line Item Draft Edit Form" must contains values:
      | Quantity                | 1                                     |
      | Price                   | 12.3400                               |
      | ProductKitItem1Product  | simple-product-03 - Simple Product 03 |
      | ProductKitItem1Quantity | 2.22                                  |
      | ProductKitItem1Price    | 34.56                                 |
      | ProductKitItem2Product  | simple-product-01 - Simple Product 01 |
      | ProductKitItem2Quantity | 1                                     |
      | ProductKitItem2Price    | 23.45                                 |
    And I should see "per item" in the "Order Line Item Draft Edit Form Kit Item 1 Unit" element
    And I should see "per ea" in the "Order Line Item Draft Edit Form Kit Item 2 Unit" element

  Scenario: Check the unit precision validation error for the kit item line items with different unit
    When fill "Order Line Item Draft Edit Form" with:
      | ProductKitItem1Quantity | 2.222 |
    And I click on "Order Line Item Draft Edit Form Save Button"
    Then I should see "Order Line Item Draft Edit Form" validation errors:
      | ProductKitItem1Quantity | Only 2 decimal digits are allowed for unit "item" |
    When fill "Order Line Item Draft Edit Form" with:
      | ProductKitItem2Quantity | 1.11 |
    And I click on "Order Line Item Draft Edit Form Save Button"
    Then I should see "Order Line Item Draft Edit Form" validation errors:
      | ProductKitItem2Quantity | Only whole numbers are allowed for unit "each" |

  Scenario: Change the kit item line items with different unit
    When fill "Order Line Item Draft Edit Form" with:
      | Quantity                | 2    |
      | ProductKitItem1Quantity | 2.11 |
    And fill "Order Line Item Draft Edit Form" with:
      | ProductKitItem1Price | 37.56 |
    And fill "Order Line Item Draft Edit Form" with:
      | ProductKitItem2Product | simple-product-02 - Simple Product 02 |
    And fill "Order Line Item Draft Edit Form" with:
      | ProductKitItem2Quantity | 1 |
    And fill "Order Line Item Draft Edit Form" with:
      | ProductKitItem2Price | 1.47 |
    And I click on "Order Line Item Draft Edit Form Price Overridden"
    And I click "Reset price"
    Then "Order Line Item Draft Edit Form" must contains values:
      | Quantity                | 2                                     |
      | Price                   | 204.18                                |
      | ProductKitItem1Product  | simple-product-03 - Simple Product 03 |
      | ProductKitItem1Quantity | 2.11                                  |
      | ProductKitItem1Price    | 37.56                                 |
      | ProductKitItem2Product  | simple-product-02 - Simple Product 02 |
      | ProductKitItem2Quantity | 1                                     |
      | ProductKitItem2Price    | 1.47                                  |
    And I click on "Order Line Item Draft Edit Form Save Button"
    When I save form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message

  Scenario: Remove line item
    Given I click "Line Items"
    When I click delete "product-kit-01" in grid
    And I click "Yes, Delete" in confirmation dialogue
    And I save form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message

  Scenario: Check the kit item line items with a missing unit
    Given I click "Line Items"
    And I click edit product-kit-01 in "Order Line Item Draft Grid"
    And "Order Line Item Draft Edit Form" must contains values:
      | Quantity                | 1                                     |
      | Price                   | 12.3400                               |
      | ProductKitItem1Product  | simple-product-03 - Simple Product 03 |
      | ProductKitItem1Quantity | 1                                     |
      | ProductKitItem1Price    | 34.56                                 |
      | ProductKitItem2Product  | simple-product-01 - Simple Product 01 |
      | ProductKitItem2Quantity | 1                                     |
      | ProductKitItem2Price    | 23.45                                 |
    And I should see "per missing_unit" in the "Order Line Item Draft Edit Form Kit Item 1 Unit" element
    And I should see "per missing_unit" in the "Order Line Item Draft Edit Form Kit Item 2 Unit" element

  Scenario: Change the kit item line items with a missing unit
    When fill "Order Line Item Draft Edit Form" with:
      | Quantity                | 2 |
      | ProductKitItem1Quantity | 2 |
    And fill "Order Line Item Draft Edit Form" with:
      | ProductKitItem1Price | 37.56 |
    And fill "Order Line Item Draft Edit Form" with:
      | ProductKitItem2Product | simple-product-02 - Simple Product 02 |
    And fill "Order Line Item Draft Edit Form" with:
      | ProductKitItem2Quantity | 3 |
    And fill "Order Line Item Draft Edit Form" with:
      | ProductKitItem2Price | 1.47 |
    And I click on "Order Line Item Draft Edit Form Price Overridden"
    And I click "Reset price"
    Then "Order Line Item Draft Edit Form" must contains values:
      | Quantity                | 2                                     |
      | Price                   | 202.99                                |
      | ProductKitItem1Product  | simple-product-03 - Simple Product 03 |
      | ProductKitItem1Quantity | 2                                     |
      | ProductKitItem1Price    | 37.56                                 |
      | ProductKitItem2Product  | simple-product-02 - Simple Product 02 |
      | ProductKitItem2Quantity | 3                                     |
      | ProductKitItem2Price    | 1.47                                  |
    And I click on "Order Line Item Draft Edit Form Save Button"
    When I save form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message
