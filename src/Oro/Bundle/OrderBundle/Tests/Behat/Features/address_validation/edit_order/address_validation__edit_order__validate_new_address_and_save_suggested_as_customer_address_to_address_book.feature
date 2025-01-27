@fixture-OroUPSBundle:AddressValidationUpsClient.yml
@fixture-OroOrderBundle:OrderAddressesFixture.yml
@feature-BB-24101
@regression
@behat-test-env

Feature: Address Validation - Edit Order - Validate New Address And Save Suggested As Customer Address To Address Book
  As an Administrator
  I should be able to validate other address, replace with suggested and save it to the address book as customer address when
  order customer user is not selected

  As an Administrator
  I should see that suggested address was added to address book when Save Address is checked for entered New Address

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
      | Customer User    |                     |
      | Shipping Address | Enter other address |
    And I save and close form
    Then I should see "Confirm Your Address"
    And I should be on Order Update page
    When I click "Address Validation Result Form First Suggested Address Radio"
    And I check "Save Address"
    And I click on "Use Selected Address Button"
    Then I should see "Review Shipping Cost"
    When I click "Save" in modal window
    Then I should see "Order has been saved" flash message
    When I click "Edit Order"
    And I click "Shipping Address"
    Then "Order Form" must contains values:
      | Shipping Address | Mr. John Edgar Doo M.D., Acme, 801 SCENIC HWY, HAINES CITY 1 FL US 33844-8562, 123-456 |
