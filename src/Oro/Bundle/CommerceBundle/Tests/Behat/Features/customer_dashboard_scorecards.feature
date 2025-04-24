@feature-BB-25440
@regression
@fixture-OroCommerceBundle:CustomerUserFixture.yml
@fixture-OroCommerceBundle:ProductFixture.yml
@fixture-OroCommerceBundle:RfqFixture.yml
@fixture-OroCommerceBundle:ShoppingListFixture.yml
@fixture-OroCommerceBundle:OrderFixture.yml

Feature: Customer Dashboard Scorecards

  Scenario: Initialize User Sessions
    Given sessions active:
      | Admin | system_session |
      | Buyer | first_session  |

  Scenario: Validate Default Scorecards Theme Configuration Settings
    Given I proceed as the Admin
    And I login as administrator
    When I go to System / Theme Configurations
    And I click "Edit" on row "Refreshing Teal" in grid
    Then "Theme Configuration Form" must contain values:
      | Customer Dashboard Scorecard     | users          |
      | Customer Dashboard Scorecard (2) | shopping-lists |
      | Customer Dashboard Scorecard (3) | open-rfqs      |
      | Customer Dashboard Scorecard (4) | total-orders   |

  Scenario: Verify Scorecard Widgets Display on Buyer Dashboard
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I click "Account Dropdown"
    When I click "Dashboard"
    Then I should see that "Users Scorecard Widget" contains "1 Users"
    And I should see that "Shopping Lists Scorecard Widget" contains "5 Shopping Lists"
    And I should see that "Open RFQs Scorecard Widget" contains "5 Open RFQs"
    And I should see that "Total Orders Scorecard Widget" contains "$50.00 Total Orders"

  Scenario Outline: Verify Navigation to Various Sections from Scorecards
    When I click "Account Dropdown"
    And I click "Dashboard"
    And I click "<Name>" in "Scorecards Container" element
    Then I should see that "Page Title" contains "<Page Title>"

    Examples:
      | Name           | Page Title         |
      | Users          | Users              |
      | Shopping Lists | Shopping Lists     |
      | Open RFQs      | Requests For Quote |
      | Total Orders   | Order History      |

  Scenario: Disable Scorecard Widgets via Theme Configuration
    Given I proceed as the Admin
    When I fill "Theme Configuration Form" with:
      | Customer Dashboard Scorecard (2) | |
      | Customer Dashboard Scorecard (3) | |
      | Customer Dashboard Scorecard (4) | |
    And I save and close form
    Then I should see "Theme Configuration" flash message

  Scenario: Validate Hidden Scorecard Widgets on Buyer Dashboard
    Given I proceed as the Buyer
    When I click "Account Dropdown"
    And I click "Dashboard"
    Then I should not see an "Shopping Lists Scorecard Widget" element
    And I should not see an "Open RFQs Scorecard Widget" element
    And I should not see an "Total Orders Scorecard Widget" element
    And I should see that "Users Scorecard Widget" contains "1 Users"

  Scenario: Unset link field for Users Scorecard Widget
    Given I proceed as the Admin
    And I go to Marketing/Content Widgets
    When I click Edit users in grid
    And I fill "Content Widget Form" with:
      | Link |  |
    And I save and close form
    Then I should see "Content widget has been saved" flash message

  Scenario: Display and Navigate User's Scorecard Widget without a Link Field
    Given I proceed as the Buyer
    When I reload the page
    Then I should see that "Users Scorecard Widget" contains "1 Users"
    And I keep in mind current path
    When I click on "Users Scorecard Widget"
    Then path remained the same

  Scenario: Set "View: None" permission for the Customer User
    Given I proceed as the Admin
    And I go to Customers/Customer User Roles
    When I click edit "Buyer" in grid
    And select following permissions:
      | Customer User | View:None |
    And I save form
    Then I should see "Customer User Role has been saved" flash message

  Scenario: Validate Hidden Scorecard Widget on Buyer Dashboard When no View Permission
    Given I proceed as the Buyer
    When I reload the page
    Then I should not see an "Users Scorecard Widget" element
