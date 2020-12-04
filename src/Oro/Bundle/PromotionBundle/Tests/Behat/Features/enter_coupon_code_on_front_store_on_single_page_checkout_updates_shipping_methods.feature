@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroFlatRateShippingBundle:FlatRate2Integration.yml
@fixture-OroCheckoutBundle:ShippingRuleForFlatRate2.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroPromotionBundle:promotions-with-coupons-on-shopping-list-page.yml
@fixture-OroPromotionBundle:promotions-with-coupon-for-line-items.yml
Feature: Enter coupon code on front store on single page checkout updates shipping methods
  In order to apply discount coupons on Front Store
  As a site user
  I need to have ability to add and manage coupons on Front Store

  Scenario: Entered coupon should update shipping methods cost
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
    And I should see "SELECT A SHIPPING METHOD"
    And I should see "Flat Rate: $3.00"
    When I scroll to "I have a Coupon Code"
    And I click "I have a Coupon Code"
    And I type "coupon-flat-rate" in "CouponCodeInput"
    And I click "Apply"
    Then I should see "Coupon code has been applied successfully, please review discounts"
    And I scroll to top
    And I should see "SELECT A SHIPPING METHOD"
    And I should see "Flat Rate: $1.00"
    And I check "Flat Rate: $1.00" on the checkout page
    When I scroll to "Subtotals"
    Then I should see "Shipping Discount -$2.00" in the "Subtotals" element

  Scenario: Entered shipping coupon code should update line items subtotals data:
    When I scroll to top
    Then Checkout "Order Summary Products Grid" should contain products:
      | Product 2 | 5 | items | $2.00 | $10.00 |
    When I scroll to "CouponCodeInput"
    And I type "line-item-coupon" in "CouponCodeInput"
    And I click "Apply"
    Then I should see "Coupon code has been applied successfully, please review discounts" flash message
    And I should see "line-item-coupon Line Item Discount Promotion" in the "Coupons List" element
    And I scroll to top
    And I should see "Order Summary"
    Then Checkout "Order Summary Products Grid" should contain products:
      | Product 2 | 5 | items | $2.00 | $10.00 -$1.00 |
    When I scroll to "Subtotals"
    Then I should see "Discount -$1.00" in the "Subtotals" element

  Scenario: Order created after passing checkout
    Given I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    And I follow "click here to review"
    Then I should see "Discount -$1.00" in the "Subtotals" element
    And I should see "Shipping Discount -$2.00" in the "Subtotals" element
