@fixture-OroUPSBundle:AddressValidationUpsClient.yml
@fixture-OroOrderBundle:OrderAddressesFixture.yml
@feature-BB-24101
@regression
@behat-test-env

Feature: Address Validation - Edit Order - Save Suggested Customer User Address Book Address as New Address
  As an Administrator
  I should see that submit address validation form with checked "Save Address" checkbox
  change selected address in address book with a new created customer user address

  Scenario: Feature Background
    Given I login as administrator
    And I go to System/ Configuration
    And follow "Commerce/Shipping/Address Validation" on configuration sidebar
    When I fill "Address Validation Configuration Form" with:
      | Address Validation Service Use Default | false |
      | Address Validation Service             | UPS   |
    And I submit form
    Then I should see "Configuration saved" flash message
    When I go to System/ User Management/ Roles
    And click edit "Administrator" in grid
    And select following permissions:
      | Customer User Address | Edit:None |
    And I save form
    Then I should see "Role saved" flash message

  Scenario: Create new customer user address from suggested
    Given I go to Sales/ Orders
    And I click edit order1 in grid
    When I fill "Order Form" with:
      | Shipping Address | ORO, 801 Scenic Hwy, HAINES CITY FL US 33844 |
    Then I should see "Confirm Your Address - Address 1"
    When I click "Address Validation Result Form First Suggested Address Radio"
    Then I should see "Save Address"
    When I click "Address Book Aware Address Validation Result Save Address Checkbox"
    And I click on "Use Selected Address Button"
    Then "Order Form" must contains values:
      | Shipping Address | ORO, 801 SCENIC HWY, HAINES CITY 1 FL US 33844-8562 |
    And I should see "ORO, 801 Scenic Hwy, HAINES CITY FL US 33844"
