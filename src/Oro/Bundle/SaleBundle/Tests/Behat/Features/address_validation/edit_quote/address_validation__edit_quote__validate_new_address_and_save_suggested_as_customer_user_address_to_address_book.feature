@fixture-OroUPSBundle:AddressValidationUpsClient.yml
@fixture-OroSaleBundle:QuoteBackofficeDefaultFixture.yml
@fixture-OroCustomerBundle:CustomerUserAddressMarleneFixture.yml
@feature-BB-24101
@regression
@behat-test-env

Feature: Address Validation - Edit Quote - Validate New Address And Save Suggested As Customer User Address To Address Book
  As an Administrator
  I should be able to validate other address, replace with suggested and save it to the address book as customer user address when
  quote customer user is selected

  Scenario: Feature Background
    Given I login as administrator
    And I go to System/ Configuration
    And follow "Commerce/Shipping/Address Validation" on configuration sidebar
    When I fill "Address Validation Configuration Form" with:
      | Address Validation Service Use Default | false |
      | Address Validation Service             | UPS   |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Validate quote address
    Given I go to Sales/ Quotes
    And I click edit PO1 in grid
    When I fill "Quote Form" with:
      | Customer                     | Wholesaler B        |
      | Customer User                | Marlene Bradley     |
      | Shipping Address             | Enter other address |
      | Shipping Address First name  | Name                |
      | Shipping Address Last name   | Last name           |
      | Shipping Address Country     | United States       |
      | Shipping Address Street      | 801 Scenic Hwy      |
      | Shipping Address City        | Haines City         |
      | Shipping Address State       | Florida             |
      | Shipping Address Postal Code | 33844               |
    And I click "Submit"
    Then I should see "Confirm Your Address"
    When I click "Address Validation Result Form First Suggested Address Radio"
    And I check "Save Address"
    And I click on "Use Selected Address Button"
    Then I should see "Review Shipping Cost"
    When I click "Save" in modal window
    Then I should see "Quote #1 successfully updated" flash message
    When I click "Edit"
    And I click "Shipping Address"
    Then "Quote Form" must contains values:
      | Shipping Address | Name Last name, 801 SCENIC HWY, HAINES CITY 1 FL US 33844-8562 |
