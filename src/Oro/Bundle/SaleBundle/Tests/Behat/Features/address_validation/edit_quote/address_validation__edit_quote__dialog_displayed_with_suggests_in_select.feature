@fixture-OroUPSBundle:AddressValidationUpsClient.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroSaleBundle:GuestQuoteWithAddressFixture.yml
@feature-BB-24101
@regression
@behat-test-env

Feature: Address Validation - Edit Quote - Dialog Displayed With Suggests In Select
  As an Administrator
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

  Scenario: Validate shipping quote address
    Given I go to Sales/ Quotes
    And I click edit PO1 in grid
    When I fill "Quote Form" with:
      | Shipping Address        | Enter other address       |
      | Shipping Address Street | 801 Scenic Hwy short-view |
    When I click "Submit"
    Then I should see "Confirm Your Address - Address 1"
    When I click "Address Validation Result Form First Suggested Address Radio"
    And I fill "Address Book Aware Address Validation Result Form" with:
      | Suggested Address Select | 801 SCENIC HWY Second HAINES CITY 2 FL US 33845-8562 |
    And I click on "Use Selected Address Button"
    And I click "Save" in modal window
    Then I should see "Quote #Quote_1 successfully updated" flash message
    When I click "Edit"
    Then "Quote Form" must contains values:
      | Shipping Address Street      | 801 SCENIC HWY Second |
      | Shipping Address City        | HAINES CITY 2         |
      | Shipping Address Postal Code | 33845-8562            |
      | Shipping Address State       | Florida               |
