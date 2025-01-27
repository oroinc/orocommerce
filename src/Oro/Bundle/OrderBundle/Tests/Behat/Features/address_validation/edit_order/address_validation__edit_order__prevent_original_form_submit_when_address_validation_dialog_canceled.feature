@fixture-OroUPSBundle:AddressValidationUpsClient.yml
@fixture-OroOrderBundle:OrderAddressesFixture.yml
@feature-BB-24101
@regression
@behat-test-env

Feature: Address Validation - Edit Order - Prevent Original Form Submit When Address Validation Dialog Canceled
  As an Administrator
  I should not see continue Order form submit if I cancel Address Validation dialog

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
    Given I go to Sales/ Orders
    And I click edit order1 in grid
    And I click "Shipping Address"
    When I save form
    Then I should see "Confirm Your Address"
    When I close ui dialog
    And I save and close form
    Then I should see "Confirm Your Address"
    When I click "Edit Address" in modal window
    Then I should be on Order Update page
