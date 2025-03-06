@ticket-BB-24934
@fixture-OroPromotionBundle:order_customer_promotions.yml

Feature: Order change customer to apply promotion
  Verify that all discouns calculate correctly if order customer have been changed.

  Scenario: Recalculate discounts if customer not changed
    Given I login as administrator
    And go to Sales / Orders
    And click "edit" on first row in grid
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
    And click "Add Product"
    When I fill "Order Form" with:
      | Customer | NoCustomerUser |
      | Product  | Product1       |
      | Price    | 50             |
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
