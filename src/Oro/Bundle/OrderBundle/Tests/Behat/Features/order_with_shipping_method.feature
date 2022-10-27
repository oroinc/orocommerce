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
    When I click "Flat Rate"
    And I save and close form
    Then go to Sales/Orders
    And I should see SimpleOrder in grid with following data:
      | Currency  | USD    |
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
