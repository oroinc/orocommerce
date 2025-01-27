@fixture-OroUPSBundle:AddressValidationUpsClient.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroSaleBundle:GuestQuoteWithAddressFixture.yml
@feature-BB-24101
@regression
@behat-test-env

Feature: Address Validation - Edit Quote - Prevent Original Form Submit When Address Validation Dialog Canceled
  As an Administrator
  I should not see continue Quote form submit if I cancel Address Validation dialog

  Scenario: Feature Background
    Given I login as administrator
    And I go to System/ Configuration
    And follow "Commerce/Shipping/Address Validation" on configuration sidebar
    When I fill "Address Validation Configuration Form" with:
      | Address Validation Service Use Default | false |
      | Address Validation Service             | UPS   |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Display Address Validation Dialog
    Given I go to Sales/ Quotes
    And I click edit PO123 in grid
    And I click "Shipping Address"
    When I click "Submit"
    Then I should see "Confirm Your Address"
    When I close ui dialog
    And I click "Submit"
    Then I should see "Confirm Your Address"
    When I click "Edit Address" in modal window
    Then I should not see "Quote #Quote_1 successfully updated" flash message
