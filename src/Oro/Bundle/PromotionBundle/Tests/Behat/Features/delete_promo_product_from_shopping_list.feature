@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-line_items_promotions.yml
@fixture-shopping_list.yml
Feature: Several promotions in Shopping List
  In order to find out applied discounts in shopping list
  As a site user
  I need to have ability to see applied discounts at shopping list on front-end

  Scenario: Check line item and subtotal discount in Shopping List with simple products
    Given I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    Then I see next line item discounts for shopping list "List 1":
      | SKU              | Discount |
      | SKU2             | $1.00    |
      | SKU1             | $3.00    |
    And I see "$4.00" subtotal discount for shopping list "List 1"
    Then I click delete line item "SKU1"
    And I click "Yes, Delete" in modal window
    And I see next line item discounts for shopping list "List 1":
      | SKU              | Discount |
      | SKU2             | $5.50    |
    And I see "$5.50" subtotal discount for shopping list "List 1"
