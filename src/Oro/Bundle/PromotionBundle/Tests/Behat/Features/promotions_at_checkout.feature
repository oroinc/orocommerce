@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-promotions.yml
@fixture-shopping_list.yml
Feature: Promotions at Checkout
  In order to find out applied discounts at checkout
  As an site user
  I need to have ability to see applied discounts at checkout stage on front-end

  Scenario: Prepare environment - disable inventory management
    Given I login as administrator
      And I go to System/Configuration
    When I click "Commerce" on configuration sidebar
      And I click "Inventory" on configuration sidebar
      And I click "Product Options" on configuration sidebar
      And I fill "Product Inventory Options Form" with:
        | Use Default         | false |
        | Decrement Inventory | false |
      And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Check line item and order discount at Billing Information Checkout's step
    Given I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
      And I press "Create Order"
    Then I see next line item discounts for checkout:
      | SKU  | Discount |
      | SKU2 | $5.00    |
      | SKU1 |          |
      And I see "$12.50" subtotal discount for checkout step
    Then I click "Continue"

  Scenario: Check line item and order discount at Shipping Information Checkout's step
    Given Page title equals to "Shipping Information - Open Order"
    Then I see next line item discounts for checkout:
      | SKU  | Discount |
      | SKU2 | $5.00    |
      | SKU1 |          |
      And I see "$12.50" subtotal discount for checkout step
    Then I click "Continue"

  Scenario: Check line item and order discount at Shipping Method Checkout's step
    Given Page title equals to "Shipping Method - Open Order"
    Then I see next line item discounts for checkout:
      | SKU  | Discount |
      | SKU2 | $5.00    |
      | SKU1 |          |
      And I see "$12.50" subtotal discount for checkout step
    Then I click "Continue"

  Scenario: Check line item and order discount at Payment Checkout's step
    Given Page title equals to "Payment - Open Order"
    Then I see next line item discounts for checkout:
      | SKU  | Discount |
      | SKU2 | $5.00    |
      | SKU1 |          |
      And I see "$12.50" subtotal discount for checkout step
    Then I click "Continue"

  Scenario: Check line item and order discount at Order Review Checkout's step
    Given Page title equals to "Order Review - Open Order"
    Then I see next line item discounts for checkout:
      | SKU  | Discount |
      | SKU2 | $5.00    |
      | SKU1 |          |
      And I see "$12.50" subtotal discount for checkout step
    Then I click "Submit Order"
      And I follow "click here to review"

  Scenario: Check line item and order discount at Order View page
    Given I should be on Order Frontend View page
# TODO uncomment when BB-10288 will be merged
#      And I show column "Row Total (Discount Amount)" in "Order Line Items Grid" frontend grid
#    Then I see next line item discounts for order:
#      | SKU  | Row Total (Discount Amount) |
#      | SKU2 | $5.00                       |
#      | SKU1 | $0.00                       |
    And I see "$12.50" subtotal discount for order
