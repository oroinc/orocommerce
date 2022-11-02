@regression
@ticket-BB-14565
@ticket-BB-15378
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroFlatRateShippingBundle:FlatRate2Integration.yml
@fixture-OroCheckoutBundle:ShippingRuleForFlatRate2.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroPromotionBundle:promotions-with-coupons-that-uses-segment-on-shopping-list-page.yml
Feature: Enter coupon code on Front Store for promotion that uses segment in query
  In order to apply discount coupons on Front Store for promotion with segment in query
  As a site user
  I need to have ability to add coupons on Front Store

  Scenario: Entered coupon should applied without errors
    Given I login as AmandaRCole@example.org the "Buyer" at "second_session" session
    And I login as administrator and use in "first_session" as "Admin"
    And I go to Marketing / Promotions / Promotions
    And I click "edit" on first row in grid
    And I click on "Advanced Filter"
    When I drag and drop "Apply segment" on "Drop condition here"
    And I type "Featured Products" in "Choose segment"
    Then I should see "Featured Products" in the "Select2 results" element
    When I click on "Featured Products"
    And I save form
    And I click "Continue" in confirmation dialogue
    Then I should see "Promotion has been saved" flash message
    When I proceed as the Buyer
    And I open page with shopping list List 1
    And I scroll to "Create Order"
    And I click "Create Order"
    Then I should see "Checkout"
    When I scroll to "I have a Coupon Code"
    And I click "I have a Coupon Code"
    And I type "mycoupon" in "CouponCodeInput"
    And I click "Apply"
    Then I should see "Coupon code has been applied successfully, please review discounts" flash message
    And I should see "mycoupon Promotion that uses segment in query" in the "Coupons List" element
    And I should see "Discount -$1.00" in the "Subtotals" element
