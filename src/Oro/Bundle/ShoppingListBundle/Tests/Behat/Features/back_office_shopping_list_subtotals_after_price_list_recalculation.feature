@regression
@ticket-BB-28024
@pricing-storage-combined
@fixture-OroShoppingListBundle:MyShoppingListsFixture.yml
Feature: Back office shopping list subtotals after price list recalculation
  In order to see the actual shopping list subtotals in the back office
  As an Administrator
  I want the shopping list grids to show the subtotal after a combined price list rebuild without a storefront visit

  Scenario: Feature Background
    Given I login as administrator

  Scenario: Shopping list subtotals before the price list recalculation
    When I go to Sales / Shopping Lists
    Then I should see following grid containing rows:
      | Label           | Subtotal  |
      | Shopping List 3 | $8,818.00 |
      | Shopping List 2 | $1,178.00 |
      | Shopping List 1 | $1,581.00 |

  Scenario: Recalculate the price list
    When I go to Sales / Price Lists
    And I click view "Default Price List" in grid
    And I click "Recalculate"
    Then I should see "Product Prices have been successfully recalculated" flash message

  Scenario: Shopping list subtotals in the Shopping Lists grid after the price list recalculation
    When I go to Sales / Shopping Lists
    Then I should see following grid containing rows:
      | Label           | Subtotal  |
      | Shopping List 3 | $8,818.00 |
      | Shopping List 2 | $1,178.00 |
      | Shopping List 1 | $1,581.00 |

  Scenario: Shopping list subtotals on the Customer view page after the price list recalculation
    When I go to Customers / Customers
    And I filter "Name" as contains "first customer"
    And I click view first customer in grid
    Then I should see following "Customer Shopping lists Grid" grid containing rows:
      | Customer User | Label           | Subtotal  |
      | Amanda Cole   | Shopping List 3 | $8,818.00 |
      | Nancy Sallee  | Shopping List 2 | $1,178.00 |
      | Amanda Cole   | Shopping List 1 | $1,581.00 |

  Scenario: Shopping list subtotals on the Customer User view page after the price list recalculation
    When I go to Customers / Customer Users
    And I filter "Email Address" as contains "AmandaRCole"
    And I click view AmandaRCole in grid
    Then I should see following "Customer User Shopping lists Grid" grid containing rows:
      | Label           | Subtotal  |
      | Shopping List 3 | $8,818.00 |
      | Shopping List 1 | $1,581.00 |
