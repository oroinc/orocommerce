@fixture-OroOrganizationBundle:BusinessUnit.yml
@fixture-OroPromotionBundle:promotions.yml
Feature: Import of Coupons codes
  As an Administrator
  I want to be able to import coupon codes via Management Console UI

  Scenario: Data Template for Coupons
    Given I login as administrator
    And I go to Marketing/Promotions/Coupons
    And number of records should be 0
    When I download "Coupon" Data Template file
    Then I see Coupon Code column
    And I see Used column
    And I see Uses per Coupon column
    And I see Uses per Customer column
    And I see Valid Until column
    And I see Promotion Rule Name column
    And I see Owner Name column
    And I don't see Id column
    And I don't see Created at column
    And I don't see Updated at column
    And I don't see Organization column

  Scenario: Import new Coupons
    Given I fill template with data:
      | Coupon Code | Used  | Uses per Coupon | Uses per Customer | Valid Until         | Promotion Rule Name          | Owner Name           |
      | test1       | 1     | 101             | 91                | 01/01/2020 00:00:00 | order Discount Promotion     | Main                 |
      | test2       | 2     | 102             | 92                | 10/10/2010 10:00:00 | line Item Discount Promotion | Main                 |
      | test3       | 3     | 103             | 93                |                     |                              | Main                 |
      | test4       | 4     | 104             | 94                |                     |                              | Child Business Unit  |
      | test5       | 5     | 105             | 95                |                     |                              | Child Business Unit  |
    When I import file
    And I reload the page
    Then I should see following grid:
      | Coupon Code | Promotion                    | Used  | Valid Until            | Uses per Coupon | Uses per Customer |
      | test1       | order Discount Promotion     | 1     | Jan 1, 2020, 12:00 AM  | 101             | 91                |
      | test2       | line Item Discount Promotion | 2     | Oct 10, 2010, 10:00 AM | 102             | 92                |
      | test3       | N/A                          | 3     |                        | 103             | 93                |
      | test4       | N/A                          | 4     |                        | 104             | 94                |
      | test5       | N/A                          | 5     |                        | 105             | 95                |
    And number of records should be 5
    When I click on test5 in grid
    Then I should see "Owner: Child Business Unit"
