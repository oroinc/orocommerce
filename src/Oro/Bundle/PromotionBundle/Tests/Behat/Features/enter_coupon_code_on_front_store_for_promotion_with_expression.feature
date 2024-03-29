@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroFlatRateShippingBundle:FlatRate2Integration.yml
@fixture-OroCheckoutBundle:ShippingRuleForFlatRate2.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroPromotionBundle:promotions-with-coupons-on-shopping-list-page.yml

Feature: Enter coupon code on Front Store for promotion with expression
  In order to apply discount coupons on Front Store
  As a site user
  I need to have ability to add and manage coupons on Front Store

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    And I login as administrator
#    And I disable inventory management

  Scenario: Coupon promotion label should have fallback as promotion name
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I open shopping list widget
    And I click "View Details"
    When I scroll to "Create Order"
    And I click "Create Order"
    Then I should see "Checkout"
    When I scroll to "I have a Coupon Code"
    And I click "I have a Coupon Code"
    And I type "coupon-2" in "Coupon Code Input"
    And I click "Apply"
    Then I should see "coupon-2 Second Promotion Name" in the "Coupons List" element

  Scenario: Add expression to the second promotion
    Given I proceed as the Admin
    And I go to Marketing/Promotions/Promotions
    And click edit Second Promotion Name in grid
    And I click "Show"
    And fill "Promotion Form" with:
      | Website    | Default       |
      | Expression | subtotal > 30 |
    And save and close form
    Then I should see "Promotion has been saved" flash message

  Scenario: Coupon promotion label should not be visible
    Given I proceed as the Buyer
    And I open shopping list widget
    And I click "View Details"
    When I scroll to "Create Order"
    And I click "Create Order"
    Then I should not see "coupon-2 Second Promotion Name"

  Scenario: Created order after passing checkout should have discounts by coupons that was added on checkout page
    Given I should not see "Discount -$1.00" in the "Subtotals" element
    And I select "ORO, Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "ORO, Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate 2" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I should see "Order Review" in the "Checkout Step Title" element
    When I click "Submit Order"
    And I follow "click here to review"
    Then I should not see "Discount -$1.00" in the "Subtotals" element
