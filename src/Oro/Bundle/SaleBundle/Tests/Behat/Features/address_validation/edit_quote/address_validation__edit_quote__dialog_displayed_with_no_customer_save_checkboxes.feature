@fixture-OroSaleBundle:QuoteBackofficeDefaultFixture.yml
@fixture-OroUPSBundle:AddressValidationUpsClient.yml
@fixture-OroCustomerBundle:Customer1TypedAddressesFixture.yml
@feature-BB-24101
@regression
@behat-test-env

Feature: Address Validation - Edit Quote - Dialog Displayed With No Customer Save Checkboxes
  As an Administrator
  I should see that customer save checkboxes are not displayed for suggested address
  when user does not have enough permissions

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
      | Customer Address      | Edit:None   |
      | Customer User Address | Create:None |
    And I save form
    Then I should see "Role saved" flash message

  Scenario: Select suggested address in address validation modal
    Given I go to Sales/ Quotes
    And I click edit PO1 in grid
    When I fill "Quote Form" with:
      | Shipping Address | Amanda Cole, ORO, 801 Scenic Hwy, HAINES CITY FL US 33844 |
    Then I should see "Confirm Your Address - Address 1"
    When I click "Address Validation Result Form First Suggested Address Radio"
    Then I should not see "Update Address"
    And I should not see "Save address"
