@fixture-OroUPSBundle:AddressValidationUpsClient.yml
@fixture-OroSaleBundle:QuoteBackofficeDefaultFixture.yml
@fixture-OroOrderBundle:address_validation/ValidatedAddressBookAddresses.yml
@feature-BB-24101
@regression
@behat-test-env

Feature: Address Validation - Edit Quote - Dialog Not Displayed For Validated Customer User Address
  As an Administrator
  I should see not see Address Validation modal for already validated customer user address

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
      | Shipping Address | ORO, 805 Scenic Hwy, HAINES CITY FL US 33844 |
    Then I should not see "Confirm Your Address - Address 3"
