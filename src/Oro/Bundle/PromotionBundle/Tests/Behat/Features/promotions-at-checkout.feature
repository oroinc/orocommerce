@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroPromotionBundle:promotions.yml
@fixture-OroPromotionBundle:shopping_list.yml
Feature: Promotions at Checkout
  In order to find out applied discounts at checkout
  As an site user
  I need to have ability to see applied discounts at checkout stage on front-end

  Scenario: Logged in as buyer and manager on different window sessions
    Given sessions active:
      | Admin  | first_session  |
      | Buyer  | second_session |
    And I switch to the "Admin" session
    And I login as administrator
    And I disable inventory management
    And I switch to the "Buyer" session
    And I signed in as AmandaRCole@example.org on the store frontend

  Scenario: Check line item and order discount at Billing Information Checkout's step
    Given I proceed as the Buyer
    When I open page with shopping list List 1
      And I press "Create Order"
    Then I see next line item discounts for checkout:
      | SKU  | Discount |
      | SKU1 |          |
      | SKU2 | $5.00    |
    And I see next subtotals for "Checkout Step":
      | Subtotal | Amount  |
      | Discount | -$12.50 |
    Then I click "Continue"

  Scenario: Check line item and order discount at Shipping Information Checkout's step
    Given Page title equals to "Shipping Information - Open Order"
    Then I see next line item discounts for checkout:
      | SKU  | Discount |
      | SKU1 |          |
      | SKU2 | $5.00    |
    And I see next subtotals for "Checkout Step":
      | Subtotal | Amount  |
      | Discount | -$12.50 |
    Then I click "Continue"

  Scenario: Check line item and order discount at Shipping Method Checkout's step
    Given Page title equals to "Shipping Method - Open Order"
    Then I see next line item discounts for checkout:
      | SKU  | Discount |
      | SKU1 |          |
      | SKU2 | $5.00    |
    And I see next subtotals for "Checkout Step":
      | Subtotal          | Amount  |
      | Discount          | -$12.50 |
      | Shipping Discount | -$1.00  |
    Then I click "Continue"

  Scenario: Check line item and order discount at Payment Checkout's step
    Given Page title equals to "Payment - Open Order"
    Then I see next line item discounts for checkout:
      | SKU  | Discount |
      | SKU1 |          |
      | SKU2 | $5.00    |
    And I see next subtotals for "Checkout Step":
      | Subtotal          | Amount  |
      | Discount          | -$12.50 |
      | Shipping Discount | -$1.00  |
    Then I click "Continue"

  Scenario: Check line item and order discount at Order Review Checkout's step
    Given Page title equals to "Order Review - Open Order"
    Then I see next line item discounts for checkout:
      | SKU  | Discount |
      | SKU1 |          |
      | SKU2 | $5.00    |
    And I see next subtotals for "Checkout Step":
      | Subtotal          | Amount  |
      | Discount          | -$12.50 |
      | Shipping Discount | -$1.00  |
    Then I click "Submit Order"
      And I follow "click here to review"

  Scenario: Check line item and order discount at Order View page
    Given I should be on Order Frontend View page
      And I show column "Row Total (Discount Amount)" in "Order Line Items Grid" frontend grid
    Then I see next line item discounts for order:
      | SKU  | Row Total (Discount Amount) |
      | SKU1 | $0.00                       |
      | SKU2 | $5.00                       |
    And I see next subtotals for "Order":
      | Subtotal          | Amount  |
      | Discount          | -$12.50 |
      | Shipping Discount | -$1.00  |
