@fixture-OroUPSBundle:AddressValidationUpsClient.yml
@fixture-OroOrderBundle:OrderAddressesFixture.yml
@feature-BB-24101
@regression
@behat-test-env

Feature: Address Validation - Edit Order - Validate Few Addresses
  As an Administrator
  I should see two Dialogs because two addresses should be validated

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

  Scenario: Display one by one Address Validation Dialogs
    Given I go to Sales/ Orders
    And I click edit order1 in grid
    When I save form
    Then I should see "Confirm Your Address - Shipping Address 1"
    When I click on "Use Selected Address Button"
    Then I should see "Confirm Your Address - Billing Address 1"
    When I click on "Use Selected Address Button"
    Then I should see "Review Shipping Cost"
    When I click "Save" in modal window
    Then I should see "Order has been saved" flash message
