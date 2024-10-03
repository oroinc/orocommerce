@regression
@ticket-BB-22535
@fixture-OroShoppingListBundle:shopping_list_line_items_prices.yml

Feature: Shopping List Line Items Prices

  Scenario: Feature Background
    Given sessions active:
      | Amanda | first_session  |
      | Nancy  | second_session |

  Scenario: Check that line item price is specific for Amanda
    Given I proceed as the Amanda
    And I signed in as AmandaRCole@example.org on the store frontend
    And I hover on "Shopping Cart"
    When I click "Shopping List Amanda" on shopping list widget
    Then I should see following grid containing rows:
      | SKU   | Product   | Availability | Qty Update All | Price   | Subtotal |
      | PSKU1 | Product 1 | IN STOCK     | 2 item         | $1.2345 | $2.47    |
    And I should see "Subtotal $2.47" in the "Subtotals" element

  Scenario: Check that line item price is default for Nancy
    Given I proceed as the Nancy
    And I signed in as NancyJSallee@example.org on the store frontend
    And I hover on "Shopping Cart"
    When I click "Shopping List Nancy" on shopping list widget
    Then I should see following grid containing rows:
      | SKU   | Product   | Availability | Qty Update All | Price    | Subtotal |
      | PSKU1 | Product 1 | IN STOCK     | 2 item         | $12.3456 | $24.69   |
    And I should see "Subtotal $24.69" in the "Subtotals" element
