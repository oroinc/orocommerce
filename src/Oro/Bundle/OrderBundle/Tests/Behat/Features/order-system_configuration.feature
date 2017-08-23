@ticket-BB-9594
Feature: Order System Configuration
  In order to cancel orders after their "do not ship later than" date
  As an Administrator
  I want to have a configuration option to enable automatic order cancellation

  Scenario: Verify default values for system configuration
    Given I login as administrator
    And go to System / Configuration
    When I follow "Commerce/Orders/Order Creation" on configuration sidebar
    Then the "Use default" checkbox should be checked
    And I should see "New Internal Order Status"
    When I follow "Commerce/Orders/Order Automation" on configuration sidebar
    Then the "Use default" checkbox should be checked
    And I should see "Enable Automatic Order Cancellation"
    And I should not see "Applicable Statuses"
    And I should not see "Target Status"
    And I save setting

    When uncheck "Use default" for "Enable Automatic Order Cancellation" field
    And I check "Enable Automatic Order Cancellation"
    And I should see "Applicable Statuses"
    And I should see "Target Status"
