@regression
@feature-BB-25722

Feature: Case insensitive handling of coupon codes
  The system should treat coupon codes as case-insensitive to avoid conflicts and ensure consistency.
  As an administrator, I want to prevent the creation of multiple coupons that differ only by letter casing,
  so that promotions remain unique, unambiguous, and do not cause errors during validation or usage.

  Scenario: Create a coupon with code 'Code1'
    Given I login as administrator
    And go to Marketing/Promotions/Coupons
    And click "Coupons Actions"
    And click "Create Coupon"
    When I fill "Coupon Form" with:
      | Coupon Code | Code1 |
      | Enabled     | true  |
    And save and close form
    Then I should see "Coupon has been saved" flash message

  Scenario: Create a coupon with same code 'CODE1' in different case
    Given I go to Marketing/Promotions/Coupons
    And click "Coupons Actions"
    And click "Create Coupon"
    When I fill "Coupon Form" with:
      | Coupon Code | CODE1 |
      | Enabled     | true  |
    And save and close form
    Then I should see "Coupon has been saved" flash message

  Scenario: Enable case-insensitive mode with duplicate codes present
    Given I go to System/ User Management/ Organizations
    And click "Configuration" on row "ORO" in grid
    And follow "Commerce/Sales/Promotions" on configuration sidebar
    When I check "Case-Insensitive Coupon Codes"
    And click "Save settings"
    Then I should see "Case-insensitive coupon codes cannot be enabled due to existing duplicate codes with different letter cases."

  Scenario: Delete one of the duplicate coupon codes
    Given I go to Marketing/Promotions/Coupons
    When I click delete "CODE1" in grid
    And I click "Yes, Delete" in modal window
    Then I should see "Coupon deleted" flash message

  Scenario: Enable case-insensitive mode after resolving duplicates
    Given I go to System/ User Management/ Organizations
    And click "Configuration" on row "ORO" in grid
    And follow "Commerce/Sales/Promotions" on configuration sidebar
    When I check "Case-Insensitive Coupon Codes"
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Try to create duplicate coupon in different case after enabling mode
    Given I go to Marketing/Promotions/Coupons
    And click "Coupons Actions"
    And click "Create Coupon"
    When I fill "Coupon Form" with:
      | Coupon Code | CODE1 |
      | Enabled     | true  |
    And save and close form
    Then I should see "A coupon code with the same characters but different casing already exists due to the enabled Case-Insensitive setting."
