Feature: Preview of alphanumeric coupon code in coupon generation form

  Scenario: Preview of alphanumeric code with custom length and without dashes
    Given I login as administrator
    And go to Marketing/Promotions/Coupons
    And I click "Coupons Actions"
    And I click "Generate Multiple Coupons"
    When I fill form with:
      | Code Prefix | hello        |
      | Code Suffix | kitty        |
      | Code Type   | Alphanumeric |
      | Code Length | 10           |
    Then I expecting to see alphanumeric coupon of 10 symbols with prefix "hello" suffix "kitty" and dashes every 0 symbols
    And I close ui dialog

  Scenario: Preview of alphanumeric code with dashes, default length and without prefix and suffix
    Given go to Marketing/Promotions/Coupons
    And I click "Coupons Actions"
    And I click "Generate Multiple Coupons"
    When I fill form with:
      | Code Type        | Alphanumeric |
      | Add Dashes Every | 2            |
    Then I expecting to see alphanumeric coupon of 12 symbols with prefix "" suffix "" and dashes every 2 symbols
    And I close ui dialog

  Scenario: Preview of alphanumeric code with code code prefix, suffix, dashes and  custom code length
    Given go to Marketing/Promotions/Coupons
    And I click "Coupons Actions"
    And I click "Generate Multiple Coupons"
    When I fill form with:
      | Code Length      | 17           |
      | Code Type        | Alphanumeric |
      | Code Prefix      | hello        |
      | Code Suffix      | kitty        |
      | Add Dashes Every | 4            |
    Then I expecting to see alphanumeric coupon of 17 symbols with prefix "hello" suffix "kitty" and dashes every 4 symbols
    And I close ui dialog
