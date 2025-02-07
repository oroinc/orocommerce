@fixture-OroUPSBundle:AddressValidationUpsClient.yml
@fixture-OroOrderBundle:OrderAddressesFixture.yml
@feature-BB-24101
@regression
@behat-test-env

Feature: Address Validation - Create Order - Dialog Not Displayed When Original Form Has Errors
  As an Administrator
  I should not see the address validation dialog when the order form contains validation errors

  Scenario: Feature Background
    Given I login as administrator
    And I go to System/ Configuration
    And follow "Commerce/Shipping/Address Validation" on configuration sidebar
    When I fill "Address Validation Configuration Form" with:
      | Address Validation Service Use Default | false |
      | Address Validation Service             | UPS   |
    And I submit form
    Then I should see "Configuration saved" flash message
    When I fill "Address Validation Configuration Order Form" with:
      | Validate Shipping Addresses on the Order Page Use Default | false |
      | Validate Billing Addresses on the Order Page Use Default  | false |
      | Validate Shipping Addresses on the Order Page             | true  |
      | Validate Billing Addresses on the Order Page              | true  |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Submit not valid Order Form
    Given I go to Sales/ Orders
    And I click "Create Order"
    And I click "Shipping Address"
    When I fill "Order Form" with:
      | Shipping Address             | Enter other address |
      | Shipping Address First name  | Name                |
      | Shipping Address Last name   | Last name           |
      | Shipping Address Country     | United States       |
      | Shipping Address Street      | 801 Scenic Hwy      |
      | Shipping Address City        | Haines City         |
      | Shipping Address State       | Florida             |
      | Shipping Address Postal Code | 33844               |
    And I save form
    Then I should see "This value should not be blank."
    And I should see "Please add at least one Line Item"
    And I should not see "Confirm Your Address"
