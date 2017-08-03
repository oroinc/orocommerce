Feature: Preview of alphabetic coupon code in coupon generation form

  Scenario: Preview of alphabetic code with custom length and without dashes
    Given I login as administrator
    And go to Marketing/Promotions/Coupons
    And I click "Generate Coupon"
    When I fill "Coupon Generation Form" with:
      | Code Type   | Alphabetic |
      | Code Length | 10         |
    Then I should see text matching "alphabetic"

  Scenario: Preview of alphabetic code with dashes
    Given go to Marketing/Promotions/Coupons
    And I click "Generate Coupon"
    When I fill "Coupon Generation Form" with:
      | Dashes Sequence | 2          |
      | Code Type       | Alphabetic |
    Then I should see text matching "Code Preview al-ph-ab-et-ic-cc"

  Scenario: Preview of Alphabetic code with code code prefix, suffix, dashes and  custom code length
    Given go to Marketing/Promotions/Coupons
    And I click "Generate Coupon"
    When I fill "Coupon Generation Form" with:
      | Code Length     | 8          |
      | Code Type       | Alphabetic |
      | Code Prefix     | hello      |
      | Code Suffix     | kitty      |
      | Dashes Sequence | 2          |
    Then I should see text matching "Code Preview he-ll-oa-lp-ha-be-tk-it-ty"