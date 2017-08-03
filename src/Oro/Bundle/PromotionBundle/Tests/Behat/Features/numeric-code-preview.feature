Feature: Preview of numeric coupon code in coupon generation form

  Scenario: Preview of numeric code with custom length and without dashes
    Given I login as administrator
    And go to Marketing/Promotions/Coupons
    And I click "Generate Coupon"
    When I fill "Coupon Generation Form" with:
      | Code Type      | Numeric |
      | Code Length    | 10 |
    Then I should see text matching "Code Preview 1234567890"

  Scenario: Preview of numeric code with dashes
    Given go to Marketing/Promotions/Coupons
    And I click "Generate Coupon"
    When I fill "Coupon Generation Form" with:
      | Code Type       | Numeric |
      | Dashes Sequence | 2       |
    Then I should see text matching "Code Preview 12-34-56-78-90-12"

  Scenario: Preview of numeric code with code code prefix, suffix, dashes and  custom code length
    Given go to Marketing/Promotions/Coupons
    And I click "Generate Coupon"
    When I fill "Coupon Generation Form" with:
      | Code Length     | 8          |
      | Code Type       | Numeric    |
      | Code Prefix     | hello      |
      | Code Suffix     | kitty      |
      | Dashes Sequence | 2          |
    Then I should see text matching "Code Preview he-ll-o1-23-45-67-8k-it-ty"