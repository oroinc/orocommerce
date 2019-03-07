@ticket-BB-16078
@fixture-OroShoppingListBundle:ShoppingListFixture.yml

Feature: Shopping Lists on Customer view page
  In order to understand which shopping lists associated to the customer
  As an Administrator
  I want to have a grid with related Shopping List on Customer view page

  Scenario: Check Order view page
    Given I login as administrator
    And I go to Customers / Customers
    And I filter "Name" as contains "first customer"
    When I click view first customer in grid
    Then I should see following "Customer Shopping lists Grid" grid:
      | Customer User | ID | Label           | Notes | Subtotal |
      | Amanda Cole   | 3  | Shopping List 5 |       | $0.00    |
      | Amanda Cole   | 1  | Shopping List 1 |       | $0.00    |
