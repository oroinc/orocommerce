@fixture-OroPromotionBundle:promotions-with-coupons-on-order-page.yml
Feature: Promotions with coupons on Order page
  In order to manage promotions with coupons on order page
  As administrator
  I need to have ability to add, remove and disable applied promotions by coupons on order edit page

  Scenario: Coupon applying on order edit page
    Given I login as administrator
    When go to Sales / Orders
    And click edit SimpleOrder in grid
    And click "Promotions and Discounts"
    When I click "Add Coupon Code"
    And type "test-1" in "Coupon Code"
    Then I should see a "Highlighted Suggestion" element
    When I click on "Highlighted Suggestion"
    And click "Add" in modal window
    Then I should see next rows in "Added Coupons" table
      | Coupon Code | Promotion                    | Type            | Discount Value |
      | test-1      | Line Item Discount Promotion | Order Line Item | $1.00          |
    When click "Apply" in modal window
    Then I should see next rows in "Promotions" table
      | Code   | Promotion                    | Type            | Status | Discount |
      | test-1 | Line Item Discount Promotion | Order Line Item | Active |  -$10.00 |
    And I see next subtotals for "Backend Order":
      | Subtotal | Amount  |
      | Subtotal | $50.00  |
      | Discount | -$10.00 |
      | Total    | $40.00  |
    When I save form
    And agree that shipping cost may have changed
    Then I see next subtotals for "Backend Order":
      | Subtotal | Amount  |
      | Subtotal | $50.00  |
      | Discount | -$10.00 |
      | Total    | $40.00  |

  Scenario: Possible to view details about added promotion in popup
    When I click "Promotions and Discounts"
    And I click "View" on row "Line Item Discount Promotion" in "Promotions"
    Then I should see "General Information"
    And I should see "Conditions"
    Then I click "Close Line Item Discount Promotion Details"

  Scenario: Delete added coupon from grid
    When I click "Promotions and Discounts"
    And I click "Remove" on row "Line Item Discount Promotion" in "Promotions"
    Then I should see no records in "Promotions" table
    And see next subtotals for "Backend Order":
      | Subtotal | Amount |
      | Subtotal | $50.00 |
      | Total    | $50.00 |
    When I save form
    And agree that shipping cost may have changed
    Then I see next subtotals for "Backend Order":
      | Subtotal | Amount  |
      | Subtotal | $50.00  |
      | Total    | $50.00  |

  Scenario: Coupon applying on order view page
    When go to Sales / Orders
    And click view SimpleOrder in grid
    And I click "Add Coupon Code"
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
      | test-1 | Line Item Discount Promotion | Order Line Item | Active |  -$10.00 |
    And I see next subtotals for "Backend Order":
      | Subtotal | Amount  |
      | Subtotal | $50.00  |
      | Discount | -$10.00 |
      | Total    | $40.00  |

  Scenario: Check whether the promotion coupon have affected the total amount
    Given go to Sales/Orders
    Then I should see SimpleOrder in grid with following data:
      | Currency  | USD    |
      | Total     | $40.00 |
      | Total ($) | $40.00 |

  Scenario: "Cancel" button do not save selected coupons
    When click view SecondOrder in grid
    And I click "Add Coupon Code"
    And type "test-1" in "Coupon Code"
    Then I should see a "Highlighted Suggestion" element
    When click on "Highlighted Suggestion"
    And click "Add" in modal window
    Then I should see next rows in "Added Coupons" table
      | Coupon Code | Promotion                    | Type            | Discount Value |
      | test-1      | Line Item Discount Promotion | Order Line Item | $1.00          |
    And I click "Cancel" in modal window
    Then I should see no records in "Promotions" table

  Scenario: Deactivate button in Promotions grid
    When go to Sales / Orders
    And click edit SimpleOrder in grid
    And click "Promotions and Discounts"
    And I click "Deactivate" on row "Line Item Discount Promotion" in "Promotions"
    Then I should see next rows in "Promotions" table
      | Code   | Promotion                    | Type            | Status   | Discount |
      | test-1 | Line Item Discount Promotion | Order Line Item | Inactive | $0.00    |
    And I see next subtotals for "Backend Order":
      | Subtotal | Amount |
      | Subtotal | $50.00 |
      | Total    | $50.00 |

  Scenario: Activate button in Promotions grid
    When I click "Activate" on row "Line Item Discount Promotion" in "Promotions"
    Then I should see next rows in "Promotions" table
      | Code   | Promotion                    | Type            | Status | Discount |
      | test-1 | Line Item Discount Promotion | Order Line Item | Active | -$10.00  |
    And I see next subtotals for "Backend Order":
      | Subtotal | Amount  |
      | Subtotal | $50.00  |
      | Discount | -$10.00 |
      | Total    | $40.00  |

  Scenario: If Line item was deleted from order, applied Line Item promotion should be deleted from order as well
    When I click "Remove" on row "AA1" in "Backend Order Line Items"
    Then I should see no records in "Promotions" table
    And I see next subtotals for "Backend Order":
      | Subtotal | Amount |
      | Total    | $0.00  |
    And click "Cancel"

  Scenario: Product was changed in order
    Given I click edit SimpleOrder in grid
    Then I should see next rows in "Promotions" table
      | Code   | Promotion                    | Type            | Status | Discount |
      | test-1 | Line Item Discount Promotion | Order Line Item | Active | -$10.00  |
    And I see next subtotals for "Backend Order":
      | Subtotal | Amount  |
      | Subtotal | $50.00  |
      | Discount | -$10.00 |
      | Total    | $40.00  |
    When I fill "Order Form" with:
      | Product | XX1 |
    Then I should see no records in "Promotions" table
    And I see next subtotals for "Backend Order":
      | Subtotal | Amount |
      | Total    | $0.00  |
    And click "Cancel"

  Scenario: Promotion is applied, Order was saved. Promotion was deleted, order promotions should not be changed
    Given I go to Marketing / Promotions / Promotions
    And click delete Line Item Discount Promotion in grid
    And I confirm deletion
    And go to Sales / Orders
    And click edit SimpleOrder in grid
    And fill "Order Form" with:
      | Quantity | 2 |
    Then I should see next rows in "Promotions" table
      | Code   | Promotion                    | Type            | Status | Discount |
      | test-1 | Line Item Discount Promotion | Order Line Item | Active | -$2.00   |
    And see next subtotals for "Backend Order":
      | Subtotal | Amount |
      | Subtotal | $10.00 |
      | Discount | -$2.00 |
      | Total    | $8.00  |
    When I save and close form
    Then I should see next rows in "Promotions" table
      | Code   | Promotion                    | Type            | Status | Discount |
      | test-1 | Line Item Discount Promotion | Order Line Item | Active | -$2.00   |
    And see next subtotals for "Backend Order":
      | Subtotal | Amount |
      | Subtotal | $10.00 |
      | Discount | -$2.00 |
      | Total    | $8.00  |
