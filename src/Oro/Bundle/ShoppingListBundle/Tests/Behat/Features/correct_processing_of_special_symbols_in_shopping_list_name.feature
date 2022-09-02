@fixture-OroShoppingListBundle:ShoppingListFixtureWithSpecialName.yml
Feature: Correct processing of special symbols in shopping list name
  In order to be able to add products to the shopping list with specific name
  As a Customer User
  I add the product to the shopping list

  Scenario: Check processing of special symbols in shopping list name
    Given I login as AmandaRCole@example.org buyer
    And I type "AA1" in "search"
    And I click "Search Button"
    When I press "ShoppingListAdd"
    Then I should see "Product has been added to" flash message and I close it
    When I hover on "Shopping List Widget"
    Then I should see "1 Item | $0.00" in the "Shopping List Widget" element
