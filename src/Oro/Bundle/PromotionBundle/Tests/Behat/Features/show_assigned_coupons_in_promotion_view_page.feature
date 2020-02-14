@fixture-OroPromotionBundle:show-assigned-coupons-in-promotion-view-page.yml

Feature: Show Assigned Coupons in Promotion view page
  In order to manage promotion's coupons
  As an Administrator
  I want to see Assigned Coupons grid on Promotion view page

  Scenario: Assigned Coupons are displayed on Promotion view page
    Given I login as administrator
    When I go to Marketing/Promotions/Coupons
    And I check all records in grid
    And I click "Edit" link from mass action dropdown
    Then I should see "Mass Coupon Edit"
    When I fill form with:
      |Promotion| order Discount Promotion |
    And I click "Apply"
    And go to Marketing / Promotions / Promotions
    And click view "order Discount Promotion" in grid
    Then I should see following "Assigned Coupons Grid" grid:
      | Coupon code| Uses Per Coupon | Uses Per Person |
      | test1      |1                |1                |
      | test2      |1                |1                |
      | test3      |1                |1                |
      | test4      |1                |1                |
      | test5      |1                |1                |
      | test6      |1                |1                |

  Scenario: Unassign action should detach coupon from promotion
    Given I click "Assigned Coupons"
    And click Unassign "test1" in grid
    And click "Cancel"
    And click Unassign "test1" in grid
    And click "Yes"
    Then I should not see "test1"

  Scenario: Should be ability to edit coupon
    Given I click edit "test2" in grid
    When I fill "Coupon Form" with:
      | Coupon Code     | test23                           |
      | Uses per Coupon | 3                                |
      | Uses per Person | 3                                |
      | Valid From      | <DateTime:Jul 10, 2018, 10:00 AM>|
      | Valid Until     | <DateTime:Jul 10, 2018, 10:00 AM>|
    And click "Save"
    Then I should see following "Assigned Coupons Grid" grid:
      | Coupon Code| Uses per Coupon | Uses per Person | Valid Until            |
      | test23     | 3               | 3               | Jul 10, 2018, 10:00 AM |

  Scenario: Should be ability to delete coupon
    Given I click delete "test23" in grid
    And click "Cancel"
    And click delete "test4" in grid
    And click "Yes, Delete"
    Then I should not see "test4"

  Scenario: Should be ability to export coupons
    Given I click on "Export Grid"
    And click on "CSV"
    Then I should see "Export started successfully. You will receive email notification upon completion." flash message
    And Email should contains the following "Grid export performed successfully. Download" text

  Scenario: Should be ability for bulk delete coupons
    Given I check first 2 records in "Assigned Coupons Grid"
    And I click "Delete" link from mass action dropdown
    And click "Yes, Delete"
    Then I should see following "Assigned Coupons Grid" grid:
      | Coupon Code| Uses per Coupon | Uses per Person |
      | test5      |1                |1                |
      | test6      |1                |1                |

  Scenario: Should be ability for bulk unassign coupons
    Given I check first 2 records in "Assigned Coupons Grid"
    And I click "Delete" link from mass action dropdown
    And click "Yes"
    Then there is no records in "Assigned Coupons Grid"
