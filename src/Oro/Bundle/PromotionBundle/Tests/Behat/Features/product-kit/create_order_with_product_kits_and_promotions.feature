@feature-BB-21128
@fixture-OroPromotionBundle:product-kit/create_order_with_product_kits_and_promotions.yml

Feature: Create Order with Product Kits and Promotions

  Scenario: Add a product kit line item
    Given I login as administrator
    And go to Sales / Orders
    And click "Create Order"
    And I click "Add Product"
    When I fill "Order Form" with:
      | Customer      | Customer1      |
      | Customer User | Amanda Cole    |
      | Product       | product-kit-01 |
    Then I see next line item discounts for backoffice order:
      | SKU            | Row Total Incl Tax | Row Total Excl Tax | Discount            |
      | product-kit-01 | $112.221           | $112.221           | $12.468999999999999 |
    And I see next subtotals for "Backend Order":
      | Subtotal | $124.69 |
      | Discount | -$12.47 |
      | Total    | $112.22 |
    And the "Price" field should be readonly in form "Order Form"

  Scenario: Change product kit line item quantity
    When I fill "Order Form" with:
      | Quantity | 2 |
    Then I see next line item discounts for backoffice order:
      | SKU            | Row Total Incl Tax | Row Total Excl Tax | Discount            |
      | product-kit-01 | $224.442           | $224.442           | $24.937999999999999 |
    And I see next subtotals for "Backend Order":
      | Subtotal | $249.38 |
      | Discount | -$24.94 |
      | Total    | $224.44 |
    And the "Price" field should be readonly in form "Order Form"

  Scenario: Add product kit item line item product
    When I fill "Order Form" with:
      | ProductKitItem1Product | simple-product-03 - Simple Product 03 |
    Then I see next line item discounts for backoffice order:
      | SKU            | Row Total Incl Tax | Row Total Excl Tax | Discount            |
      | product-kit-01 | $231.102           | $231.102           | $25.677999999999997 |
    And I see next subtotals for "Backend Order":
      | Subtotal | $256.78 |
      | Discount | -$25.68 |
      | Total    | $231.10 |
    And the "Price" field should be readonly in form "Order Form"

  Scenario: Change product kit item line item product
    When I fill "Order Form" with:
      | ProductKitItem2Product | simple-product-02 - Simple Product 02 |
    Then I see next line item discounts for backoffice order:
      | SKU            | Row Total Incl Tax | Row Total Excl Tax | Discount            |
      | product-kit-01 | $233.334           | $233.334           | $25.925999999999998 |
    And I see next subtotals for "Backend Order":
      | Subtotal | $259.26 |
      | Discount | -$25.93 |
      | Total    | $233.33 |
    And the "Price" field should be readonly in form "Order Form"

  Scenario: Change product kit item line item quantity
    When I fill "Order Form" with:
      | ProductKitItem1Quantity | 2 |
      | ProductKitItem2Quantity | 3 |
    Then I see next line item discounts for backoffice order:
      | SKU            | Row Total Incl Tax | Row Total Excl Tax | Discount            |
      | product-kit-01 | $248.886           | $248.886           | $27.654000000000003 |
    And I see next subtotals for "Backend Order":
      | Subtotal | $276.54 |
      | Discount | -$27.65 |
      | Total    | $248.89 |
    And the "Price" field should be readonly in form "Order Form"

  Scenario: Change product kit item line item price
    When I fill "Order Form" with:
      | ProductKitItem1Price | 12.3456 |
      | ProductKitItem2Price | 23.4567 |
    Then I see next line item discounts for backoffice order:
      | SKU            | Row Total Incl Tax | Row Total Excl Tax | Discount           |
      | product-kit-01 | $393.376           | $393.376           | $43.70400000000001 |
    And I see next subtotals for "Backend Order":
      | Subtotal | $437.04 |
      | Discount | -$43.70 |
      | Total    | $393.34 |
    And the "Price" field should be readonly in form "Order Form"

#   TODO: Should be uncommented after implementation BB-23120 feature
#  Scenario: Change product kit line item price
#    When I fill "Order Form" with:
#      | Price | 100.00 |
#    Then I see next line item discounts for backoffice order:
#      | SKU            | Row Total Incl Tax | Row Total Excl Tax | Discount |
#      | product-kit-01 | $180.04            | $180.04            | $20.00   |
#    And I see next subtotals for "Backend Order":
#      | Subtotal | $200.00 |
#      | Discount | -$20.00 |
#      | Total    | $180.00 |
#
#  Scenario: Reset product kit line item price
#    When I click "Order Form Line Item 1 Price Overridden"
#    And I click "Reset price"
#    And I click on empty space
#    Then I see next line item discounts for backoffice order:
#      | SKU            | Row Total Incl Tax | Row Total Excl Tax | Discount           |
#      | product-kit-01 | $393.376           | $393.376           | $43.70400000000001 |
#    And I see next subtotals for "Backend Order":
#      | Subtotal | $437.04 |
#      | Discount | -$43.70 |
#      | Total    | $393.34 |

  Scenario: Add one more product kit line item
    When I click "Add Product"
    And fill "Order Form" with:
      | Product2  | product-kit-01 |
      | Quantity2 | 3              |
    Then I see next line item discounts for backoffice order:
      | SKU            | Row Total Incl Tax | Row Total Excl Tax | Discount            |
      | product-kit-01 | $393.376           | $393.376           | $43.70400000000001  |
      | product-kit-01 | $336.663           | $336.663           | $37.406999999999996 |
    And I see next subtotals for "Backend Order":
      | Subtotal | $811.11 |
      | Discount | -$81.11 |
      | Total    | $730.00 |
    And the "Price" field should be readonly in form "Order Form"
    And the "Price2" field should be readonly in form "Order Form"

  Scenario: Save order and check discounts
    When I save form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message
    And I see next line item discounts for backoffice order:
      | SKU            | Row Total Incl Tax | Row Total Excl Tax | Discount |
      | product-kit-01 | $393.376           | $393.376           | $43.704  |
      | product-kit-01 | $336.663           | $336.663           | $37.407  |
    And I see next subtotals for "Backend Order":
      | Subtotal | $811.11 |
      | Discount | -$81.11 |
      | Total    | $730.00 |
    And the "Price" field should be readonly in form "Order Form"
    And the "Price2" field should be readonly in form "Order Form"
