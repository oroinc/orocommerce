@fixture-OroPromotionBundle:promotions_for_coupons.yml
@fixture-OroPromotionBundle:coupons.yml
@regression
Feature: Mass edit of Coupons codes
  As an Administrator
  I want to be able to mass edit coupon codes via Management Console UI

  Scenario: Partial mass edit
    Given I login as administrator
    When I go to Marketing/Promotions/Coupons
    And I sort grid by Uses per Coupon
    And I check first 2 records in grid
    And I click "Edit" link from mass action dropdown
    Then I should see "Mass Coupon Edit"
    When I fill form with:
      | Promotion         | order Discount Promotion |
      | Uses per Coupon   | 77                       |
      | Uses per Person   | 88                       |
    And I focus on "Valid From" field
    And I click "Today"
    And I click "Apply"
    Then I should see "2 entities were edited" flash message
    And I filter Uses per Coupon as equals "77"
    And I filter Uses per Person as equals "88"
    And I filter Promotion as contains "order Discount Promotion"
    And there are 2 records in grid

  Scenario: Mass edit of all visible
    Given I reset "Uses per Coupon" filter
    Given I reset "Uses per Person" filter
    Given I reset "Promotion" filter
    When I select 10 from per page list dropdown
    And I check All Visible records in grid
    And I click "Edit" link from mass action dropdown
    Then I should see "Mass Coupon Edit"
    When I fill form with:
      | Uses per Coupon  | 88        |
      | Uses per Person  | 99        |
    And I focus on "Valid From" field
    And I click "Today"
    And I click "Apply"
    Then I should see "10 entities were edited" flash message
    And I filter Uses per Coupon as equals "88"
    And I filter Uses per Person as equals "99"
    And I filter Promotion as is empty
    And there are 10 records in grid

  Scenario: Mass edit of all
    Given I reset "Uses per Coupon" filter
    Given I reset "Uses per Person" filter
    Given I reset "Promotion" filter
    When I select 25 from per page list dropdown
    And I check all records in grid
    And I click "Edit" link from mass action dropdown
    Then I should see "Mass Coupon Edit"
    When I fill "Mass Coupon Edit Form" with:
      | Uses per Coupon  | 99        |
      | Uses per Person  | 100       |
      | Valid From       | <DateTime:Jul 09, 2017, 10:00 AM> |
      | Valid Until      | <DateTime:Jul 10, 2017, 10:00 AM> |
    And I click "Apply"
    Then I should see "100 entities were edited" flash message
    And I filter Uses per Coupon as equals "99"
    And I filter Uses per Person as equals "100"
    And there are 100 records in grid
    And I should see "Jul 10, 2017, 10:00 AM" in grid
