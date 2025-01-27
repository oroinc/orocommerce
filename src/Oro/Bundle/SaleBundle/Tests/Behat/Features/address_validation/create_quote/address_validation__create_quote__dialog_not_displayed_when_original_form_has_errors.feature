@fixture-OroUPSBundle:AddressValidationUpsClient.yml
@feature-BB-24101
@regression
@behat-test-env

Feature: Address Validation - Create Quote - Dialog Not Displayed When Original Form Has Errors
  As an Administrator
  I should not see the address validation dialog when the quote form contains validation errors

  Scenario: Feature Background
    Given I login as administrator
    And I go to System/ Configuration
    And follow "Commerce/Shipping/Address Validation" on configuration sidebar
    When I fill "Address Validation Configuration Form" with:
      | Address Validation Service Use Default | false |
      | Address Validation Service             | UPS   |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Submit not valid Quote Form
    Given I go to Sales/ Quotes
    And I click "Create Quote"
    And I click "Shipping Address"
    When I fill "Quote Form" with:
      | Shipping Address             | Enter other address |
      | Shipping Address First name  | Name                |
      | Shipping Address Last name   | Last name           |
      | Shipping Address Country     | United States       |
      | Shipping Address Street      | 801 Scenic Hwy      |
      | Shipping Address City        | Haines City         |
      | Shipping Address State       | Florida             |
      | Shipping Address Postal Code | 33844               |
    And I save and close form
    Then I should see "Product cannot be empty."
    And I should not see "Confirm Your Address"
