@regression
@behat-test-env
@feature-BB-24101

Feature: Fedex shipping integration
  Scenario: Create FedEx integration
    Given I login as administrator
    And go to System/ Integrations/ Manage Integrations
    And click "Create Integration"
    When I fill "Integration Form" with:
      | Type                    | FedEx                    |
      | Name                    | FedEx                    |
      | Label                   | FedEx                    |
      | Test Mode               | true                     |
      | Project API Key         | test_key                 |
      | Project API Secret Key  | test_secret_key          |
      | Shipping Account Number | 1234                     |
      | Shipping Services       | FedEx Priority Overnight |
    And save and close form
    Then I should see "Integration saved" flash message

  Scenario: Enable FedEx as Address Validation Provider
    Given I login as administrator
    And I go to System/ Configuration
    And follow "Commerce/Shipping/Address Validation" on configuration sidebar
    When I fill "Address Validation Configuration Form" with:
      | Address Validation Service Use Default | false |
      | Address Validation Service             | FedEx |
    And I submit form
    Then I should see "Configuration saved" flash message
