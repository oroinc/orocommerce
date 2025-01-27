@fixture-OroSaleBundle:QuoteBackofficeDefaultFixture.yml
@fixture-OroUPSBundle:AddressValidationUpsClient.yml
@fixture-OroCustomerBundle:Customer1TypedAddressesFixture.yml
@feature-BB-24101
@regression
@behat-test-env

Feature: Address Validation - Edit Quote - Update Selected Customer Address In Address Book
  As an Administrator
  I should see that not validated customer address from the address book was updated

  Scenario: Feature Background
    Given I login as administrator
    And I go to System/ Configuration
    And follow "Commerce/Shipping/Address Validation" on configuration sidebar
    When I fill "Address Validation Configuration Form" with:
      | Address Validation Service Use Default | false |
      | Address Validation Service             | UPS   |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Update selected customer address with suggested
    Given I go to Sales/ Quotes
    And I click edit PO1 in grid
    When I fill "Quote Form" with:
      | Shipping Address | Amanda Cole, ORO, 801 Scenic Hwy, HAINES CITY FL US 33844 |
    Then I should see "Confirm Your Address - Address 1"
    When I click "Address Validation Result Form First Suggested Address Radio"
    Then I should see "Update Address"
    When I click "Address Book Aware Address Validation Result Update Address Checkbox"
    And I click on "Use Selected Address Button"
    Then "Quote Form" must contains values:
      | Shipping Address | Amanda Cole, ORO, 801 SCENIC HWY, HAINES CITY 1 FL US 33844-8562 |
    When I click on "Quote Form Shipping Address Select"
    Then I should not see "Amanda Cole, ORO, 801 Scenic Hwy, HAINES CITY FL US 33844"
