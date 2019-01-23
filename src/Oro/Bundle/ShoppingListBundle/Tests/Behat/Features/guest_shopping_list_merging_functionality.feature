@ticket-BB-10050-merge
@fixture-OroShoppingListBundle:ProductFixture.yml
Feature: Guest shopping list merging functionality
  As a guest I have a possibility to fill one shopping list and it should be added (or merged depending on limit)
  to customer user on login

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Set limit to One shopping list in configuration
    Given I proceed as the Admin
    And I login as administrator
    And I go to System/Configuration
    And I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And uncheck "Use default" for "Shopping List Limit" field
    And I fill in "Shopping List Limit" with "1"
    And uncheck "Use default" for "Enable guest shopping list" field
    And I check "Enable guest shopping list"
    And I save setting
    And I should see "Configuration saved" flash message

  Scenario: Check no customer shopping lists by default
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I should see "No Shopping Lists"
    And I click "Sign Out"

  Scenario: Create shopping list as a guest
    Given I am on homepage
    And I should see "No Shopping Lists"
    And I should see "Shopping List"
    And type "PSKU1" in "search"
    And I click "Search Button"
    And I should see "Product1"
    And I should see "Add to Shopping List"
    And I click "View Details" for "PSKU1" product
    And I should see "Add to Shopping List"
    And I click "Add to Shopping List"
    And I should see "Product has been added to" flash message
    And I should see "In shopping list"
    And I hover on "Shopping List Widget"
    And I should see "1 Item | $0.00" in the "Shopping List Widget" element

  Scenario: Check guest shopping list was added to customer
    Given I signed in as AmandaRCole@example.org on the store frontend in old session
    And I should see "Shopping List"
    And I open shopping list widget
    And I click "Shopping List" on shopping list widget
    And I should see "PSKU1"
    And click "Sign Out"

  Scenario: Create other shopping List as a guest
    Given I am on homepage
    And I should see "Shopping List"
    And type "CONTROL1" in "search"
    And I click "Search Button"
    And I should see "Control Product"
    When I click "Add to Shopping List" for "CONTROL1" product
    Then I should see "Product has been added to" flash message

  Scenario: Check guest shopping list was merged to existing customer shopping list
    Given I signed in as AmandaRCole@example.org on the store frontend in old session
    And I should see "Shopping List"
    And I open shopping list widget
    And I click "Shopping List" on shopping list widget
    And I should see "PSKU1"
    Then I should see "CONTROL1"
