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
    And I save form
    Then I should see next rows in "Promotions" table
      | Promotion       | Type        | Status | Discount |
      | Order Promotion | Order Total | Active |   -$7.00 |
    And I click "Cancel"

  Scenario: Coupon applying on order view page with already applied promotion
    When click view SimpleOrder in grid
    Then I should see next rows in "Promotions" table
      | Promotion       | Type        | Status | Discount |
      | Order Promotion | Order Total | Active |   -$7.00 |
    When I click "Add Coupon Code"
    And type "test-1" in "Coupon Code"
    Then I should see a "Highlighted Suggestion" element
    When click on "Highlighted Suggestion"
    And click "Add" in modal window
    Then I should see next rows in "Added Coupons" table
      | Coupon Code | Promotion                    | Type            | Discount Value |
      | test-1      | Line Item Discount Promotion | Order Line Item | $1.00          |
    When click "Apply" in modal window
    Then I should see next rows in "Promotions" table
      | Code   | Promotion                    | Type            | Status | Discount |
      | N/A    | Order Promotion              | Order Total     | Active |   -$7.00 |
      | test-1 | Line Item Discount Promotion | Order Line Item | Active |  -$10.00 |
    And I see next subtotals for "Backend Order":
      | Subtotal | Amount  |
      | Subtotal | $50.00  |
      | Discount | -$17.00 |
      | Total    | $33.00  |
