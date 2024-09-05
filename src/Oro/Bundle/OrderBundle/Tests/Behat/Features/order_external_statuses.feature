@fixture-OroOrderBundle:order.yml
Feature: Order External Statuses
  In order to monitor order statuses managed by an external system
  As an Administrator
  I want to be able to see order external statuses in the back-office and in the storefront

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    And I login as administrator

  Scenario: Enable Order External Status Management
    Given I go to System/ Configuration
    And I follow "Commerce/Orders/Order Status Management" on configuration sidebar
    And uncheck "Use default" for "Enable External Status Management" field
    And I check "Enable External Status Management"
    When I save form
    Then I should see "Configuration saved" flash message
    And the "Enable External Status Management" checkbox should be checked

  Scenario: Verify order status in the back-office
    When I go to Sales/Orders
    Then I should see following grid:
      | Order Number | Status              | Internal Status |
      | SecondOrder  | Wait For Approval   | Open            |
      | SimpleOrder  |                     | Open            |
    When I click view SecondOrder in grid
    Then I should see that order internal status is "Open"
    And I should see a "Wait For Approval Order Status Badge" element
    When I click "Edit"
    Then I should see a "Wait For Approval Order Status Badge" element

  Scenario: Verify order status in the storefront
    Given I operate as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I click "Account Dropdown"
    And I click "Order History"
    Then I should see following "PastOrdersGrid" grid:
      | Order Number | Order Status      |
      | SecondOrder  | Wait For Approval |
      | SimpleOrder  |                   |
    When I click view "SecondOrder" in grid
    Then I should see "Order Status Wait For Approval"

  Scenario: Disable Order External Status Management
    Given I operate as the Admin
    And I go to System/ Configuration
    And I follow "Commerce/Orders/Order Status Management" on configuration sidebar
    And I uncheck "Enable External Status Management"
    When I save form
    Then I should see "Configuration saved" flash message
    And the "Enable External Status Management" checkbox should be unchecked

  Scenario: Verify order internal status in the storefront
    Given I operate as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I click "Account Dropdown"
    And I click "Order History"
    Then I should see following "PastOrdersGrid" grid:
      | Order Number | Order Status |
      | SecondOrder  | Open         |
      | SimpleOrder  | Open         |
    When I click view "SecondOrder" in grid
    Then I should see "Order Status Open"
