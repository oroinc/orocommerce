@ticket-BB-15876
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroPromotionBundle:promotions.yml
@fixture-OroPromotionBundle:shopping_list.yml

Feature: Promotions on checkout from quote
  In order to check that promotions can't be used for Quotes
  As a Buyer
  I need to not see enter coupon section and discount subtotal at checkout

  Scenario: Logged in as buyer and manager on different window sessions
    Given sessions active:
      | Admin  | first_session  |
      | Buyer  | second_session |

  Scenario: Prepare quote and check that discounts are not applied on checkout
    Given I proceed as the Admin
    And I login as administrator
    And I disable inventory management
    And I go to Sales / Quotes
    When I click "Create Quote"
    And I fill "Quote Form" with:
      | Customer         | Company A   |
      | Customer User    | Amanda Cole |
      | PO Number        | PO42        |
      | LineItemProduct  | SKU2        |
    And I type "10" in "LineItemPrice"
    And I save and close form
    And agree that shipping cost may have changed
    Then I should see "Quote has been saved" flash message

    When click "Send to Customer"
    And click "Send"
    And I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And click "Account"
    And click "Quotes"
    And click view "PO42" in grid
    And click "Accept and Submit to Order"
    And I click "Submit"
    Then I should not see "Discount"

  Scenario: Check that there is no coupon form on checkout
    Then I should not see "I have a Coupon Code"

  Scenario: Check that discounts does not applied on submitted order
    When I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I click "Continue"
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    When I follow "click here to review"
    And I should be on Order Frontend View page
    Then I should not see "Discount"
