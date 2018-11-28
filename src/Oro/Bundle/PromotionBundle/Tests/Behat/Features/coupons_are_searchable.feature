@regression
@ticket-BB-15172
@fixture-OroPromotionBundle:coupons-searchable.yml
Feature: Coupons are searchable
  In order to be able quickly find coupons
  As an Administrator
  I need to have an ability to use admin search for looking coupons

  Scenario: Search coupons by coupon code
    Given I login as administrator
    When I follow "Search"
    And type "test" in "search"
    And I should see 1 search suggestions
    When I click "Go"
    Then I should be on Search Result page
    And I should see following search entity types:
      | Type        | N | isSelected |
      | All         | 1 | yes        |
      | Coupons     | 1 |            |
    And number of records should be 1
    And I should see following search results:
      | Title       | Type    |
      | test-12345  | Coupon  |
