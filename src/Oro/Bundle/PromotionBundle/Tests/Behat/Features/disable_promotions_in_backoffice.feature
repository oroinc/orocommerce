@ticket-BB-17730
@fixture-OroPromotionBundle:promotions-with-coupons-on-order-page.yml
@fixture-OroPromotionBundle:promotions-with-coupons-on-order-view-page.yml
@fixture-OroPromotionBundle:disabled-promotion-order.yml

Feature: Disable promotions in backoffice
  As administrator
  I should not be able to see promotions and manipulate coupons buttons for orders crated with disabled promotions

  Scenario: See the promotions for order with enabled promotions
    Given I login as administrator
    When I go to Sales/Orders
    And click edit SimpleOrder in grid
    And fill "Order Form" with:
      | Product | Second Product |
      | Price   | 7              |
    And I save form
    And click "Save" in modal window
    Then I should see next rows in "Promotions" table
      | Promotion       | Type        | Status | Discount |
      | Order Promotion | Order Total | Active | -$7.00   |
    And I should see following buttons:
      | Add Coupon Code |
    When I save and close form
    Then I should see next rows in "Promotions" table
      | Promotion       | Type        | Status | Discount |
      | Order Promotion | Order Total | Active | -$7.00   |
    And I should see following buttons:
      | Add Coupon Code |

  Scenario: Do not see the promotions for order with disabled promotions
    Given I login as administrator
    When I go to Sales/Orders
    And click edit Disabled Promotions Order in grid
    And I save form
    Then I should see no records in "Promotions" table
    And I should not see following buttons:
      | Add Coupon Code |
    And I should see "Promotions are disabled for the order."
    When I save and close form
    Then I should see no records in "Promotions" table
    And I should not see following buttons:
      | Add Coupon Code |
    And I should see "Promotions are disabled for the order."
