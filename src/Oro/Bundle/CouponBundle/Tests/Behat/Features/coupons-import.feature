Feature: Import of Coupons codes
  As an Administrator
  I want to be able to import coupon codes via Management Console UI

  Scenario: Data Template for Coupons
    Given I login as administrator
    And I go to Marketing/Promotion/Coupons
    And number of records should be 0
    When I download "Coupon" Data Template file
    Then I see Coupon Code column
    And I see Used column
    And I see Uses per Coupon column
    And I see Uses per Customer column
    And I see Owner Name column
    And I don't see Id column
    And I don't see Created at column
    And I don't see Updated at column
    And I don't see Organization column

  Scenario: Import new Coupons
    Given I fill template with data:
      | Coupon Code | Used  | Uses per Coupon | Uses per Customer | Owner Name |
      | test-1      | 1     | 101             | 91                | Main       |
      | test-2      | 2     | 102             | 92                | Main       |
      | test-3      | 3     | 103             | 93                | Main       |
      | test-4      | 4     | 104             | 94                | Main       |
      | test-5      | 5     | 105             | 95                | Main1      |
    When I import file
    And I reload the page
    Then I should see following grid:
      | Coupon Code | Used  | Uses per Coupon | Uses per Customer |
      | test-1      | 1     | 101             | 91                |
      | test-2      | 2     | 102             | 92                |
      | test-3      | 3     | 103             | 93                |
      | test-4      | 4     | 104             | 94                |
      | test-5      | 5     | 105             | 95                |
    And number of records should be 5
