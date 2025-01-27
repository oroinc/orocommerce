@fixture-OroUPSBundle:AddressValidationUpsClient.yml
@fixture-OroSaleBundle:QuoteBackofficeDefaultFixture.yml
@fixture-OroCustomerBundle:Customer1TypedAddressesFixture.yml
@feature-BB-24101
@regression
@behat-test-env

Feature: Address Validation - Edit Quote - Save Suggested Customer Address Book Address as New Address
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
      | Customer Address | Edit:None |
    And I save form
    Then I should see "Role saved" flash message

  Scenario: Create a new customer user address from suggested customer address
    Given I go to Sales/ Quotes
    And I click edit PO1 in grid
    And I click "Shipping Address"
    When I fill "Quote Form" with:
      | Shipping Address | Amanda Cole, ORO, 801 Scenic Hwy, HAINES CITY FL US 33844 |
    Then I should see "Confirm Your Address - Address 1"
    When I click "Address Validation Result Form First Suggested Address Radio"
    Then I should see "Save Address"
    When I click "Address Book Aware Address Validation Result Save Address Checkbox"
    And I click on "Use Selected Address Button"
    Then "Quote Form" must contains values:
      | Shipping Address | Amanda Cole, ORO, 801 SCENIC HWY, HAINES CITY 1 FL US 33844-8562 |
    And I should see "Amanda Cole, ORO, 801 Scenic Hwy, HAINES CITY FL US 33844"
