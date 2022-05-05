@fixture-OroPromotionBundle:promotions_for_coupons.yml
@ticket-BB-11463
@regression
Feature: Generation of long coupon code

  Scenario: Trying to generate coupons exceeding max length
    Given I login as administrator
    And go to Marketing/Promotions/Coupons
    And click "Coupons Actions"
    And I click "Generate Multiple Coupons"
    When I fill "Generate Multiple Coupons Form" with:
      | Promotion        | order Discount Promotion         |
      | Coupon Quantity  | 10                               |
      | Uses per Coupon  | 5                                |
      | Uses per Person  | 5                                |
      | Valid From       | <DateTime:Jul 1, 2017, 12:00 AM> |
      | Valid Until      | <DateTime:Jul 1, 2018, 12:00 AM> |
      | Code Length      | 122                              |
      | Code Type        | Numeric                          |
      | Code Prefix      | prefix                           |
      | Code Suffix      | suffix                           |
      | Add Dashes Every | 1                                |
    And I click "Generate"
    Then I should see "Coupon codes must not be longer than 255 symbols, including prefix, suffix, and dashes. With the current settings, the coupon codes are 256 symbols longer."
    Then I expecting to see numeric coupon of 122 symbols with prefix "prefix" suffix "suffix" and dashes every 1 symbols
    When I close ui dialog
    And I should see "There are no coupon"

  Scenario: Generate coupons with max length
    Given click "Coupons Actions"
    And I click "Generate Multiple Coupons"
    When I fill "Generate Multiple Coupons Form" with:
      | Promotion        | order Discount Promotion         |
      | Coupon Quantity  | 10                               |
      | Uses per Coupon  | 5                                |
      | Uses per Person  | 5                                |
      | Valid From       | <DateTime:Jul 1, 2017, 12:00 AM> |
      | Valid Until      | <DateTime:Jul 1, 2018, 12:00 AM> |
      | Code Length      | 121                              |
      | Code Type        | Numeric                          |
      | Code Prefix      | prefix                           |
      | Code Suffix      | suffix                           |
      | Add Dashes Every | 1                                |
    And I click "Generate"
    Then I should see "Coupons have been generated successfully" flash message
    And I should see "Total Of 10 Records"
