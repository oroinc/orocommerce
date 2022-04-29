@regression
@ticket-BB-10412
@automatically-ticket-tagged
Feature: Pricing configuration validation

  Scenario: Default Currency field validation should not affect other fields validation
    Given I login as administrator
    When I go to System/Configuration
    And I follow "Commerce/Catalog/Pricing" on configuration sidebar
    And I fill "Pricing Configuration Form" with:
      | Default Currency | Euro (€) |
    And I save form
    Then I should see "Currency Euro is not enabled"
    And I should not see "This value should be of type float."
    When I fill "Pricing Configuration Form" with:
      | Default Currency | US Dollar ($) |
    And I save form
    Then I should see "Configuration saved" flash message
