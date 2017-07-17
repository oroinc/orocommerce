@fixture-OroShoppingListBundle:DuplicateList.yml
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
      | User  |first_session |
      | Admin |second_session|

  Scenario: Front - user without permissions
    Given I proceed as the Admin
    And I signed in as AmandaRCole@example.org on the store frontend
    And click "Account"
    And click "Roles"
    And click edit "Buyer" in grid
    And click "Shopping"
    And select following permissions:
      | Shopping List | Duplicate:None |
    And click "Save"
    And I proceed as the User
    And I signed in as NancyJSallee@example.org on the store frontend
    When open page with shopping list "My Shopping List"
    Then I should see "My Shopping List"
    And I should not see following buttons:
      |Duplicate List|

  Scenario: Front - user with permissions
    Given I proceed as the Admin
    And click "Roles"
    And click edit "Customizable" in grid
    And click "Shopping"
    And select following permissions:
      |Shopping List|Duplicate:User|
    And click "Save"
    And I proceed as the User
    When reload the page
    Then I should see following buttons:
      |Duplicate List|
    When click "Duplicate List"
    Then should see 'Shopping list "My Shopping List" has been duplicated' flash message
    And should see "My Shopping List (copied"
    And I should see following line items in "Shopping List Line Items Table":
      |SKU |Quantity|Unit|
      |SKU1|10      |item|
      |SKU2|11      |item|
    And open page with shopping list "My Shopping List"
    And click "Edit Shopping List Label"
    And type "Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium.12345" in "value"
    And click "Save"
    And click "Sign Out"

  Scenario: Backend - user without permissions
    Given I proceed as the User
    And I login as "Charlie1@example.com" user
    And go to Sales/ Shopping Lists
    When I click view "Lorem ipsum dolor" in grid
    Then I should not see following buttons:
      |Duplicate List|

  @skip
  # todo: uncomment after resolve BB-10693
  Scenario: Backend - user with permissions
    Given user have "Organization" permissions for "Duplicate" "Shopping List" entity
    And I proceed as the User
    When reload the page
    Then I should see following buttons:
      |Duplicate List|
    And click "Duplicate List"
    Then should see 'Shopping list "Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium.12345" has been duplicated' flash message
    And should see "Donec quam felis, ultricies nec,… (copied"
    And should see following grid:
      |SKU |Product |Quantity|Unit|
      |SKU1|Product1|10      |item|
      |SKU2|Product2|11      |item|
    And click Logout in user menu
    And I signed in as NancyJSallee@example.org on the store frontend
    Then click "Frontend Shopping Lists Dropdown"
    And click "Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec,… (copied"
    And I should see "Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec,… (copied"
    And I should see following line items in "Shopping List Line Items Table":
      |SKU |Quantity|Unit|
      |SKU1|10      |item|
      |SKU2|11      |item|

  Scenario: Backend - user with permissions does not able to duplicate guest shopping list
    Given I login as administrator
    And I login as "Charlie1@example.com" user
    And go to Sales/ Shopping Lists
    When I click view "Guest Shopping List" in grid
    Then I should not see following buttons:
      |Duplicate List|

  Scenario: Backend - user with permissions does not able to duplicate shopping list for customer user when limit reached
    Given I login as administrator
    And I go to System/Configuration
    And I click "Commerce" on configuration sidebar
    And I click "Sales" on configuration sidebar
    And I click "Shopping List" on configuration sidebar
    And uncheck Use Default for "Shopping List Limit" field
    And I fill in "Shopping List Limit" with "1"
    And I save setting
    And I should see "Configuration saved" flash message
    And I login as "Charlie1@example.com" user
    And go to Sales/ Shopping Lists
    When I click view "Lorem ipsum dolor" in grid
    Then I should not see following buttons:
      |Duplicate List|
