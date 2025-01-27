@fixture-OroUPSBundle:AddressValidationUpsClient.yml
@fixture-OroSaleBundle:QuoteBackofficeDefaultFixture.yml
@feature-BB-24101
@regression
@behat-test-env

Feature: Address Validation - Edit Quote - Validate New Address And Save Entered As Customer Address To Address Book
  As an Administrator
  I should be able to validate other address and save it to the address book as customer address when
  quote customer user is not selected

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
    And I click "Shipping Address"
    When I fill "Quote Form" with:
      | Customer User                |                     |
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
    And I check "Save Address"
    And I click on "Use Selected Address Button"
    Then I should see "Review Shipping Cost"
    When I click "Save" in modal window
    Then I should see "Quote #1 successfully updated" flash message
    When I click "Edit"
    And I click "Shipping Address"
    Then "Quote Form" must contains values:
      | Shipping Address | Name Last name, 801 Scenic Hwy, HAINES CITY FL US 33844 |
