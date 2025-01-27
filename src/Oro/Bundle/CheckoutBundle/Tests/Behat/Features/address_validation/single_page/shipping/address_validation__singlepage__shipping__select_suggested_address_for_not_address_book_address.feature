@fixture-OroUPSBundle:AddressValidationUpsClient.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:address_validation/AddressValidationCheckoutFixture.yml
@behat-test-env
@feature-BB-24101
@regression

Feature: Address Validation - Single-Page - Shipping - Select Suggested Address For Not Address Book Address
  As an Buyer
  I should be able to validate and replace not address book address with the first suggested

  Scenario: Feature Background
    Given I login as administrator
    And I activate "Single Page Checkout" workflow
    And I go to System/ Configuration
    And follow "Commerce/Shipping/Address Validation" on configuration sidebar
    When I fill "Address Validation Configuration Form" with:
      | Address Validation Service Use Default | false |
      | Address Validation Service             | UPS   |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Replace entered address with suggested
    Given I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I click "Create Order"
    And I click on "Single Checkout Page Add New Shipping Address"
    And I fill "New Address Popup Form" with:
      | First Name      | Name              |
      | Label           | Shipping Address 1 |
      | Last Name       | Last name         |
      | Street          | 801 Scenic Hwy    |
      | City            | Haines City       |
      | Country         | United States     |
      | State           | Florida           |
      | Zip/Postal Code | 33844             |
    And I click "Add Address" in modal window
    Then I should see "Confirm Your Address - Shipping address 1"
    When I click "Address Validation Result Form First Suggested Address Radio Storefront"
    And I click on "Use Selected Address Button"
    Then I should see "Checkout"
    When I click on "Shipping Address Select"
    Then I should see "New address (Name Last name, 801 SCENIC HWY, HAINES CITY 1 FL US 33844-8562)"
