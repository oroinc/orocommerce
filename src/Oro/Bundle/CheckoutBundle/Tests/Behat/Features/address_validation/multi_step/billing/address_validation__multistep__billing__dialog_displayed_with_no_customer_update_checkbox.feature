@fixture-OroUPSBundle:AddressValidationUpsClient.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:address_validation/AddressValidationCheckoutFixture.yml
@behat-test-env
@feature-BB-24101
@regression

Feature: Address Validation - Multi-Step - Billing - Dialog Displayed With No Customer Update Checkbox
  As an Buyer
  I should see that customer update checkbox are not displayed for suggested address
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
    When I fill "Address Validation Configuration Checkout Form" with:
      | Validate Billing Addresses During Checkout Use Default | false |
      | Validate Billing Addresses During Checkout             | true  |
    And I submit form
    Then I should see "Configuration saved" flash message
    When I go to Customers/ Customer User Roles
    And click edit "Buyer" in grid
    And select following permissions:
      | Customer Address | Edit:None   |
    And I save form
    Then I should see "Customer User Role has been saved" flash message

  Scenario: Select suggested address in address validation modal
    Given I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I click "Create Order"
    And I click on "Multistep Billing Address Select"
    And I select "ORO, customer avenue, 10115 Berlin, Germany" from "Billing Address"
    Then I should see "Confirm Your Address"
    When I click "Address Validation Result Form First Suggested Address Radio Storefront"
    Then I should not see "Update Address"
