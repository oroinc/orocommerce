@ticket-BB-10050-owner
@fixture-OroShoppingListBundle:ProductFixture.yml
@fixture-OroShoppingListBundle:UserFixture.yml
Feature: Guest shopping lists owner
  As administrator I should have a possibility to change default guest shopping list owner in configuration

  Scenario: Change default owner to new user
    Given I login as administrator
    And I go to System/Configuration
    And I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And uncheck "Use default" for "Enable guest shopping list" field
    And I check "Enable guest shopping list"
    And uncheck "Use default" for "Default Guest Shopping List Owner" field
    And I fill in "Default Guest Shopping List Owner" with "Admin User - newadmin@example.com (newadmin)"
    And I should see "Admin User"
    And I save setting
    And I should see "Configuration saved" flash message

  Scenario: Create shopping list on frontend
    Given I am on homepage
    And I should see "Shopping list"
    And type "PSKU1" in "search"
    And I click "Search Button"
    And I should see "Product1"
    And I should see "Add to Shopping List"
    And I click "View Details" for "PSKU1" product
    And I should see "Add to Shopping List"
    And I click "Add to Shopping List"
    And I should see "Product has been added to" flash message
    Then I should see "In shopping list"

  Scenario: Check shopping list saved with correct owner
    Given I login as administrator
    And I go to Sales/Shopping Lists
    And I click View Shopping List in grid
    Then I should see "Owner: Admin User (Main)"
