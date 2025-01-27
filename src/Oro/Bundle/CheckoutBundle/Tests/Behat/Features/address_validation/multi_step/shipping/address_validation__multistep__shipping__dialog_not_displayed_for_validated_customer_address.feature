@fixture-OroUPSBundle:AddressValidationUpsClient.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:address_validation/AddressValidationCheckoutFixture.yml
@fixture-OroCheckoutBundle:address_validation/ValidatedAddressBookAddresses.yml
@behat-test-env
@feature-BB-24101
@regression

Feature: Address Validation - Multi-Step - Shipping - Dialog Not Displayed For Validated Customer Address
  As an Buyer
  I should see not see Address Validation modal for already validated customer address

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
    Given I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I click "Create Order"
    And I click "Continue"
    And I click on "Multistep Shipping Address Select"
    And I select "ORO, 4444 Hard Road, YORK NY US 12103" from "Shipping Address"
    And I click "Continue"
    Then I should see "Shipping Method"
