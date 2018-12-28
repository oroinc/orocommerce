@fixture-OroOrderBundle:OrdersGrid.yml
@ticket-BB-12733

Feature: Orders Grid

  In order to ensure backoffice Orders Grid works correctly
  As an administrator
  I check that grid views work correctly.

  Scenario: Check grid views are applied correctly
    Given I login as administrator
    When I go to Sales / Orders
    Then I should see "Open Orders"
    And I should see following grid:
      | Order Number |
      | order1       |
      | order3       |
    When I click grid view list
    And I click "All Orders"
    Then I should see following grid:
      | Order Number |
      | order1       |
      | order2       |
      | order3       |
    And I should see "All Orders"
    When I reset "Orders Grid" grid
    Then I should see following grid:
      | Order Number |
      | order1       |
      | order2       |
      | order3       |
    And I should see "All Orders"
