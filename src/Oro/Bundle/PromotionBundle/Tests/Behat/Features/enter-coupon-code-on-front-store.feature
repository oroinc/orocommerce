@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroPromotionBundle:promotions-with-coupons-on-shopping-list-page.yml
Feature: Enter coupon code on Front Store
  In order to apply discount coupons on Front Store
  As a site user
  I need to have ability to add and manage coupons for discount on shopping list and checkout

  Scenario: Entered coupon should give discount on shopping list page
    Given I login as AmandaRCole@example.org the "Buyer" at "first_session" session
    And I login as administrator and use in "second_session" as "Admin"
    And I disable inventory management
    And I proceed as the Buyer
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
    Then I should see "Coupon code has been removed" flash message
    And I should not see "coupon-1 Shopping list Promotion"
    And I should see "I have a Coupon Code"
    And I should not see "Discount -$1.00" in the "Subtotals" element

  Scenario: Coupons added to shopping list must be applied to checkout
    Given I scroll to "I have a Coupon Code"
    When I click "I have a Coupon Code"
    And I type "coupon-1" in "CouponCodeInput"
    And I press "Apply"
    Then I should see "Coupon code has been applied successfully, please review discounts" flash message
    When I scroll to "Create Order"
    And I click "Create Order"
    Then I should see "Checkout"
    Then I should see "coupon-1 Shopping list Promotion" in the "Coupons List" element
    And I should see "Discount -$1.00" in the "Subtotals" element

  Scenario: Coupons removed from checkout must be removed from shopping list
    Given I click "Coupon Delete Button"
    Then I should see "Coupon code has been removed" flash message
    And I should see "Checkout"
    When I click on "Checkout Edit Order Link"
    Then I should see "List 1"
    And I should not see "coupon-1 Shopping list Promotion"
    And I should not see "Discount -$1.00" in the "Subtotals" element

  Scenario: Coupons added to checkout must be added to shopping list
    When I scroll to "Create Order"
    And I click "Create Order"
    Then I should see "Checkout"
    When I scroll to "I have a Coupon Code"
    When I click "I have a Coupon Code"
    And I type "coupon-1" in "CouponCodeInput"
    And I press "Apply"
    Then I should see "Coupon code has been applied successfully, please review discounts" flash message
    And I should see "Checkout"
    When I click on "Checkout Edit Order Link"
    Then I should see "List 1"
    And I should see "coupon-1 Shopping list Promotion"
    And I should see "Discount -$1.00" in the "Subtotals" element

  Scenario: Coupons removed from shopping list must be removed from checkout
    Given I click "Coupon Delete Button"
    Then I should see "Coupon code has been removed" flash message
    And I should see "List 1"
    When I scroll to "Create Order"
    And I click "Create Order"
    Then I should see "Checkout"
    And I should not see "coupon-1 Shopping list Promotion"
    And I should not see "Discount -$1.00" in the "Subtotals" element

  Scenario: Created order after passing checkout should have discounts by coupons that was added on checkout page
    Given I should see "Billing Information" in the "Checkout Step Title" element
    When I click "I have a Coupon Code"
    And I type "coupon-1" in "CouponCodeInput"
    And I press "Apply"
    Then I see next subtotals for "Checkout Step":
      | Subtotal | Amount  |
      | Discount | -$1.00 |
    When I click "Continue"
    And I should see "Shipping Information" in the "Checkout Step Title" element
    And I click "Continue"
    And I should see "Shipping Method" in the "Checkout Step Title" element
    And I click "Continue"
    And I should see "Payment" in the "Checkout Step Title" element
    And I click "Continue"
    And I should see "Order Review" in the "Checkout Step Title" element
    And I click "Submit Order"
    And I follow "click here to review"
    Then I see next subtotals for "Order":
      | Subtotal          | Amount |
      | Discount          | -$1.00 |
