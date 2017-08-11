@ticket-BB-10050-limit
@fixture-OroShoppingListBundle:ProductFixture.yml
Feature: Shopping list limit
  As Administrator I have a possibility to restrict limit of shopping lists for customer in configuration

  Scenario: Unlimited shopping list default configuration check
    Given I login as administrator
    And I go to System/Configuration
    And I follow "Commerce/Sales/Shopping List" on configuration sidebar
    Then the "Use default" checkbox should be checked

  Scenario: Unlimited shopping list on frontend
    And I signed in as AmandaRCole@example.org on the store frontend
    And type "Prod1" in "search"
    And click "Search Button"
    And type "PSKU1" in "search"
    And I click "Search Button"
    And I click "View Details" for "PSKU1" product
    And I should see "Add to Shoppin..."
    And I click "Add to Shoppin..."
    And I should see "Product has been added to" flash message
    And I should see "In shopping list"
    And I should see "1 Shopping List"
    And I open shopping list widget
    And I click "Create New List"
    And I click "Create"
    Then I should see "2 Shopping Lists"

  Scenario: Remove one shopping list
    Given I open shopping list widget
    And I click "View Details"
    And I click "Delete"
    And I click "Yes, Delete"
    And I should see "Shopping List deleted" flash message
    And I should see "1 Shopping List"

  Scenario: Set limit to One shopping list in configuration
    Given I login as administrator
    And I go to System/Configuration
    And I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And uncheck Use Default for "Shopping List Limit" field
    And I fill in "Shopping List Limit" with "1"
    And I save setting
    And I should see "Configuration saved" flash message

  Scenario: Check limit is applied on frontend
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I should not see "1 Shopping List"
    And I should see "Shopping List"
    And I open shopping list widget
    And I should not see "Create New List"
