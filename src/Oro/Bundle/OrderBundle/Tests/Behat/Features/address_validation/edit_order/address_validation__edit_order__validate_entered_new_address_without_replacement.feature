@fixture-OroUPSBundle:AddressValidationUpsClient.yml
@fixture-OroOrderBundle:OrderAddressesFixture.yml
@feature-BB-24101
@regression
@behat-test-env

Feature: Address Validation - Edit Order - Validate Entered New Address Without Replacement
  As an Administrator
  I should see that address validation dialog displayed and
  address details were not changed after address validation

  Scenario: Feature Background
    Given I login as administrator
    And I go to System/ Configuration
    And follow "Commerce/Shipping/Address Validation" on configuration sidebar
    When I fill "Address Validation Configuration Form" with:
      | Address Validation Service Use Default | false |
      | Address Validation Service             | UPS   |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Validate order address
    Given I go to Sales/ Orders
    And I click edit order1 in grid
    And I click "Shipping Address"
    When I fill "Order Form" with:
      | Shipping Address             | Enter other address |
      | Shipping Address First name  | Name                |
      | Shipping Address Last name   | Last name           |
      | Shipping Address Country     | United States       |
      | Shipping Address Street      | 801 Scenic Hwy      |
      | Shipping Address City        | Haines City         |
      | Shipping Address State       | Florida             |
      | Shipping Address Postal Code | 33844               |
    And I save and close form
    Then I should see "Confirm Your Address - Shipping Address 1"
    And I should be on Order Update page
    When I click on "Use Selected Address Button"
    Then I should see "Review Shipping Cost"
    When I click "Save" in modal window
    Then I should see "Order has been saved" flash message
    When I click "Edit Order"
    And I click "Shipping Address"
    Then "Order Form" must contains values:
      | Shipping Address Street      | 801 Scenic Hwy |
      | Shipping Address City        | Haines City    |
      | Shipping Address Postal Code | 33844          |
      | Shipping Address State       | Florida        |

  Scenario: Submit already validated address
    When I save form
    Then I should not see "Confirm Your Address - Address 1"
    And I should see "Order has been saved" flash message
