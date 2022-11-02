@regression
@ticket-BB-16591
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroFlatRateShippingBundle:FlatRate2Integration.yml
@fixture-OroCheckoutBundle:ShippingRuleForFlatRate2.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroPromotionBundle:promotions-with-coupons-on-shopping-list-page.yml
Feature: Enter coupon code on front store during single page checkout
  In order to apply discount coupons on Front Store
  As a site user
  I need to have ability to add and manage coupons on Front Store

  Scenario: Entered coupon should give discount on checkout page
    Given I login as AmandaRCole@example.org the "Buyer" at "first_session" session
    And I login as administrator and use in "second_session" as "Admin"
    And I activate "Single Page Checkout" workflow
    And I disable inventory management
    And I proceed as the Buyer
    And I open shopping list widget
    And I click "View Details"
    When I scroll to "Create Order"
    And I click "Create Order"
    Then I should see "Checkout"
    When I scroll to "I have a Coupon Code"
    And I click "I have a Coupon Code"
    And I type "coupon-1" in "CouponCodeInput"
    And I click "Apply"
    Then I should see "Coupon code has been applied successfully, please review discounts" flash message
    And I should see "coupon-1 First Promotion Label" in the "Coupons List" element
    And I should see "Discount -$1.00" in the "Subtotals" element

  Scenario: Entered invalid coupon should not pass validation
    When I type "coupon-1" in "CouponCodeInput"
    And I click "Apply"
    Then I should see "This coupon has been already added"
    When I type "not-existing-coupon" in "CouponCodeInput"
    And I click "Apply"
    Then I should see "Invalid coupon code, please try another"
    And I should not see "not-existing-coupon" in the "Coupons List" element

  Scenario: Removed coupon should not give discount
    When I click "Coupon Delete Button"
    Then I should see "Coupon code has been removed" flash message
    And I should not see "coupon-1 First Promotion Label"
    And I should see "I have a Coupon Code"
    And I should not see "Discount -$1.00" in the "Subtotals" element

  Scenario: Coupon promotion label should have fallback as promotion name
    When I scroll to "I have a Coupon Code"
    And I click "I have a Coupon Code"
    And I type "coupon-2" in "Coupon Code Input"
    And I click "Apply"
    Then I should see "coupon-2 Second Promotion Name" in the "Coupons List" element

  Scenario: Created order after passing checkout should have discounts by coupons that was added on checkout page
    Given I click "Submit Order"
    And I follow "click here to review"
    Then I should see "Discount -$1.00" in the "Subtotals" element
