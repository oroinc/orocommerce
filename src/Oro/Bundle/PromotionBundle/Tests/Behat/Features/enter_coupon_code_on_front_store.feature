@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroFlatRateShippingBundle:FlatRate2Integration.yml
@fixture-OroCheckoutBundle:ShippingRuleForFlatRate2.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroPromotionBundle:promotions-with-coupons-on-shopping-list-page.yml
@skip
Feature: Enter coupon code on Front Store
  In order to apply discount coupons on Front Store
  As a site user
  I need to have ability to add and manage coupons on Front Store

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    And I login as administrator
    And I disable inventory management

  Scenario: Entered coupon should give discount on checkout page
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I open shopping list widget
    And I click "View Details"
    When I scroll to "Create Order"
    And I click "Create Order"
    Then I should see "Checkout"
    When I scroll to "I have a Coupon Code"
    And I click "I have a Coupon Code"
    When I type "CoupoN-1" in "CouponCodeInput"
    And I click "Apply"
    Then I should see "Invalid coupon code, please try another"
    When I type "coupon-1" in "CouponCodeInput"
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
    Given I should see "Discount -$1.00" in the "Subtotals" element
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate 2" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I should see "Order Review" in the "Checkout Step Title" element
    And I scroll to "Submit Order"
    When I click "Submit Order"
    And I follow "click here to review"
    Then I should see "Discount -$1.00" in the "Subtotals" element
