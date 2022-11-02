@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroFlatRateShippingBundle:FlatRate2Integration.yml
@fixture-OroCheckoutBundle:ShippingRuleForFlatRate2.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroPromotionBundle:promotions-with-coupons-on-shopping-list-page.yml

Feature: Enter coupon code case insensitive on Front Store
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
    When follow "Commerce/Sales/Promotions" on configuration sidebar
    And uncheck "Use default" for "Case-Insensitive Coupon Codes" field
    And I check "Case-Insensitive Coupon Codes"
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

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
    And I type "CoUpoN-1" in "CouponCodeInput"
    And I click "Apply"
    Then I should see "Coupon code has been applied successfully, please review discounts" flash message
    And I should see "coupon-1 First Promotion Label" in the "Coupons List" element
    And I should see "Discount -$1.00" in the "Subtotals" element

  Scenario: Entered invalid coupon should not pass validation
    When I type "cOUpon-1" in "CouponCodeInput"
    And I click "Apply"
    Then I should see "This coupon has been already added"

  Scenario: Created order after passing checkout should have discounts by coupons that was added on checkout page
    When I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate 2" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I should see "Order Review" in the "Checkout Step Title" element
    And I scroll to "Submit Order"
    When I click "Submit Order"
    And I follow "click here to review"
    Then I should see "Discount -$1.00" in the "Subtotals" element
