@ticket-BB-20436
@regression
@fixture-OroPromotionBundle:product_for_report_with_discounts.yml

Feature: Report with the amount of discounts applied
  In order to be able to create report with the amount of applied discounts
  As an administrator
  I add the order with discounts and create the report in which the sum of all applied discounts of the order is calculated

  Scenario: Create order with discounts
    Given I login as administrator
    And go to Sales/Orders
    And click edit Order in grid
    When I fill "Order Form" with:
      | Customer      | first customer |
      | Customer User | Amanda Cole    |
      | Product       | SKU            |
    And click "Add Coupon Code"
    # Add first coupon
    And type "coupon-1" in "Coupon Code"
    And click on "Highlighted Suggestion"
    And click "Add" in modal window
    # Add second coupon
    And type "coupon-2" in "Coupon Code"
    And click on "Highlighted Suggestion"
    And click "Add" in modal window
    # Check and save
    Then I should see next rows in "Added Coupons" table
      | Coupon Code | Promotion          | Type        | Discount Value |
      | coupon-1    | Discount Promotion | Order Total | $10.00         |
      | coupon-2    | Discount Promotion | Order Total | $10.00         |
    And click "Apply" in modal window
    When I save and close form
    Then I should see "Review Shipping Cost"
    When I click "Save" in modal window
    Then I should see "Order has been saved" flash message

  Scenario: Create custom report
    Given I go to Reports & Segments/ Manage Custom Reports
    When I click "Create Report"
    And fill "Report Form" with:
      | Name        | Order with discounts sum |
      | Entity      | Order                    |
      | Report Type | Table                    |
    And add the following columns:
      | ID                                            | None | Order ID                |
      | Order Number                                  | None | Order Number            |
      | Applied Promotions->Applied Discounts->Amount | Sum  | Applied Discounts (SUM) |
    And add the following grouping columns:
      | ID           |
      | Order Number |
    And I save and close form
    Then I should see "Report saved" flash message
    And there are 1 records in grid
    And should see following grid:
      | Order ID | Order Number | Applied Discounts (SUM) |
      | 1        | Order        | 20.00                   |
