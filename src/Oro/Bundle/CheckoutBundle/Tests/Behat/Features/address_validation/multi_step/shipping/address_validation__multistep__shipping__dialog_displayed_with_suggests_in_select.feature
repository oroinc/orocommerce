@fixture-OroUPSBundle:AddressValidationUpsClient.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:address_validation/AddressValidationCheckoutFixture.yml
@behat-test-env
@feature-BB-24101
@regression

Feature: Address Validation - Multi-Step - Shipping - Dialog Displayed With Suggests In Select
  As an Buyer
  I should see that address validation dialog displayed
  with entered address and select that contains all address suggests

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
    And I click "Continue"
    And I click "Add Address"
    And I fill "New Address Popup Form" with:
      | First Name      | Name                      |
      | Label           | Shipping Address 1         |
      | Last Name       | Last name                 |
      | Street          | 801 Scenic Hwy short-view |
      | City            | Haines City               |
      | Country         | United States             |
      | State           | Florida                   |
      | Zip/Postal Code | 33844                     |
    And I click "Add Address" in modal window
    Then I should see "Confirm Your Address - Shipping address 1"
    When I click "Address Validation Result Form First Suggested Address Radio Storefront"
    And I fill "Address Book Aware Address Validation Result Form Storefront" with:
      | Suggested Address Select | 801 SCENIC HWY Second HAINES CITY 2 FL US 33845-8562 |
    And I click on "Use Selected Address Button"
    And I click "Add Address"
    Then "New Address Popup Form" must contains values:
      | Street      | 801 SCENIC HWY Second |
      | City        | HAINES CITY 2         |
      | Postal Code | 33845-8562            |
      | State       | Florida               |
