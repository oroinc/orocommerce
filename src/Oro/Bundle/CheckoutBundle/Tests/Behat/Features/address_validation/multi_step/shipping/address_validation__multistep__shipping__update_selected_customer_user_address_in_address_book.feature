@fixture-OroUPSBundle:AddressValidationUpsClient.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:address_validation/AddressValidationCheckoutFixture.yml
@feature-BB-24101
@regression
@behat-test-env

Feature: Address Validation - Multi-Step - Shipping - Update Selected Customer User Address In Address Book
  As an Buyer
  I should see that not validated customer user address from the address book was updated

  Scenario: Feature Background
    Given I login as administrator
    And I go to System/ Configuration
    And follow "Commerce/Shipping/Address Validation" on configuration sidebar
    When I fill "Address Validation Configuration Form" with:
      | Address Validation Service Use Default | false |
      | Address Validation Service             | UPS   |
    And I submit form
    Then I should see "Configuration saved" flash message
    When I go to Customers/ Customer User Roles
    And click edit "Buyer" in grid
    And select following permissions:
      | Customer User Address | Edit:Corporate (All Levels) |
    And I save form
    Then I should see "Customer User Role has been saved" flash message

  Scenario: Update selected customer user address with suggested
    Given I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I click "Create Order"
    And I click "Continue"
    And I click on "Multistep Shipping Address Select"
    And I select "ORO, Fifth avenue, 10115 Berlin, Germany" from "Shipping Address"
    And I click "Continue"
    Then I should see "Confirm Your Address"
    When I click "Address Validation Result Form First Suggested Address Radio Storefront"
    Then I should see "Update Address"
    When I click "Address Book Aware Address Validation Result Update Address Checkbox Storefront"
    And I click on "Use Selected Address Button"
    And I click on "Multistep Shipping Address Select"
    Then I should see "ORO, 801 SCENIC HWY, HAINES CITY 1 FL US 33844"
    And I should not see "ORO, Fifth avenue, 10115 Berlin, Germany"
