@regression
@ticket-BB-24640

Feature: Toggle promotions configuration
  In order to control the availability of promotions
  As an Administrator
  I should be able to disable and enable the "Enable Promotions" setting in system configuration

  Scenario: Enable and disable promotions via system configuration
    Given I login as administrator
    And go to System/ Configuration
    When I follow "Commerce/Sales/Promotions" on configuration sidebar
    Then the "Enable Promotions" checkbox should be checked
    When I uncheck "Use default" for "Enable Promotions" field
    And uncheck "Enable Promotions"
    And save form
    Then I should see "Configuration saved" flash message
    When I check "Enable Promotions"
    And save form
    Then I should see "Configuration saved" flash message

