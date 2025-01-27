@fixture-OroUPSBundle:AddressValidationUpsClient.yml
@fixture-OroOrderBundle:OrderAddressesFixture.yml
@feature-BB-24101
@regression
@behat-test-env

Feature: Address Validation - Edit Order - Dialog Displayed With Suggests In Select
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

  Scenario: Validate shipping order address
    Given I go to Sales/ Orders
    And I click edit order1 in grid
    When I fill "Order Form" with:
      | Shipping Address        | Enter other address       |
      | Shipping Address Street | 801 Scenic Hwy short-view |
    And I save form
    Then I should see "Confirm Your Address - Shipping address 1"
    When I click "Address Validation Result Form First Suggested Address Radio"
    And I fill "Address Book Aware Address Validation Result Form" with:
      | Suggested Address Select | 801 SCENIC HWY Second HAINES CITY 2 FL US 33845-8562 |
    And I click on "Use Selected Address Button"
    Then I should see "Review Shipping Cost"
    When I click "Save" in modal window
    Then I should see "Order has been saved" flash message
    And "Order Form" must contains values:
      | Shipping Address Street      | 801 SCENIC HWY Second |
      | Shipping Address City        | HAINES CITY 2         |
      | Shipping Address Postal Code | 33845-8562            |
      | Shipping Address State       | Florida               |
