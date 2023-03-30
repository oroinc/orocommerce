@ticket-BB-16307
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroPayPalBundle:PayPalExpressProduct.yml
@fixture-OroPromotionBundle:100-percent-promotions-with-coupons.yml
@behat-test-env
Feature: Payflow Gateway Express payments should not be available for zero total amount
  In order to purchase goods using PayPal
  As a buyer
  I should be able to use Payflow Gateway Express if order total is greater than zero

  Scenario: Feature Background
    Given There are products in the system available for order
    And I create PayPal PaymentsPro integration with following settings:
      | creditCardPaymentAction | charge |
    And I create payment rule with "ExpressPayPal" payment method

  Scenario: Start checkout and choose ExpressPayPal payment method
    Given I login as AmandaRCole@example.org the "Buyer" at "first_session" session
    And I am on the homepage
    And I open page with shopping list List 1
    And I click "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "ExpressPayPal" on the "Payment" checkout step and press Continue

  Scenario: Add Coupons for 100% discount
    Given I scroll to "I have a Coupon Code"
    When I click "I have a Coupon Code"
    And I type "coupon-100-order" in "Coupon Code Input"
    And I click "Apply"
    And I type "coupon-100-shipping" in "Coupon Code Input"
    And I click "Apply"
    Then I should see "The selected payment method is not available. Please return to the payment method selection step and select a different one." flash message
    And I should see "coupon-100-order Promotion Order 100 Label" in the "Coupons List" element
    And I should see "coupon-100-shipping Promotion Shipping 100 Label" in the "Coupons List" element
    And I should see "Total $0.00"

  Scenario: Ensure payment method is not available anymore
    Given on the "Order Review" checkout step I go back to "Edit Payment"
    Then I should see "No payment methods are available, please contact us to complete the order submission."
    And I should not see "ExpressPayPal"
