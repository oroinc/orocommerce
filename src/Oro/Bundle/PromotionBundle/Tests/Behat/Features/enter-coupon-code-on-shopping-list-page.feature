@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroPromotionBundle:promotions-with-coupons-on-shopping-list-page.yml
Feature: Enter coupon code on shopping list page
  In order to apply discount coupons on shopping list
  As a site user
  I need to have ability to add and manage coupons for discount on shopping list page

  Scenario: Entered coupon should give discount on shopping list page
    Given I login as AmandaRCole@example.org buyer
    And I open shopping list widget
    And I click "View Details"
    And I scroll to "I have a Coupon Code"
    Then I click "I have a Coupon Code"
    And I type "coupon-1" in "CouponCodeInput"
    And I press "Apply"
    # TODO fix in BB-12230, when already refresh will be possible to see message not briefly
    # And I should see "Coupon code has been applied successfully, please review discounts" flash message
    And I see next subtotals for "Shopping List":
      | Subtotal | Amount |
      | Discount | -$1.00 |
