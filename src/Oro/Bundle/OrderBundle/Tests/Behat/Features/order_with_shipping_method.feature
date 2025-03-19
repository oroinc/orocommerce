@ticket-BB-15946
@fixture-OroOrderBundle:order.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroCheckoutBundle:Shipping.yml
Feature: Order with shipping method
  In order to override shipping cost amount in order
  As an administrator
  I need to have ability to manage shipping cost amount in Order

  Scenario: Add Flat rate to order
    Given I login as administrator
    And go to Sales/Orders
    And click edit SimpleOrder in grid
    When click "Calculate Shipping Button"
    And I click "Flat Rate"
    And I save and close form
    Then go to Sales/Orders
    And I should see SimpleOrder in grid with following data:
      | Currency  | USD    |
      | Total     | $53.00 |
      | Total ($) | $53.00 |

  Scenario: Change the price for shipping rules
    Given I go to System/ Shipping Rules
    And click "edit" on first row in grid
    When I fill form with:
      | Price | 2 |
    And save and close form
    Then I should see "Shipping rule has been saved" flash message

  Scenario: Re-save order with changed shipping prices
    Given I go to Sales/Orders
    And click edit SimpleOrder in grid
    When I save and close form
    And go to Sales/Orders
    # The price has not changed because the order has already been created and paid for.
    Then I should see SimpleOrder in grid with following data:
      | Total     | $53.00 |
      | Total ($) | $53.00 |

  Scenario: Override shipping cost to 5
    When I click edit SimpleOrder in grid
    And I type "5" in "Overridden shipping cost amount"
    And I save and close form
    Then go to Sales/Orders
    And I should see SimpleOrder in grid with following data:
      | Currency  | USD    |
      | Total     | $55.00 |
      | Total ($) | $55.00 |

  Scenario: Override shipping cost to 0
    When I click edit SimpleOrder in grid
    And I type "0" in "Overridden shipping cost amount"
    And I save and close form
    Then go to Sales/Orders
    And I should see SimpleOrder in grid with following data:
      | Currency  | USD    |
      | Total     | $50.00 |
      | Total ($) | $50.00 |
