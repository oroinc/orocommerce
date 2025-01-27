@regression
@fixture-OroUPSBundle:AddressValidationUpsClient.yml
@feature-BB-24101
@behat-test-env

Feature: UPS Address Validation Integration
  Scenario: Enable UPS as Address Validation Provider
    Given I login as administrator
    And I go to System/ Configuration
    And follow "Commerce/Shipping/Address Validation" on configuration sidebar
    When I fill "Address Validation Configuration Form" with:
      | Address Validation Service Use Default | false |
      | Address Validation Service             | UPS   |
    And I submit form
    Then I should see "Configuration saved" flash message
