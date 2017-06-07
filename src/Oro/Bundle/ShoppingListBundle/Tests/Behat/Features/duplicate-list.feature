@fixture-DuplicateList.yml
Feature: Duplicate Lists
  In order to create multiple similar orders
  As a Customer User
  I want to duplicate (clone) my shopping list

#  Description
#  Create "Duplicate List" operation for shopping lists on the store frontend.
#  Create a separate permission "Duplicate" for the Shopping List entity in Customer User Roles (available on Customer User Role edit pages both on store frontend and in the backoffice).
#  Show the "Duplicate List" as a button next to (to the left) of the "Delete List" button.
#  When a shopping list is duplicated, copy its entire content. The shopping list name should be modified - append " (copied 2017-12-01 23:45)" (where 2017-12-01 23:45 should be the date and time when a duplicate list was created.
#  If the duplication was successfull, show the edit page of the new shopping list and show the success message "Shopping list "Abcdefg" has been duplicated" (where Abcdefg should be the name of the original shopping list.
#  Configuration
#  No configuration.
#  Acceptance Criteria
#  Show how a customer user can duplicate one of his shopping lists.
#  Show that shopping list duplication can be disabled for selected customer user role by a customer admin on the store frontend, as well as by an account manager in the backoffice.
#  Sample Data
#  No updates required.
#  Design & Mockups
#  Button icon - http://fontawesome.io/icon/clone/
#  The "Duplicate List" button should be located to the left of the "Delete" button.
#  Messages & Labels
#  Duplicate List
#  Shopping list "Abcdefg" has been duplicated
#  (copied 2017-12-01 23:45)

  Scenario: Create different window session
    Given sessions active:
      | Admin          |first_session |
      | User           |second_session|

  Scenario: Front - user without permissions
    Given I proceed as the Admin
    And I signed in as AmandaRCole@example.org on the store frontend
    And click "Account"
    And click "Roles"
    And click edit "Buyer" in grid
    And I wait for action
    And user have "None" permissions for "Duplicate" "Shopping List" entity
    And click "Save"
    And I proceed as the User
    And I signed in as NancyJSallee@example.org on the store frontend
    And click "NewCategory"
    And I wait for action
    And add "SKU1" product with "item" unit and "10" quantity to the shopping list
    And add "SKU2" product with "set" unit and "11" quantity to the shopping list
    When open "Shopping list" shopping list
    Then I should not see following buttons:
    |Duplicate List|

  Scenario: Front - user with permissions
    Given I proceed as the Admin
    And click "Roles"
    And click edit "Customizable" in grid
    And I wait for action
    And user have "User (Own)" permissions for "Duplicate" "Shopping List" entity
    And click "Save"
    And I proceed as the User
    When reload the page
    Then I should see following buttons:
      |Duplicate List|
    When click "Duplicate List"
    Then should see 'Shopping list "Shopping list" has been duplicated' flash message
    And should see "Shopping list (copied"
    And I should see following "Shopping list" grid:
      |SKU |Quantity|Unit|
      |SKU1|10      |item|
      |SKU2|11      |set |

  Scenario: Backend - user without permissions
#    Given I proceed as the Admin
#    And I login as administrator
#    And go to Customers/Customer User Roles
#    And click edit "Customizable" in grid
#    And I wait for action
#    And user have "None" permissions for "Duplicate" "Shopping List" entity
#    And save and close form
#    And should see "Customer User Role has been saved" flash message
#    And I proceed as the User
#    When reload the page
#    Then I should not see following buttons:
#      |Duplicate List|
#    And I wait for action

  Scenario: Backend - user with permissions
#    Given I proceed as the Admin
#    And go to Customers/Customer User Roles
#    And click edit "Customizable" in grid
#    And I wait for action
#    And user have "User (Own)" permissions for "Duplicate" "Shopping List" entity
#    And save and close form
#    And should see "Customer User Role has been saved" flash message
#    And I proceed as the User
#    When reload the page
#    Then I should see following buttons:
#      |Duplicate List|
#    When click "Duplicate List"
#    Then should see 'Shopping list "Shopping list" has been duplicated' flash message
#    And should see "Shopping list (copied"
##    And should see "SKU1" product with "10" quantity and "item" unit in the shopping list
##    And should see "SKU2" product with "11" quantity and "set" unit in the shopping list
#    And I wait for action