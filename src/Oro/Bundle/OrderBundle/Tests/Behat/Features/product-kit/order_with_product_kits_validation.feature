@regression
@feature-BB-21128
@fixture-OroOrderBundle:product-kit/order_with_product_kits_validation.yml

Feature: Order with Product Kits Validation

  Scenario: Add a product kit line item with max quantity violation
    Given I login as administrator
    And go to Sales / Orders
    And click "Create Order"
    When I click "Add Product"
    And fill "Order Form" with:
      | Customer                | Customer1                             |
      | Product                 | product-kit-01                        |
      | Quantity                | 1                                     |
      | ProductKitItem1Product  | simple-product-03 - Simple Product 03 |
      | ProductKitItem1Quantity | 6                                     |
      | ProductKitItem2Product  | simple-product-01 - Simple Product 01 |
      | ProductKitItem2Quantity | 11                                    |
    And the "Price" field should be readonly in form "Order Form"
    And click "Calculate Shipping Button"
    And I save form
    Then I should see "Order Form" validation errors:
      | ProductKitItem1Quantity | The quantity should be between 2 and 5  |
      | ProductKitItem2Quantity | The quantity should be between 3 and 10 |
    And fill "Order Form" with:
      | ProductKitItem1Quantity | 2 |
      | ProductKitItem2Quantity | 3 |
    And the "Price" field should be readonly in form "Order Form"

  Scenario: Add a product kit line item with min quantity violation
    When fill "Order Form" with:
      | ProductKitItem1Quantity | 1 |
      | ProductKitItem2Quantity | 2 |
    And click "Calculate Shipping Button"
    And I save form
    Then I should see "Order Form" validation errors:
      | ProductKitItem1Quantity | The quantity should be between 2 and 5  |
      | ProductKitItem2Quantity | The quantity should be between 3 and 10 |
    And fill "Order Form" with:
      | ProductKitItem1Quantity | 2 |
      | ProductKitItem2Quantity | 3 |
    And the "Price" field should be readonly in form "Order Form"

  Scenario: Check unit precisions in the Quantity tooltip
    When I click on "Order Form Line Item 1 Kit Item 1 Quantity Label Tooltip"
    Then I should see "The quantity of product kit item units to be purchased: piece (fractional, 1 decimal digit)" in the "Tooltip Popover Content" element
    And I click on empty space
    When I click on "Order Form Line Item 1 Kit Item 2 Quantity Label Tooltip"
    Then I should see "The quantity of product kit item units to be purchased: piece (whole numbers)" in the "Tooltip Popover Content" element
    And I click on empty space

  Scenario: Add a product kit line item with unit precision violation
    When fill "Order Form" with:
      | ProductKitItem1Quantity | 2.45 |
      | ProductKitItem2Quantity | 3.34 |
    And click "Calculate Shipping Button"
    And I save form
    Then I should see "Order Form" validation errors:
      | ProductKitItem1Quantity | Only 1 decimal digit are allowed for unit "piece" |
      | ProductKitItem2Quantity | Only whole numbers are allowed for unit "piece" |
    And fill "Order Form" with:
      | ProductKitItem1Quantity | 2 |
      | ProductKitItem2Quantity | 3 |
    And the "Price" field should be readonly in form "Order Form"

  Scenario: Add a product kit line item with non-numeric quantity violation
    When fill "Order Form" with:
      | ProductKitItem1Quantity | invalid |
      | ProductKitItem2Quantity | invalid |
    Then I should see "Order Form" validation errors:
      | ProductKitItem1Quantity | This value should be decimal number. |
      | ProductKitItem2Quantity | This value should be decimal number. |
    And the "Price" field should be readonly in form "Order Form"
    And fill "Order Form" with:
      | ProductKitItem1Quantity | 2 |
      | ProductKitItem2Quantity | 3 |
    And the "Price" field should be readonly in form "Order Form"

  Scenario: Add a product kit line item with missing price violation
    When fill "Order Form" with:
      | ProductKitItem2Product | simple-product-02 - Simple Product 02 |
    Then "Order Form" must contains values:
      | ProductKitItem2Price |  |
    And the "Price" field should be readonly in form "Order Form"
    When I save form
    Then I should see "Order Form" validation errors:
      | ProductKitItem2Price | Price value should not be blank. |
    And the "Price" field should be readonly in form "Order Form"

  Scenario: Add a product kit line item with empty price violation
    When fill "Order Form" with:
      | ProductKitItem1Price |  |
      | ProductKitItem2Price |  |
    Then I should see "Order Form" validation errors:
      | ProductKitItem1Price | Price value should not be blank. |
      | ProductKitItem2Price | Price value should not be blank. |
    And the "Price" field should be readonly in form "Order Form"

  Scenario: Add a product kit line item with negative price violation
    When fill "Order Form" with:
      | ProductKitItem1Price | -1 |
      | ProductKitItem2Price | -1 |
    Then I should see "Order Form" validation errors:
      | ProductKitItem1Price | This value should be 0 or more. |
      | ProductKitItem2Price | This value should be 0 or more. |
    And the "Price" field should be readonly in form "Order Form"

  Scenario: Add a product kit line item with non-numeric price violation
    When fill "Order Form" with:
      | ProductKitItem1Price | invalid |
      | ProductKitItem2Price | invalid |
    Then I should see "Order Form" validation errors:
      | ProductKitItem1Price | This value should be of type numeric. |
      | ProductKitItem2Price | This value should be of type numeric. |
    And the "Price" field should be readonly in form "Order Form"

  Scenario: Check that zero kit item prices are still valid
    When fill "Order Form" with:
      | ProductKitItem1Price | 0 |
      | ProductKitItem2Price | 0 |
    And I click "Order Form Line Item 1 Price Overridden"
    And I click "Reset price"
    And I click on empty space
    And the "Price" field should be readonly in form "Order Form"
    And click "Calculate Shipping Button"
    And I save and close form
    Then I should see "Order Total: $123.46"
