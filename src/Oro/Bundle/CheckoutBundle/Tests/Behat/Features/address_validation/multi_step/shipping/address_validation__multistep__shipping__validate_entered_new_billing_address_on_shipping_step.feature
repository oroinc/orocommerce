@fixture-OroUPSBundle:AddressValidationUpsClient.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:address_validation/AddressValidationCheckoutFixture.yml
@behat-test-env
@feature-BB-24101
@regression

Feature: Address Validation - Multi-Step - Shipping - Validate Entered New Billing Address On Shipping Step
  As a Buyer
  I should see that address validation dialog displayed on shipping step
  when use billing address checkbox is applied

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
      | First Name           | Name           |
      | Label                | Address 1      |
      | Last Name            | Last name      |
      | Street               | 801 Scenic Hwy |
      | City                 | Haines City    |
      | Country              | United States  |
      | State                | Florida        |
      | Zip/Postal Code      | 33844          |
      | Ship to this Address | false          |
    And I click "Add Address" in modal window
    And I click "Continue"
    Then I should see "Shipping Address"
    When I check "Use billing address"
    And I click "Continue"
    Then I should see "Confirm Your Address"
    When I click "Address Validation Result Form First Suggested Address Radio Storefront"
    And I click on "Use Selected Address Button"
    Then I should see "Shipping Address"
    And I should see "New address (Name Last name, 801 SCENIC HWY, HAINES CITY 1 FL US 33844-8562)"
    And the "Use billing address" checkbox should be unchecked
