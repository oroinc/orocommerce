@fixture-OroUPSBundle:AddressValidationUpsClient.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:address_validation/AddressValidationCheckoutFixture.yml
@behat-test-env
@feature-BB-24101
@regression

Feature: Address Validation - Single-Page - Billing - Guest Address Replaced With New Suggested Address
  As a Guest
  I should be able to replace the billing customer address on single step checkout
  with a new address

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
      | Validate Billing Addresses During Checkout Use Default  | false |
      | Validate Billing Addresses During Checkout              | true  |
      | Validate Shipping Addresses During Checkout Use Default | false |
      | Validate Shipping Addresses During Checkout             | false |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Enable Guest Shopping List setting
    Given I go to System/ Configuration
    And I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And uncheck "Use default" for "Enable Guest Shopping List" field
    And I check "Enable Guest Shopping List"
    When I save form
    Then I should see "Configuration saved" flash message
    And the "Enable Guest Shopping List" checkbox should be checked

  Scenario: Enable guest checkout setting
    Given I follow "Commerce/Sales/Checkout" on configuration sidebar
    And uncheck "Use default" for "Enable Guest Checkout" field
    And I check "Enable Guest Checkout"
    When I save form
    Then the "Enable Guest Checkout" checkbox should be checked

  Scenario: Set payment term for Non-Authenticated Visitors group
    Given I go to Customers/ Customer Groups
    When I click Edit Non-Authenticated Visitors in grid
    And I fill form with:
      | Payment Term | net 10 |
    When I save form
    Then I should see "Customer group has been saved" flash message

  Scenario: Create Shopping List as unauthorized user
    Given I am on homepage
    And I type "SKU123" in "search"
    And I click "Search Button"
    When I click "Add to Shopping List"
    Then I should see "Product has been added to" flash message and I close it
    When I open shopping list widget
    And I click "Open List"
    Then I should see "SKU123"

  Scenario: Replace entered address with suggested
    When I click "Create Order"
    And I click on "Single Checkout Page Add New Billing Address"
    And I fill "New Address Popup Form" with:
      | First Name      | Name                      |
      | Label           | Billing Address 1         |
      | Last Name       | Last name                 |
      | Email           | tester@test.com           |
      | Street          | 801 Scenic Hwy short-view |
      | City            | Haines City               |
      | Country         | United States             |
      | State           | Florida                   |
      | Zip/Postal Code | 33844                     |
    And I click "Add Address" in modal window
    Then I should see "Confirm Your Address - Billing address 1"
    When I fill "Address Book Aware Address Validation Result Form Storefront" with:
      | Suggested Address Select | 801 SCENIC HWY Second HAINES CITY 2 FL US 33845-8562 |
    And I click on "Use Selected Address Button"
    Then I should see "New address (Name Last name, 801 SCENIC HWY Second, HAINES CITY 2 FL US 33845-8562)" for "Select Single Page Checkout Billing Address" select

  Scenario: Ensure that entered email address is still there
    When I click on "Single Checkout Page Add New Billing Address"
    Then "New Address Popup Form" must contains values:
      | First Name      | Name                  |
      | Label           | Billing Address 1     |
      | Last Name       | Last name             |
      | Email           | tester@test.com       |
      | Street          | 801 SCENIC HWY Second |
      | City            | HAINES CITY 2         |
      | Country         | United States         |
      | State           | Florida               |
      | Zip/Postal Code | 33845-8562            |
