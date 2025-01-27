@fixture-OroUPSBundle:AddressValidationUpsClient.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:address_validation/AddressValidationCheckoutFixture.yml
@behat-test-env
@feature-BB-24101
@regression

Feature: Address Validation - Single-Page - Shipping - Prevent Original Form Submit When Address Validation Dialog Canceled
  As an Buyer
  I should not be redirected to thank you page after submit if I cancel Address Validation dialog

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

  Scenario: Display Address Validation Dialog
    Given I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I click "Create Order"
    And I click "Submit Order"
    Then I should see "Confirm Your Address"
    When I close ui dialog
    Then I should see "Checkout"
    And I click "Submit Order"
    Then I should see "Confirm Your Address"
    When I click "Edit Address" in modal window
    Then I should see "Checkout"
