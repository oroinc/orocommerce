@fixture-OroUPSBundle:AddressValidationUpsClient.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:address_validation/AddressValidationCheckoutFixture.yml
@behat-test-env
@feature-BB-24101
@regression

Feature: Address Validation - Multi-Step - Billing - Prevent Original Form Submit When Address Validation Dialog Canceled
  As an Buyer
  I should be on the same multistep checkout billing step when I cancel Address Validation dialog

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

  Scenario: Display Address Validation Dialog
    Given I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I click "Create Order"
    When I click "Continue"
    Then I should see "Confirm Your Address"
    When I close ui dialog
    Then I should see "Billing Address"
    When I click "Continue"
    Then I should see "Confirm Your Address"
    When I click "Edit Address" in modal window
    Then I should see "Billing Address"
