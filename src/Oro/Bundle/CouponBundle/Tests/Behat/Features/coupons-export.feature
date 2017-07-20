@fixture-OroCouponBundle:export-coupons.yml
Feature: Export of Coupons codes
  As an Administrator
  I want to be able to export coupon codes via Management Console UI

  Scenario: Export Coupon Codes
    Given I login as administrator
    When I go to Marketing/Promotion/Coupons
    When I press "Export"
    Then I should see "Export started successfully. You will receive email notification upon completion." flash message
    And Exported file for "Coupon" contains the following data:
      | Coupon Code | Used  | Uses per Coupon | Uses per Customer | Owner Name |
      | 1           | 0     | 3               | 2                 | Main       |
      | 2           | 0     | 4               | 3                 | Main       |
      | 3           | 0     | 5               | 4                 | Main       |
      | 4           | 0     | 6               | 5                 | Main       |
      | 5           | 0     | 7               | 6                 | Main       |
