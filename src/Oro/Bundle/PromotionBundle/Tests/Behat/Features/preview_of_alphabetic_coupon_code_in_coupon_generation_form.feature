@regression
Feature: Preview of alphabetic coupon code in coupon generation form
  ToDo: BAP-16103 Add missing descriptions to the Behat features

  Scenario: Preview of alphabetic code with custom length and without dashes
    Given I login as administrator
    And go to Marketing/Promotions/Coupons
    And I click "Coupons Actions"
    And I click "Generate Multiple Coupon"
    When I fill form with:
      | Code Prefix | hello      |
      | Code Suffix | kitty      |
      | Code Type   | Alphabetic |
      | Code Length | 10         |
    Then I expecting to see alphabetic coupon of 10 symbols with prefix "hello" suffix "kitty" and dashes every 0 symbols
    And I close ui dialog

  Scenario: Preview of alphabetic code with dashes, default length and without prefix and suffix
    Given go to Marketing/Promotions/Coupons
    And I click "Coupons Actions"
    And I click "Generate Multiple Coupons"
    When I fill form with:
      | Code Type        | Alphabetic |
      | Add Dashes Every | 2          |
    Then I expecting to see alphabetic coupon of 12 symbols with prefix "" suffix "" and dashes every 2 symbols
    And I close ui dialog

  Scenario: Preview of alphabetic code with code code prefix, suffix, dashes and  custom code length
    Given go to Marketing/Promotions/Coupons
    And I click "Coupons Actions"
    And I click "Generate Multiple Coupons"
    When I fill form with:
      | Code Length      | 17         |
      | Code Type        | Alphabetic |
      | Code Prefix      | hello      |
      | Code Suffix      | kitty      |
      | Add Dashes Every | 4          |
    Then I expecting to see alphabetic coupon of 17 symbols with prefix "hello" suffix "kitty" and dashes every 4 symbols
    And I close ui dialog
