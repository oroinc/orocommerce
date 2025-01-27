@fixture-OroUPSBundle:AddressValidationUpsClient.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:address_validation/AddressValidationCheckoutFixture.yml
@behat-test-env
@feature-BB-24101
@regression

Feature: Address Validation - Multi-Step - Billing - Validate Entered New Shipping Address On Billing Step
  As a Buyer
  I should see that address validation dialog displayed on billing step
  when ship to this address checkbox is applied

  Scenario: Feature Background
    Given I login as administrator
    And I go to System/ Configuration
    And follow "Commerce/Shipping/Address Validation" on configuration sidebar
    When I fill "Address Validation Configuration Form" with:
      | Address Validation Service Use Default | false |
      | Address Validation Service             | UPS   |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Validate shipping order address
    Given I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I click "Create Order"
    And I click "Add Address"
    And I fill "New Address Popup Form" with:
      | First Name      | Name           |
      | Label           | Address 1      |
      | Last Name       | Last name      |
      | Street          | 801 Scenic Hwy |
      | City            | Haines City    |
      | Country         | United States  |
      | State           | Florida        |
      | Zip/Postal Code | 33844          |
    And I click "Add Address" in modal window
    Then I should see "Confirm Your Address - Address 1"
    When I click "Address Validation Result Form First Suggested Address Radio Storefront"
    And I click on "Use Selected Address Button"
    Then I should see "Billing Address"
    When I click "Add Address"
    Then "New Address Popup Form" must contains values:
      | Street          | 801 SCENIC HWY |
      | City            | HAINES CITY 1  |
      | Zip/Postal Code | 33844-8562     |
      | State           | Florida        |
    And the "Ship to this address" checkbox should be checked
