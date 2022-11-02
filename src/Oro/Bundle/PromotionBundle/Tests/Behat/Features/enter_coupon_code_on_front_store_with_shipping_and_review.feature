@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroFlatRateShippingBundle:FlatRate2Integration.yml
@fixture-OroCheckoutBundle:ShippingRuleForFlatRate2.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroPromotionBundle:promotions-with-coupons-on-shopping-list-page.yml

Feature: Enter coupon code on Front Store with shipping and review
  In order to apply discount coupons on Front Store
  As a site user
  I need to have ability to add and manage coupons for shipping promotions on Front Store

  Scenario: Coupon for shipping promotion can be added before Shipping Method step
    Given I login as AmandaRCole@example.org the "Buyer" at "first_session" session
    And I login as administrator and use in "second_session" as "Admin"
    And I disable inventory management
    And I proceed as the Buyer
    And I open shopping list widget
    And I click "View Details"
    When I scroll to "Create Order"
    And I click "Create Order"
    Then I should see "Billing Information" in the "Checkout Step Title" element
    When I scroll to "I have a Coupon Code"
    And I click "I have a Coupon Code"
    When I type "coupon-flat-rate" in "CouponCodeInput"
    And I click "Apply"
    Then I should see "Coupon code has been applied successfully, please review discounts" flash message
    And I should see "coupon-flat-rate Flat rate shipping Promotion" in the "Coupons List" element
    When I type "coupon-flat-rate2" in "CouponCodeInput"
    And I click "Apply"
    Then I should see "Coupon code has been applied successfully, please review discounts" flash message
    And I should see "coupon-flat-rate2 Flat rate2 shipping Promotion" in the "Coupons List" element

  Scenario: Flat Rate2 Promotion is applied when Flat Rate 2 shipping method is chosen
    When on the "Billing Information" checkout step I press Continue
    And on the "Shipping Information" checkout step I press Continue
    And I check "Flat Rate 2" on the "Shipping Method" checkout step and press Continue
    Then I should see "Shipping Discount -$1.00" in the "Subtotals" element

  Scenario: Flat Rate Promotion is applied when Flat Rate shipping method is chosen
    When on the "Payment" checkout step I go back to "Edit Shipping Method"
    And I click "Flat Rate Shipping Method"
    And on the "Shipping Method" checkout step I press Continue
    Then I should see "Shipping Discount -$2.00" in the "Subtotals" element

  Scenario: Created order after passing checkout should have discounts by coupons that was added on checkout page
    When on the "Payment" checkout step I press Continue
    Then I should see "Order Review" in the "Checkout Step Title" element
    When I scroll to "Submit Order"
    And I click "Submit Order"
    And I follow "click here to review"
    And I should see "Shipping Discount -$2.00" in the "Subtotals" element

  Scenario: Check that coupon for Flat Rate2 shipping method does not apply for order
    Given I proceed as the Admin
    When I go to Sales / Orders
    And click "edit" on first row in grid
    And I click "Order Totals"
    Then I see next subtotals for "Backend Order":
      | Subtotal          | Amount |
      | Subtotal          | $20.00 |
      | Shipping          |  $3.00 |
      | Shipping Discount | -$2.00 |
      | Total             | $21.00 |
    When I click "Shipping Information"
    And I click on "Backend Flat Rate2 Shipping Method"
    And I click "Order Totals"
    Then I see next subtotals for "Backend Order":
      | Subtotal          | Amount |
      | Subtotal          | $20.00 |
      | Shipping          |  $2.00 |
      | Total             | $22.00 |
