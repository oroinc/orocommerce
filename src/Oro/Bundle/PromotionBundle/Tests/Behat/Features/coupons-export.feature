@fixture-OroOrganizationBundle:BusinessUnit.yml
@fixture-OroPromotionBundle:promotions.yml
@fixture-OroPromotionBundle:export-coupons.yml
Feature: Export of Coupons codes
  As an Administrator
  I want to be able to export coupon codes via Management Console UI

  Scenario: Export Coupon Codes
    Given I login as administrator
    When I go to Marketing/Promotions/Coupons
    When I press "Export"
    Then I should see "Export started successfully. You will receive email notification upon completion." flash message
    And Exported file for "Coupon" contains the following data:
      | Coupon Code | Used  | Uses per Coupon | Uses per Person | Valid Until         | Promotion Rule Name           | Owner Name          |
      | test-1      | 0     | 3               | 2               | 01/01/2020 00:00:00 | order Discount Promotion      | Main                |
      | test-2      | 0     | 4               | 3               | 10/10/2010 10:00:00 | line Item Discount Promotion  | Main                |
      | test-3      | 0     | 5               | 4               |                     |                               | Main                |
      | test-4      | 0     | 6               | 5               |                     |                               | Child Business Unit |
      | test-5      | 0     | 7               | 6               |                     |                               | Child Business Unit |
