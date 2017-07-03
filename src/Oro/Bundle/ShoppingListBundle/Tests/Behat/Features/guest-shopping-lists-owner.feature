@ticket-BB-10050-owner
@fixture-ProductFixture.yml
@fixture-UserFixture.yml
Feature: Guest shopping lists owner
 As administrator I should have a possibility to change default guest shopping list owner in configuration

  Scenario: Change default owner to new user
    Given I login as administrator
    And I go to System/Configuration
    And I click "Commerce" on configuration sidebar
    And I click "Sales" on configuration sidebar
    And I click "Guest Shopping List" on configuration sidebar
    And uncheck Use Default for "Default Guest Shopping List Owner" field
    And I fill in "Select2Entity" with "Admin User - newadmin@example.com (newadmin)"
    And I should see "Admin User"
    And I save setting
    And I should see "Configuration saved" flash message

  Scenario: Create shopping list on frontend
    Given I visit store frontend as guest
    And I should see "Shopping list"
    And type "PSKU1" in "search"
    And I click "FrontendSearchButton"
    And I should see "Product1"
    And I should see "Add to Shopping list"
    And I click "Product1"
    And I should see "Add to Shopping list"
    And I click "Add to Shopping list"
    And I should see "Product has been added to" flash message
    Then I should see "In shopping list"

  Scenario: Check shopping list saved with correct owner
    Given I login as administrator
    And I go to Sales/Shopping Lists
    And I click View Shopping list in grid
    Then I should see "Owner: Admin User (Main)"
