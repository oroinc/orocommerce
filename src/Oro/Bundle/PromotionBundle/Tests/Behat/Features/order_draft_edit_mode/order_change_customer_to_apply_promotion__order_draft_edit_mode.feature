@feature-BB-26023-enabled
@regression
@ticket-BB-24934
@fixture-OroPromotionBundle:promotion_for_order.yml
@fixture-OroPromotionBundle:order_with_customer.yml

Feature: Order change customer to apply promotion - Order Draft Edit Mode
  Verify that all discouns calculate correctly if order customer have been changed.

  Scenario: Enable Order Draft Edit Mode
    Given I set configuration property "oro_order.enable_order_draft_edit_mode" to "1"

  Scenario: Recalculate discounts if customer not changed
    Given I login as administrator
    And go to Sales / Orders
    And click "edit" on first row in grid
    When click "Totals"
    Then I see next subtotals for "Backend Order":
      | Subtotal | $50.00 |
      | Discount | -$1.00 |
      | Total    | $49.00 |

  Scenario: Recalculate discounts if customer(automatically added promotions)
    When I fill "Order Form" with:
      | Customer | NoCustomerUser |
    Then I see next subtotals for "Backend Order":
      | Subtotal | $50.00 |
      | Total    | $50.00 |
    When I fill "Order Form" with:
      | Customer | WithCustomerUser |
    Then I see next subtotals for "Backend Order":
      | Subtotal | $50.00 |
      | Discount | -$1.00 |
      | Total    | $49.00 |
    And I click "Cancel"

  Scenario: Try to fill order form and switch customer then check added discount
    Given I click "Create Order"
    When I fill "Order Form" with:
      | Customer | NoCustomerUser |
    And fill "Order Line Item Draft Create Form" with:
      | Product | Product1 |
      | Price   | 50       |
    And click "Add Product"
    And I click "Totals"
    Then I see next subtotals for "Backend Order":
      | Subtotal | $50.00 |
      | Total    | $50.00 |
    When I fill "Order Form" with:
      | Customer | WithCustomerUser |
    Then I see next subtotals for "Backend Order":
      | Subtotal | $50.00 |
      | Discount | -$1.00 |
      | Total    | $49.00 |
