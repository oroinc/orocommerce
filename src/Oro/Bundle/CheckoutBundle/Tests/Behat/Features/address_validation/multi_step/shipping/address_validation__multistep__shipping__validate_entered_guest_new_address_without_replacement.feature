@fixture-OroUPSBundle:AddressValidationUpsClient.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:address_validation/AddressValidationCheckoutFixture.yml
@behat-test-env
@feature-BB-24101
@regression

Feature: Address Validation - Multi-Step - Shipping - Validate Entered Guest New Address Without Replacement
  As a Buyer
  I should see that address validation dialog displayed and
  address details were not changed after address validation on checkout shipping address on guest checkout

  Scenario: Create different window session
    Given sessions active:
      | User | second_session |

  Scenario: Feature Background
    Given I login as administrator
    And I go to System/ Configuration
    And follow "Commerce/Shipping/Address Validation" on configuration sidebar
    When I fill "Address Validation Configuration Form" with:
      | Address Validation Service Use Default | false |
      | Address Validation Service             | UPS   |
    And I submit form
    Then I should see "Configuration saved" flash message
    When I fill "Address Validation Configuration Checkout Form" with:
      | Validate Billing Addresses During Checkout Use Default | false |
      | Validate Billing Addresses During Checkout             | false |
    And I submit form
    Then I should see "Configuration saved" flash message
    When I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And fill "Shopping List Configuration Form" with:
      | Enable Guest Shopping List Default | false |
      | Enable Guest Shopping List         | true  |
    And click "Save settings"
    Then I should see "Configuration saved" flash message
    When follow "Commerce/Sales/Checkout" on configuration sidebar
    And fill "Checkout Configuration Form" with:
      | Enable Guest Checkout Default | false |
      | Enable Guest Checkout         | true  |
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Validate shipping order address
    Given I proceed as the User
    And I am on homepage
    And type "SKU123" in "search"
    And I click "Search Button"
    And I click "400-Watt Bulb Work Light"
    And I click "Add to Shopping List"
    When I open shopping list widget
    And I click "Checkout"
    Then I should see "Proceed to Guest Checkout"
    When I click "Proceed to Guest Checkout"
    And I fill "Billing Information Form" with:
      | First Name      | Name              |
      | Label           | Billing Address 1 |
      | Last Name       | Last name         |
      | Email           | tester@test.com   |
      | Street          | 801 Scenic Hwy    |
      | City            | Haines City       |
      | Country         | United States     |
      | State           | Florida           |
      | Zip/Postal Code | 33844             |
    And I click "Continue"
    Then I should see "Shipping Address"
    When I fill "Shipping Information Form" with:
      | First Name      | Name               |
      | Label           | Shipping Address 1 |
      | Last Name       | Last name          |
      | Street          | 801 Scenic Hwy     |
      | City            | Haines City        |
      | Country         | United States      |
      | State           | Florida            |
      | Zip/Postal Code | 33844              |
    And I click "Continue"
    Then I should see "Confirm Your Address - Shipping Address 1"
    When I click on "Use Selected Address Button"
    #TODO email field reset here
    Then I should see "Shipping Method"
    When I click "Back"
    Then "Shipping Information Form" must contains values:
      | Street          | 801 Scenic Hwy |
      | City            | Haines City    |
      | Zip/Postal Code | 33844          |
      | State           | Florida        |

  Scenario: Submit already validated address
    When I reload the page
    And I click "Continue"
    Then I should see "Shipping Method"
