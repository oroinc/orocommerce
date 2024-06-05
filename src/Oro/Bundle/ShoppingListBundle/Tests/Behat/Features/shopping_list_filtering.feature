@regression
@ticket-BB-21978
@fixture-OroShoppingListBundle:ShoppingListFixture.yml
Feature: Shopping list filtering
  In order to manage shopping lists on back office
  As an Admin
  I need to be able to filter shopping lists

  Scenario: Check filter by ID
    Given I login as administrator
    And I go to Sales/Shopping Lists
    When I filter ID as equals "2147483647"
    And there are 0 records in grid
    When I filter ID as equals "2147483648"
    Then should see "The entered value must be less or equal to 2147483647" flash message
