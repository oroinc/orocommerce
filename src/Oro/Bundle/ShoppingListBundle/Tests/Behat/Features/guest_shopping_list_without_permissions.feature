@regression
@ticket-BAP-18470
@fixture-OroShoppingListBundle:GuestShoppingListsFixture.yml
Feature: Guest Shopping Lists Turned On But Guest Role Has No Permissions To Interact With Shopping List
  In order to follow Guest Role Has No Permissions
  As a Guest User
  I can not interact with Shopping List

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Guest | system_session |

  Scenario: Set guest shopping list in configurations
    Given I proceed as the Admin
    And I login as administrator
    And I go to System/Configuration
    When I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And uncheck "Use default" for "Enable Guest Shopping List" field
    And I check "Enable Guest Shopping List"
    And I save setting
    Then I should see "Configuration saved" flash message
    When I go to Customers/ Customer User Roles
    And I click edit "Non-Authenticated Visitors" in grid
    And select following permissions:
      | Shopping List | View:None | Edit:None |
    And I save and close form
    Then I should see "Customer User Role has been saved" flash message

  Scenario: Check that guest cannot see shopping list block and buttons
    Given I proceed as the Guest
    When I am on homepage
    Then I should not see "Shopping list"
    When I open product with sku "PSKU1" on the store frontend
    Then I should not see "Add to Shopping List"
