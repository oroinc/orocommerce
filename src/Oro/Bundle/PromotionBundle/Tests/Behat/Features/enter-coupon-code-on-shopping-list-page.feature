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
    When I click "I have a Coupon Code"
    And I type "coupon-1" in "CouponCodeInput"
    And I press "Apply"
    Then I should see "Coupon code has been applied successfully, please review discounts" flash message
    And I should see "coupon-1 Shopping list Promotion" in the "Coupons List" element
    And I see next subtotals for "Shopping List":
      | Subtotal | Amount |
      | Discount | -$1.00 |

  Scenario: Entered invalid coupon should not pass validation
    When I type "coupon-1" in "CouponCodeInput"
    And I press "Apply"
    Then I should see "This coupon has been already added"
    When I type "not-existing-coupon" in "CouponCodeInput"
    And I press "Apply"
    Then I should see "Invalid coupon code, please try another one"
    And I should not see "not-existing-coupon" in the "Coupons List" element

  Scenario: Removed coupon should not give discount
    When I click "Coupon Delete Button"
    Then I should not see "coupon-1 Shopping list Promotion"
    And I should see "I have a Coupon Code"
    And I should not see "Discount -$1.00" in the "Subtotals" element
