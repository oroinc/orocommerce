@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroPromotionBundle:promotion_with_payment_term_expression.yml
@fixture-OroPromotionBundle:shopping_list.yml
Feature: Promotions with payment method expression at Checkout
  In order to find out applied discounts at checkout
  As an site user
  I need to have ability to see applied discounts at checkout stage on front-end

  Scenario: Check line item and order discount at Billing Information Checkout's step
    Given I login as administrator
    And I disable inventory management
    Then I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I click "Create Order"
    Then Page title equals to "Billing Information - Checkout"
    When I click "Continue"
    Then Page title equals to "Shipping Information - Checkout"
    When I click "Continue"
    Then Page title equals to "Shipping Method - Checkout"
    When I click "Continue"
    Then Page title equals to "Payment - Checkout"
    When I click "Continue"
    Then Page title equals to "Order Review - Checkout"
    And I see next subtotals for "Checkout Step":
      | Subtotal          | Amount  |
      | Discount          | -$10.00 |
    Then I click "Submit Order"
    And Email should contains the following:
      | Body | Discount -$10.00         |
    When I follow "click here to review"
    Then I should be on Order Frontend View page
    And I see next subtotals for "Order":
      | Subtotal          | Amount  |
      | Discount          | -$10.00 |
