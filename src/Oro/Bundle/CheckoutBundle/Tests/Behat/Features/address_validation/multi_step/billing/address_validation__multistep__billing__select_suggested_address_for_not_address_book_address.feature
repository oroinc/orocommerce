@fixture-OroUPSBundle:AddressValidationUpsClient.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:address_validation/AddressValidationCheckoutFixture.yml
@behat-test-env
@feature-BB-24101
@regression

Feature: Address Validation - Multi-Step - Billing - Select Suggested Address For Not Address Book Address
  As an Buyer
  I should be able to validate and replace not address book address with the first suggested

  Scenario: Feature Background
    Given I login as administrator
    And I go to System/ Configuration
    And follow "Commerce/Shipping/Address Validation" on configuration sidebar
    When I fill "Address Validation Configuration Form" with:
      | Address Validation Service Use Default | false |
      | Address Validation Service             | UPS   |
    And I submit form
    Then I should see "Configuration saved" flash message
    When I fill "Address Validation Configuration Checkout Form" with:
      | Validate Billing Addresses During Checkout Use Default | false |
      | Validate Billing Addresses During Checkout             | true  |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Replace entered address with suggested
    Given I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I click "Create Order"
    And I click "Add Address"
    And I fill "New Address Popup Form" with:
      | First Name           | Name              |
      | Label                | Billing Address 1 |
      | Last Name            | Last name         |
      | Email                | tester@test.com   |
      | Street               | 801 Scenic Hwy    |
      | City                 | Haines City       |
      | Country              | United States     |
      | State                | Florida           |
      | Zip/Postal Code      | 33844             |
      | Ship to this Address | false             |
    And I click "Add Address" in modal window
    Then I should see "Confirm Your Address - Billing address 1"
    When I click "Address Validation Result Form First Suggested Address Radio Storefront"
    And I click on "Use Selected Address Button"
    And I click "Continue"
    Then I should see "Shipping Address"
    When I click "Back"
    And I click "Add Address"
    Then "New Address Popup Form" must contains values:
      | Street          | 801 SCENIC HWY |
      | City            | HAINES CITY 1  |
      | Zip/Postal Code | 33844-8562     |
      | State           | Florida        |
