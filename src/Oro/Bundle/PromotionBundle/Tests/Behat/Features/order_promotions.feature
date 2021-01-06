@regression
@ticket-BB-19895
@fixture-OroPromotionBundle:order_promotions.yml

Feature: Order promotions
  Verify that all discounted actions work correctly if line items in order have been changed.

  Scenario: Recalculate discounts if line items not changed
    Given I login as administrator
    And go to Sales / Orders
    And click "edit" on first row in grid
    # Need to re-calculate discounts
    When I save form
    Then I should see "Order has been saved" flash message
    When click "Order Totals"
    Then I see next subtotals for "Backend Order":
      | Subtotal | $10.00 |
      | Total    | $10.00 |

  Scenario: Recalculate discounts if line items changed(automatically added promotions)
    Given I fill "Order Form" with:
      | Product | Product2 |
      | Price   | 20       |
    Then I should see next rows in "Promotions" table
      | Promotion                 | Type            | Status | Discount |
      | Order Line Item Promotion | Order Line Item | Active | -$2.00   |
      | Order Total Promotion     | Order Total     | Active | -$1.00   |
    When I click "Order Totals"
    Then I see next subtotals for "Backend Order":
      | Subtotal | $20.00 |
      | Discount | -$3.00 |
      | Total    | $17.00 |
    When I save form
    And click "Save" in modal window
    Then I should see "Order has been saved" flash message

  Scenario: Add coupon if line items not changed
    Given I click "Add Coupon Code"
    And type "OrderTotalCoupon" in "Coupon Code"
    And should see a "Highlighted Suggestion" element
    And click on "Highlighted Suggestion"
    When I click "Add" in modal window
    And click "Apply" in modal window
    Then should see next rows in "Promotions" table
      | Promotion                    | Type            | Status | Discount |
      | Order Line Item Promotion    | Order Line Item | Active | -$2.00   |
      | Order Total Promotion        | Order Total     | Active | -$1.00   |
      | Order Total Coupon Promotion | Order Total     | Active | -$3.00   |
    When I click "Order Totals"
    Then I see next subtotals for "Backend Order":
      | Subtotal | $20.00 |
      | Discount | -$6.00 |
      | Total    | $14.00 |

  Scenario: Add coupon if line items changed
    Given I fill "Order Form" with:
      | Product | Product1 |
      | Price   | 30       |
    Given I click "Add Coupon Code"
    And type "orderLineItemCoupon" in "Coupon Code"
    And should see a "Highlighted Suggestion" element
    And click on "Highlighted Suggestion"
    When I click "Add" in modal window
    When I click "Apply" in modal window
    Then should see next rows in "Promotions" table
      | Promotion                        | Type            | Status | Discount |
      | Order Line Item Promotion        | Order Line Item | Active | -$2.00   |
      | Order Total Promotion            | Order Total     | Active | -$1.00   |
      | Order Total Coupon Promotion     | Order Total     | Active | -$3.00   |
      | Order Line Item Coupon Promotion | Order Line Item | Active | -$4.00   |
    When I click "Order Totals"
    Then see next subtotals for "Backend Order":
      | Subtotal | $30.00  |
      | Discount | -$10.00 |
      | Total    | $20.00  |
    When I save form
    And click "Save" in modal window
    Then I should see "Order has been saved" flash message

  Scenario: Remove coupon if line items not changed
    Given I click "Remove" on row "Order Total Coupon Promotion" in "Promotions"
    Then should see next rows in "Promotions" table
      | Promotion                        | Type            | Status | Discount |
      | Order Line Item Promotion        | Order Line Item | Active | -$2.00   |
      | Order Total Promotion            | Order Total     | Active | -$1.00   |
      | Order Line Item Coupon Promotion | Order Line Item | Active | -$4.00   |
    When I click "Order Totals"
    Then see next subtotals for "Backend Order":
      | Subtotal | $30.00 |
      | Discount | -$7.00 |
      | Total    | $23.00 |

  Scenario: Remove coupon if line items changed
    Given I fill "Order Form" with:
      | Product | Product2 |
      | Price   | 40       |
    Given I click "Remove" on row "Order Line Item Coupon Promotion" in "Promotions"
    Then should see next rows in "Promotions" table
      | Promotion                 | Type            | Status | Discount |
      | Order Line Item Promotion | Order Line Item | Active | -$2.00   |
      | Order Total Promotion     | Order Total     | Active | -$1.00   |
    When I click "Order Totals"
    Then see next subtotals for "Backend Order":
      | Subtotal | $40.00 |
      | Discount | -$3.00 |
      | Total    | $37.00 |
    When I save form
    And click "Save" in modal window
    Then I should see "Order has been saved" flash message

  Scenario: Remove applied promotions if line items not changed
    Given I click "Remove" on row "Order Line Item Promotion" in "Promotions"
    And click "Remove" on row "Order Total Promotion" in "Promotions"
    When I click "Order Totals"
    Then see next subtotals for "Backend Order":
      | Subtotal | $40.00 |
      | Total    | $40.00 |

  # There is no need to remove promotions if line items have changed because they will be added again.
