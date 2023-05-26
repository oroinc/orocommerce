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
      | SKU   | Item      |          | Qty Update All | Price   | Subtotal |
      | PSKU1 | Product 1 | In Stock | 1 item         | $1.2345 | $1.2345  |
    And I should see "Subtotal $1.23" in the "Subtotals" element

  Scenario: Check that line item price is default for Nancy
    Given I proceed as the Nancy
    And I signed in as NancyJSallee@example.org on the store frontend
    And I hover on "Shopping Cart"
    When I click "Shopping List Nancy" on shopping list widget
    Then I should see following grid containing rows:
      | SKU   | Item      |          | Qty Update All | Price    | Subtotal |
      | PSKU1 | Product 1 | In Stock | 1 item         | $12.3456 | $12.3456 |
    And I should see "Subtotal $12.35" in the "Subtotals" element
