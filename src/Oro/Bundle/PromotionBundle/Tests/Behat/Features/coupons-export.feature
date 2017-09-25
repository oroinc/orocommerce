@fixture-OroOrganizationBundle:BusinessUnit.yml
@fixture-OroPromotionBundle:promotions_for_coupons.yml
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
      | Coupon Code |  Uses per Coupon | Uses per Person | Valid Until         | Promotion Rule Name           | Owner Name          |
      | test-1      |  3               | 2               | 01/01/2020 00:00:00 | order Discount Promotion      | Main                |
      | test-2      |  4               | 3               | 10/10/2010 10:00:00 | line Item Discount Promotion  | Main                |
      | test-3      |  5               | 4               |                     |                               | Main                |
      | test-4      |  6               | 5               |                     |                               | Child Business Unit |
      | test-5      |  7               | 6               |                     |                               | Child Business Unit |
