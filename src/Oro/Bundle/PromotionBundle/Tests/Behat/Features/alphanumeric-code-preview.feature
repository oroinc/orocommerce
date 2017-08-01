Feature: Preview of alphanumeric coupon code in coupon generation form

  Scenario: Preview of alphanumeric code with custom length and without dashes
    Given I login as administrator
    And go to Marketing/Promotions/Coupons
    And I click "Generate Coupon"
    When I fill "Coupon Generation Form" with:
      | Code Type   | Alphanumeric |
      | Code Length | 10           |
    Then I should see text matching "Code Preview alphanum12"

  Scenario: Preview of alphanumeric code with dashes
    Given go to Marketing/Promotions/Coupons
    And I click "Generate Coupon"
    When I fill "Coupon Generation Form" with:
      | Code Type       | Alphanumeric |
      | Dashes Sequence | 2            |
    Then I should see text matching "Code Preview al-ph-an-um-12-34"

  Scenario: Preview of alphanumeric code with code code prefix, suffix, dashes and  custom code length
    Given go to Marketing/Promotions/Coupons
    And I click "Generate Coupon"
    When I fill "Coupon Generation Form" with:
      | Code Length     | 8            |
      | Code Type       | Alphanumeric |
      | Code Prefix     | hello        |
      | Code Suffix     | kitty        |
      | Dashes Sequence | 2            |
    Then I should see text matching "Code Preview he-ll-oa-lp-ha-nu-mk-it-ty"