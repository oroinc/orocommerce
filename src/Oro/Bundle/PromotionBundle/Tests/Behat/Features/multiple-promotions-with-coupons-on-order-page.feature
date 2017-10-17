@ticket-BB-11877
@automatically-ticket-tagged
@fixture-OroPromotionBundle:multiple-promotions-with-coupons-on-order-page.yml
Feature: Multiple promotions with coupons on Order page
  In order to manage multiple promotions with coupons on order page
  As administrator
  I need to have ability to add and remove applied promotions by coupons on order edit page

  Scenario: Apply and Save multiple promotions in the order
    Given I login as administrator
    And go to Sales / Orders
    And click edit SimpleOrder in grid
    And click "Promotions and Discounts"
    When I click "Add Coupon Code"

    # Add all 4 promotions by coupons
    And type "test-1" in "Coupon Code"
    Then I should see a "Highlighted Suggestion" element
    When I click on "Highlighted Suggestion"
    And click "Add" in modal window
    And type "test-2" in "Coupon Code"
    Then I should see a "Highlighted Suggestion" element
    When I click on "Highlighted Suggestion"
    And click "Add" in modal window
    And type "test-3" in "Coupon Code"
    Then I should see a "Highlighted Suggestion" element
    When I click on "Highlighted Suggestion"
    And click "Add" in modal window
    And type "test-4" in "Coupon Code"
    Then I should see a "Highlighted Suggestion" element
    When I click on "Highlighted Suggestion"
    And click "Add" in modal window
    Then I should see next rows in "Added Coupons" table
      | Coupon Code | Promotion     | Type        | Discount Value |
      | test-1      | Promotion 10  | Order Total | $1.00          |
      | test-2      | Promotion -10 | Order Total | $1.00          |
      | test-3      | Promotion 20  | Order Total | $1.00          |
      | test-4      | Promotion 5   | Order Total | $1.00          |
    When click "Apply" in modal window
    And I save form
    And agree that shipping cost may have changed
    Then I should see next rows in "Promotions" table
      | Code   | Promotion     | Type        | Status | Discount |
      | test-2 | Promotion -10 | Order Total | Active | -$1.00   |
      | test-4 | Promotion 5   | Order Total | Active | -$1.00   |
      | test-1 | Promotion 10  | Order Total | Active | -$1.00   |
      | test-3 | Promotion 20  | Order Total | Active | -$1.00   |

    Scenario: Remove promotions and remove one of applied promotions in order
      When I click "Cancel"
      And go to Marketing / Promotions / Promotions
      And check all records in grid
      And click Delete mass action
      And confirm deletion
      Then there is no records in grid
      When go to Sales / Orders
      And click edit SimpleOrder in grid
      And click "Promotions and Discounts"
      And I click "Remove" on row "Promotion 5" in "Promotions"
      Then I should see next rows in "Promotions" table
        | Code   | Promotion     | Type        | Status | Discount |
        | test-2 | Promotion -10 | Order Total | Active | -$1.00   |
        | test-1 | Promotion 10  | Order Total | Active | -$1.00   |
        | test-3 | Promotion 20  | Order Total | Active | -$1.00   |
