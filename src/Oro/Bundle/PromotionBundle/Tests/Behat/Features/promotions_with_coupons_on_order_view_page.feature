@fixture-OroPromotionBundle:promotions-with-coupons-on-order-page.yml
@fixture-OroPromotionBundle:promotions-with-coupons-on-order-view-page.yml
Feature: Promotions with coupons on Order view page
  In order to manage promotions with coupons on order view page
  As administrator
  I need to have ability to add promotions by coupons on order view page

  Scenario: Applying promotion to order
    Given I login as administrator
    When go to Sales / Orders
    And click edit SimpleOrder in grid
    # Triggered to re-calculate discounts
    And fill "Order Form" with:
      | Product  | Product1 |
      | Quantity | 11       |
      | Price    | 5        |
    # As promotion was created after order, so we are not applied it automatically
    # We can activate it manually by admin only during order update (add new products, change quantity etc)
    # It's inactive by default for already existed orders
    Then I should see next rows in "Promotions" table
      | Promotion       | Type        | Status   | Discount |
      | Order Promotion | Order Total | Inactive | $0.00    |
    When I click "Activate" on row "Order Promotion" in "Promotions"
    Then I should see next rows in "Promotions" table
      | Promotion       | Type        | Status   | Discount |
      | Order Promotion | Order Total | Active   | -$7.00   |
    And I save and close form
    And click "Save" in modal window

  Scenario: Coupon applying on order view page with already applied promotion
    Given I should see next rows in "Promotions" table
      | Promotion       | Type        | Status | Discount |
      | Order Promotion | Order Total | Active | -$7.00   |
    When I click "Add Coupon Code"
    # Coupon code was added after order created, but it still works for already existed order
    # And promotion applied (manually by admin) despite was added after order created
    And type "test-1" in "Coupon Code"
    Then I should see a "Highlighted Suggestion" element
    When click on "Highlighted Suggestion"
    And click "Add" in modal window
    Then I should see next rows in "Added Coupons" table
      | Coupon Code | Promotion                    | Type            | Discount Value |
      | test-1      | Line Item Discount Promotion | Order Line Item | $1.00          |
    When click "Apply" in modal window
    And I click "Promotions and Discounts"
    Then I should see next rows in "Promotions" table
      | Code   | Promotion                    | Type            | Status | Discount |
      |        | Order Promotion              | Order Total     | Active | -$7.00   |
      | test-1 | Line Item Discount Promotion | Order Line Item | Active | -$11.00  |
    And I see next subtotals for "Backend Order":
      | Subtotal | Amount  |
      | Subtotal | $55.00  |
      | Discount | -$18.00 |
      | Total    | $37.00  |
