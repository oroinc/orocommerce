@feature-BB-24920
@regression
@fixture-OroCommerceBundle:CustomerUserFixture.yml
@elasticsearch

Feature: Validate Quick Access Menu in Customer Dashboard
  In order to ensure the correct functionality of the Quick Access Menu in the customer dashboard
  As an administrator
  I should be able to configure and validate its visibility and navigation for customer users

  Scenario: Enable Conversations Feature and Global Search History Feature for Customer Dashboard
    Given I set configuration property "oro_conversation.enable_conversation" to "1"
    And I set configuration property "oro_website_search.enable_global_search_history_feature" to "1"

  Scenario: Verify Presence of Quick Access Menu Items
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I click "Account Dropdown"
    When I click "Dashboard"
    Then I should see an "Quick Access Dashboard Menu" element
    And I should see that "Quick Access Dashboard Menu" contains "My Profile"
    And I should see that "Quick Access Dashboard Menu" contains "Conversations"
    And I should see that "Quick Access Dashboard Menu" contains "Order History"
    And I should see that "Quick Access Dashboard Menu" contains "Quotes"
    And I should see that "Quick Access Dashboard Menu" contains "Shopping Lists"
    And I should see that "Quick Access Dashboard Menu" contains "Requests For Quote"
    And I should see that "Quick Access Dashboard Menu" contains "Saved Searches"

  Scenario: Validate Navigation to Conversations Page
    When I click "Account Dropdown"
    And I click "Dashboard"
    And I click "Conversations" in "Quick Access Dashboard Menu" element
    Then I should see that "Conversations Block" contains "Conversations"

  Scenario Outline: Verify Navigation to Various Sections from Quick Access Menu
    When I click "Account Dropdown"
    And I click "Dashboard"
    And I click "<Name>" in "Quick Access Dashboard Menu" element
    Then I should see that "Page Title" contains "<Name>"

    Examples:
      | Name               |
      | My Profile         |
      | Order History      |
      | Quotes             |
      | Shopping Lists     |
      | Requests For Quote |
      | Saved Searches     |
