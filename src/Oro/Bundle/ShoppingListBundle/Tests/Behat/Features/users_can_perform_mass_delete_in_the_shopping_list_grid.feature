@fixture-OroProductBundle:mass_delete_action_in_products_grid.yml
@regression

Feature: Users can perform mass delete in the shopping list grid
  In order to manage shopping list contents efficiently
  As a user
  I want to be able to delete multiple items from the shopping list using mass actions

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Admin enables shopping list for all users
    Given I proceed as the Admin
    And login as administrator
    And go to System/ Configuration
    When I follow "Commerce/Sales/Shopping List" on configuration sidebar
    Then the "Enable Guest Shopping List" checkbox should not be checked
    When uncheck "Use default" for "Enable Guest Shopping List" field
    And check "Enable Guest Shopping List"
    And save form
    Then I should see "Configuration saved" flash message
    And the "Enable Guest Shopping List" checkbox should be checked

  Scenario: User deletes product from shopping list using mass action
    Given I proceed as the Buyer
    And I am on homepage
    And click "Search Button"
    And click "Add to Shopping List" for "PSKU1" product
    And click "Add to Shopping List" for "PSKU2" product
    And I open shopping list widget
    And click "Open List"
    When I filter "SKU" as contains "PSKU2"
    And check first 1 records in grid
    And click "Delete"
    And click "Yes, Delete" in confirmation dialogue
    Then I should see "1 item(s) have been deleted successfully" flash message
