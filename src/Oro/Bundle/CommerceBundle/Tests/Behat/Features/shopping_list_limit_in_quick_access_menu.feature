@feature-BB-24920
@regression
@fixture-OroCommerceBundle:CustomerUserFixture.yml
@elasticsearch

Feature: Shopping List Limit in Quick Access Menu
  In order to control the visibility of the "Shopping Lists" menu item in the customer dashboard
  As an administrator
  I should be able to configure a shopping list limit and verify its effect on customer users

  Scenario: Initialize User Sessions
    Given sessions active:
      | Buyer | first_session  |
      | Admin | second_session |

  Scenario: Enable Conversations Feature and Global Search History
    And I set configuration property "oro_conversation.enable_conversation" to "1"
    And I set configuration property "oro_website_search.enable_global_search_history_feature" to "1"

  Scenario: Validate "Shopping Lists" Menu Item Before Limit is Applied
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I click "Account Dropdown"
    When I click "Dashboard"
    Then I should see that "Quick Access Dashboard Menu" contains "Shopping Lists"

  Scenario: Set Shopping List Limit to 1 in Configuration
    Given I proceed as the Admin
    And I login as administrator
    And I go to System / Configuration
    When I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And uncheck "Use default" for "Shopping List Limit" field
    And I fill in "Shopping List Limit" with "1"
    And I save setting
    Then I should see "Configuration saved" flash message

  Scenario: Verify "Shopping Lists" is Hidden After Limit is Applied
    Given I operate as the Buyer
    When I reload the page
    Then I should see that "Quick Access Dashboard Menu" does not contain "Shopping Lists"
