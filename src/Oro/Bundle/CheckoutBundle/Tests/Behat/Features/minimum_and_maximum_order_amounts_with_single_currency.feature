@ticket-BB-26895
@regression

Feature: Minimum and maximum order amounts with single currency
  As an administrator
  I want to be able to save Minimum and Maximum Order Amount settings when only one currency is enabled

  Scenario: Remove EUR currency from organization configuration
    Given I login as administrator
    When I go to System/ User Management/ Organizations
    And I click view ORO in grid
    And I click "Configuration"
    And I follow "System Configuration/General Setup/Currency" on configuration sidebar
    And uncheck "Use System" for "Allowed Currencies" field
    And I click "Delete currency EUR"
    And I confirm deletion
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Remove EUR currency from system configuration leaving only USD enabled
    When I go to System/ Configuration
    And I follow "System Configuration/General Setup/Currency" on configuration sidebar
    And I click "Delete currency EUR"
    And I confirm deletion
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Set minimum and maximum order amounts in the system config with single currency
    When I go to System/Configuration
    And I follow "Commerce/Sales/Checkout" on configuration sidebar
    And uncheck "Use default" for "Minimum Order Amount" field
    And I fill in "Minimum Order Amount USD Config Field" with "5"
    And uncheck "Use default" for "Maximum Order Amount" field
    And I fill in "Maximum Order Amount USD Config Field" with "500"
    And I click "Save settings"
    Then I should see "Configuration saved" flash message
