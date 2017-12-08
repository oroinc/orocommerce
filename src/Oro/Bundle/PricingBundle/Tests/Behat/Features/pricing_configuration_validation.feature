@regression
@ticket-BB-10412
@automatically-ticket-tagged
Feature: Pricing configuration validation
  ToDo: BAP-16103 Add missing descriptions to the Behat features

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

  Scenario: Offset of processing CPL Prices should validate wrong data
    When I fill "Pricing Configuration Form" with:
      | Offset Of Processing CPL Prices Use Default | false |
      | Offset Of Processing CPL Prices             | qqqq  |
    Then I should see "Pricing Configuration Form" validation errors:
      | Offset Of Processing CPL Prices | This value should be of type float. |
    When I fill "Pricing Configuration Form" with:
      | Offset Of Processing CPL Prices | -10 |
    Then I should see "Pricing Configuration Form" validation errors:
      | Offset Of Processing CPL Prices | This value should be greater than 0. |
    When I fill "Pricing Configuration Form" with:
      | Offset Of Processing CPL Prices | 10.5 |
    And I save form
    Then I should see "Configuration saved" flash message
