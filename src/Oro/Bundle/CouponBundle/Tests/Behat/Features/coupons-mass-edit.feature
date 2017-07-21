@fixture-OroPromotionBundle:promotions.yml
@fixture-OroCouponBundle:coupons.yml
Feature: Mass edit of Coupons codes
  As an Administrator
  I want to be able to mass edit coupon codes via Management Console UI

  Scenario: Partial mass edit
    Given I login as administrator
    When I go to Marketing/Promotion/Coupons
    And I sort grid by Uses per Coupon
    And I check first 2 records in grid
    And I click "Edit" link from mass action dropdown
    Then I should see "Mass Coupon Edit"
    When I fill form with:
      |Promotion         | order Discount Promotion |
      |Uses per Coupon   |77                        |
      |Uses per Customer |88                        |
    And I click "Apply"
    Then I should see "2 entities were edited" flash message
    And I filter Uses per Coupon as = "77"
    And I filter Uses per Customer as = "88"
    And I filter Promotion as contains "order Discount Promotion"
    And there are 2 records in grid

  Scenario: Mass edit of all visible
    Given I reset "Uses per Coupon" filter
    Given I reset "Uses per Customer" filter
    Given I reset "Promotion" filter
    When I select 10 from per page list dropdown
    And I check All Visible records in grid
    And I click "Edit" link from mass action dropdown
    Then I should see "Mass Coupon Edit"
    When I fill form with:
      |Uses per Coupon   |88        |
      |Uses per Customer |99        |
    And I click "Apply"
    Then I should see "10 entities were edited" flash message
    And I filter Uses per Coupon as = "88"
    And I filter Uses per Customer as = "99"
    And I filter Promotion as is empty
    And there are 10 records in grid

  Scenario: Mass edit of all
    Given I reset "Uses per Coupon" filter
    Given I reset "Uses per Customer" filter
    Given I reset "Promotion" filter
    When I select 25 from per page list dropdown
    And I check all records in grid
    And I click "Edit" link from mass action dropdown
    Then I should see "Mass Coupon Edit"
    When I fill form with:
      |Uses per Coupon   |99        |
      |Uses per Customer |100       |
    And I click "Apply"
    Then I should see "100 entities were edited" flash message
    And I filter Uses per Coupon as = "99"
    And I filter Uses per Customer as = "100"
    And there are 100 records in grid
