@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroWarehouseBundle:Checkout.yml
@fixture-OroUserBundle:user.yml
Feature: Guest Checkout
  In order to purchase goods that I want
  As a Guest customer
  I want to enter and complete checkout without having to register

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | User  | second_session |

  Scenario: Set payment term for Non-Authenticated Visitors group
    Given I proceed as the Admin
    And I login as administrator
    And I enable the existing warehouses
    And go to Customers/ Customer Groups
    And I click Edit Non-Authenticated Visitors in grid
    And I fill form with:
      | Payment Term | net 10 |
    When I save form
    Then I should see "Customer group has been saved" flash message

  Scenario: Disable guest checkout setting
    Given I proceed as the Admin
    And go to System/ Configuration
    And I click "Commerce" on configuration sidebar
    And I click "Sales" on configuration sidebar
    When I click "Checkout" on configuration sidebar
    Then the "Enable Guest Checkout" checkbox should not be checked

  Scenario: Create Shopping List as unauthorized user from product view page with disabled guest checkout
    Given I proceed as the User
    And I am on homepage
    And type "SKU123" in "search"
    And I click "Search Button"
    And I click "400-Watt Bulb Work Light"
    And I click "Add to Shopping list"
    And I should see "Product has been added to" flash message
    When I click "Shopping list"
    And I should see "400-Watt Bulb Work Light"
    Then I should not see following buttons:
      | Create Order |

  Scenario: Enable guest checkout setting
    Given I proceed as the Admin
    And uncheck Use Default for "Enable Guest Checkout" field
    And I check "Enable Guest Checkout"
    When I save form
    Then the "Enable Guest Checkout" checkbox should be checked

  Scenario: Change default guest checkout user owner
    Given I proceed as the Admin
    And uncheck Use Default for "Default guest checkout owner" field
    And I fill form with:
      | Default guest checkout owner | Charlie Sheen |
    When I save form
    Then I should see "Charlie Sheen"

  Scenario: Create order from guest shopping list
    Given I proceed as the User
    And I reload the page
    And I should see following buttons:
      | Create Order |
    And I press "Create Order"
    And I fill form with:
      | First Name      | Tester          |
      | Last Name       | Testerson       |
      | Email           | tester@test.com |
      | Street          | Fifth avenue    |
      | City            | Berlin          |
      | Country         | Germany         |
      | State           | Berlin          |
      | Zip/Postal Code | 10115           |
    And I should not see "Save address"
    And press "Continue"
    And I fill form with:
      | First Name      | Tester          |
      | Last Name       | Testerson       |
      | Street          | Fifth avenue    |
      | City            | Berlin          |
      | Country         | Germany         |
      | State           | Berlin          |
      | Zip/Postal Code | 10115           |
    And press "Continue"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    When I press "Submit Order"
    Then I should see "Thank You For Your Purchase!"

  Scenario: Checkout with shipping to billing adress
    Given I proceed as the User
    And I am on homepage
    And type "SKU123" in "search"
    And I click "Search Button"
    And I click "400-Watt Bulb Work Light"
    And I click "Add to Shopping list"
    And I click "Shopping list"
    And I press "Create Order"
    And I fill form with:
      | First Name           | Tester          |
      | Last Name            | Testerson       |
      | Email                | tester@test.com |
      | Street               | Fifth avenue    |
      | City                 | Berlin          |
      | Country              | Germany         |
      | State                | Berlin          |
      | Zip/Postal Code      | 10115           |
    And I click "Ship to This Address"
    And I press "Continue"
    And I press "Continue"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    When I press "Submit Order"
    Then I should see "Thank You For Your Purchase!"
#
  Scenario: Check guest orders on backend
    Given I proceed as the Admin
    When I go to Sales/ Orders
    And I should see "Tester Testerson" in grid with following data:
      | Customer      | Tester Testerson |
      | Customer User | Tester Testerson |
      | Owner         | Charlie Sheen    |
