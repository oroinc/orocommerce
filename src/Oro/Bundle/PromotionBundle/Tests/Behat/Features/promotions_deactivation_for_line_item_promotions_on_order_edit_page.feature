@regression
@ticket-BB-16110
@ticket-BB-16121
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroPromotionBundle:line_items_promotions.yml
@fixture-OroPromotionBundle:shopping_list.yml
Feature: Promotions deactivation for line item promotions on order edit page
  In order to calculate discounts for created order
  As administrator
  I need to have ability to deactivate and activate applied promotions

  Scenario: Finish checkout with more than one line item promotion and check that subtotals calculated right
    Given I login as AmandaRCole@example.org the "Buyer" at "first_session" session
    And I login as administrator and use in "second_session" as "Admin"
    And I disable inventory management
    And I proceed as the Buyer
    And I do the order through completion, and should be on order view page
    Then I see next subtotals for "Order":
      | Subtotal          | Amount |
      | Subtotal          | $20.00 |
      | Discount          | -$4.00 |
      | Shipping          | $3.00  |
      | Shipping Discount | $0.00  |
      | Tax               | $0.00  |
      | Total             | $19.00 |

    When I operate as the Admin
    And I go to Sales / Orders
    And I click "edit" on first row in grid
    Then I should see next rows in "Promotions" table
      | Promotion                                   | Type            | Status | Discount |
      | lineItemDiscountPromotionStopProcessingRule | Order Line Item | Active | -$3.00   |
      | lineItemDiscountPromotionRule1              | Order Line Item | Active | -$1.00   |
    And I see next subtotals for "Backend Order":
      | Subtotal          | Amount |
      | Subtotal          | $20.00 |
      | Discount          | -$4.00 |
      | Shipping          | $3.00  |
      | Total             | $19.00 |

  Scenario: Deactivate and activate first promotion and check that subtotals does not changed
    When I click "Deactivate" on row "lineItemDiscountPromotionStopProcessingRule" in "Promotions"
    Then I should see next rows in "Promotions" table
      | Promotion                                   | Type            | Status   | Discount |
      | lineItemDiscountPromotionStopProcessingRule | Order Line Item | Inactive | $0.00    |
      | lineItemDiscountPromotionRule1              | Order Line Item | Active   | -$1.00   |
    And I see next subtotals for "Backend Order":
      | Subtotal          | Amount |
      | Subtotal          | $20.00 |
      | Discount          | -$1.00 |
      | Shipping          | $3.00  |
      | Total             | $22.00 |
    When I click "Activate" on row "lineItemDiscountPromotionStopProcessingRule" in "Promotions"
    Then I should see next rows in "Promotions" table
      | Promotion                                   | Type            | Status | Discount |
      | lineItemDiscountPromotionStopProcessingRule | Order Line Item | Active | -$3.00   |
      | lineItemDiscountPromotionRule1              | Order Line Item | Active | -$1.00   |
    And I see next subtotals for "Backend Order":
      | Subtotal          | Amount |
      | Subtotal          | $20.00 |
      | Discount          | -$4.00 |
      | Shipping          | $3.00  |
      | Total             | $19.00 |
