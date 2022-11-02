@ticket-BB-16078
@fixture-OroShoppingListBundle:ShoppingListFixture.yml

Feature: Shopping Lists on Customer User view page
  In order to understand which shopping lists associated to the customer user
  As an Administrator
  I want to have a grid with related Shopping List on Customer User view page

  Scenario: Check Order view page
    Given I login as administrator
    And I go to Customers / Customer Users
    And I filter "Email Address" as contains "AmandaRCole"
    When I click view AmandaRCole in grid
    Then I should see following "Customer User Shopping lists Grid" grid:
      | ID | Label           | Notes | Subtotal |
      | 3  | Shopping List 5 |       | $0.00    |
      | 1  | Shopping List 1 |       | $0.00    |
