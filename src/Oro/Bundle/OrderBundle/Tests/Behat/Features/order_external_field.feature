@fixture-OroOrderBundle:externalOrder.yml

Feature: Order External Field
  In order to monitor whether order was created by the 3rd party application
  As an Administrator
  I want to be able to see the value of order's ecternal field

  Scenario: Order status field in the orders grid
    Given I login as administrator
    When I go to Sales/Orders
    And I show column Is External in grid
    Then I should see following grid:
      | Order Number  | Is External |
      | ExternalOrder | Yes         |
      | SecondOrder   | No          |
      | SimpleOrder   | No          |
    When I check "No" in "Is External" filter
    Then I should see following grid:
      | Order Number  | Is External |
      | SecondOrder   | No          |
      | SimpleOrder   | No          |
    When I check "Yes" in "Is External" filter
    Then I should see following grid:
      | Order Number  | Is External |
      | ExternalOrder | Yes         |

  Scenario: Order status field in the order view page
    Given I login as administrator
    When I go to Sales/Orders
    And I click view "ExternalOrder" in grid
    Then I should see "External Order"
