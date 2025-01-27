@fixture-OroUPSBundle:AddressValidationUpsClient.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:address_validation/AddressValidationCheckoutFixture.yml
@behat-test-env
@feature-BB-24101
@regression

Feature: Address Validation - Single-Page - Billing - Validate Billing Address With Use Shipping Address
  As a Buyer
  I should see that address validation dialog displayed for billing address when it is used as shipping

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
    When I fill "Address Validation Configuration Checkout Form" with:
      | Validate Billing Addresses During Checkout Use Default | false |
      | Validate Billing Addresses During Checkout             | false |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Validate shipping order address
    Given I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I click "Create Order"
    And I check "Use billing address"
    And I click "Submit Order"
    Then I should see "Confirm Your Address - Primary address"
    When I click "Address Validation Result Form First Suggested Address Radio Storefront"
    And I click on "Use Selected Address Button"
    Then I should see "Thank You For Your Purchase!"
