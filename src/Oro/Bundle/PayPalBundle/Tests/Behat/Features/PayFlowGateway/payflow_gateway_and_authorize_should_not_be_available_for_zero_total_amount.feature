@behat-test-env
@ticket-BB-16307
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroPaymentBundle:ProductsAndShoppingListsForPayments.yml
@fixture-OroPromotionBundle:100-percent-promotions-with-coupons.yml
Feature: Payflow Gateway and Authorize should not be available for zero total amount
  In order to purchase goods using PayPal
  As a buyer
  I should be able to use Payflow Gateway only if order total is greater than zero

  Scenario: Create new PayPal PayFlow Gateway Integration
    Given I login as AmandaRCole@example.org the "Buyer" at "first_session" session
    And I login as administrator and use in "second_session" as "Admin"
    When I go to System/Integrations/Manage Integrations
    And I click "Create Integration"
    And I select "PayPal Payflow Gateway" from "Type"
    And I fill PayPal integration fields with next data:
      | Name                         | PayPalFlow           |
      | Label                        | PayPalFlow           |
      | Short Label                  | PPlPro               |
      | Allowed Credit Card Types    | Mastercard           |
      | Partner                      | PayPal               |
      | Vendor                       | qwerty123456         |
      | User                         | qwer12345            |
      | Password                     | qwer123423r23r       |
      | Zero Amount Authorization    | true                 |
      | Payment Action               | Authorize and Charge |
      | Express Checkout Name        | ExpressPayPal        |
      | Express Checkout Label       | ExpressPayPal        |
      | Express Checkout Short Label | ExprPPl              |
    And I save and close form
    Then I should see "Integration saved" flash message
    And I should see PayPalFlow in grid

  Scenario: Create new Payment Rule for PayPal PayFlow Gateway integration
    Given I go to System/Payment Rules
    When I click "Create Payment Rule"
    And I check "Enabled"
    And I fill in "Name" with "PayPalFlow"
    And I fill in "Sort Order" with "1"
    And I select "PayPalFlow" from "Method"
    And I click "Add Method Button"
    And I save and close form
    Then I should see "Payment rule has been saved" flash message

  Scenario: Start checkout and choose PayPal Gateway payment method
    Given There are products in the system available for order
    And I operate as the Buyer
    When I open page with shopping list List 1
    And I click "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I fill credit card form with next data:
      | CreditCardNumber | 5424000000000015 |
      | Month            | 11               |
      | Year             | 2027             |
      | CVV              | 123              |
    And I click "Continue"

  Scenario: Add Coupons for 100% discount
    Given I scroll to "I have a Coupon Code"
    When I click "I have a Coupon Code"
    And I type "coupon-100-order" in "Coupon Code Input"
    And I click "Apply"
    And I type "coupon-100-shipping" in "Coupon Code Input"
    And I click "Apply"
    Then I should see "The selected payment method is not available. Please return to the payment method selection step and select a different one."
    And I should see "coupon-100-order Promotion Order 100 Label" in the "Coupons List" element
    And I should see "coupon-100-shipping Promotion Shipping 100 Label" in the "Coupons List" element
    And I should see "Total $0.00"

  Scenario: Ensure payment method is not available anymore
    Given on the "Order Review" checkout step I go back to "Edit Payment"
    Then I should see "No payment methods are available, please contact us to complete the order submission."
