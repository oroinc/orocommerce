@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroFlatRateShippingBundle:FlatRate2Integration.yml
@fixture-OroCheckoutBundle:ShippingRuleFreeShipping.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroPromotionBundle:promotions-with-coupons-on-shopping-list-page.yml
@behat-test-env
Feature: Enter coupon code on front store on single page checkout
  In order to apply discount coupons on Front Store
  As a site user
  I need to have ability to add and manage coupons on Front Store

  Scenario: Create different window session
    Given sessions active:
      | Admin  |first_session |
      | User   |second_session|

  Scenario: Create PayPal integration
    Given I proceed as the Admin
    And I login as administrator
    And I activate "Single Page Checkout" workflow
    And I disable inventory management
    And I create PayPal Payflow integration
    And I create payment rule with "PayPalFlow" payment method

  Scenario: Payment card data should not be removed after coupon applied.
    Given I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    And I open shopping list widget
    And I click "View Details"
    When I scroll to "Create Order"
    And I click "Create Order"
    Then I should see "Checkout"
    And I check "PayPalFlow" on the checkout page
    And I fill "PayPal Credit Card Form" with:
      | CreditCardNumber | 5424000000000015 |
      | Month            | 11               |
      | Year             | 2027             |
      | CVV              | 123              |
    When I scroll to "I have a Coupon Code"
    And I click "I have a Coupon Code"
    And I type "coupon-1" in "CouponCodeInput"
    And I click "Apply"
    Then I should see "Coupon code has been applied successfully, please review discounts" flash message
    And I should see "coupon-1 First Promotion Label" in the "Coupons List" element
    And I should see "Discount -$1.00" in the "Subtotals" element
    And "PayPal Credit Card Form" must contains values:
      | Credit Card Number | 5424000000000015 |
      | Expiration Date    | 11               |

  Scenario: Payment card data should be visible after coupon removed.
    When I click "Coupon Delete Button"
    Then "PayPal Credit Card Form" must contains values:
      | Credit Card Number | 5424000000000015 |
      | Expiration Date    | 11               |

  Scenario: Entered 100 percent coupon should applied without errors
    When I scroll to "I have a Coupon Code"
    And I click "I have a Coupon Code"
    When I type "coupon-3" in "CouponCodeInput"
    And I click "Apply"
    Then I should see "Coupon code has been applied successfully, please review discounts" flash message
    And I should not see "PayPalFlow"
    When I click "Submit Order"
    Then I should see "Please select payment method."
    When I click "Coupon Delete Button"
    Then I should see "Coupon code has been removed" flash message
    And I should not see "coupon-3 Third Promotion Name"
    And I should see "PayPalFlow"
    And I should not see "Please select payment method."

  Scenario: Payment card data should be visible when coupon applied after Paypal payment method available again.
    When I scroll to top
    And I check "PayPalFlow" on the checkout page
    Then I fill "PayPal Credit Card Form" with:
      | CreditCardNumber | 5424000000000015 |
      | Month            | 11               |
      | Year             | 2027             |
      | CVV              | 123              |
    When I scroll to "I have a Coupon Code"
    And I click "I have a Coupon Code"
    And I type "coupon-1" in "CouponCodeInput"
    And I click "Apply"
    Then I should see "Coupon code has been applied successfully, please review discounts" flash message
    And I should see "coupon-1 First Promotion Label" in the "Coupons List" element
    And I should see "Discount -$1.00" in the "Subtotals" element
    And "PayPal Credit Card Form" must contains values:
      | Credit Card Number | 5424000000000015 |
      | Expiration Date    | 11               |

  Scenario: Payment card data should be visible when coupon removed and after Paypal payment method available again.
    When I click "Coupon Delete Button"
    Then "PayPal Credit Card Form" must contains values:
      | Credit Card Number | 5424000000000015 |
      | Expiration Date    | 11               |

  Scenario: Order created without validation errors.
    Given I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
