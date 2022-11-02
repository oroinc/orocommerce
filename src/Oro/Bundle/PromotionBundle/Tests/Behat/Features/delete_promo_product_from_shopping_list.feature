@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroPromotionBundle:line_items_promotions.yml
@fixture-OroPromotionBundle:shopping_list.yml
Feature: Delete promo product from shopping list
  As a Customer User
  I need to have ability to delete promo product from shopping list

  Scenario: Check subtotal recalculation after delete promo product
    Given I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    Then I see next line item discounts for shopping list "List 1":
      | SKU              | Discount |
      | SKU1             | -$3.00    |
      | SKU2             | -$1.00    |
    And I see next subtotals for "Shopping List":
      | Subtotal | Amount |
      | Discount | -$4.00 |
    Then I click delete line item "SKU1"
    And I click "Yes, Delete" in modal window
    And I see next line item discounts for shopping list "List 1":
      | SKU              | Discount |
      | SKU2             | -$5.50    |
    And I see next subtotals for "Shopping List":
      | Subtotal | Amount |
      | Discount | -$5.50 |
