@community-edition-only
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroCheckoutBundle:GuestCheckout.yml
@fixture-OroUserBundle:user.yml
Feature: Guest Checkout
  In order to purchase goods that I want
  As a Guest customer
  I want to enter and complete checkout without having to register

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | User  | second_session |

  Scenario: Enable guest shopping list setting
    Given I proceed as the Admin
    And login as administrator
    And go to System/ Configuration
    When I follow "Commerce/Sales/Shopping List" on configuration sidebar
    Then the "Enable Guest Shopping List" checkbox should not be checked
    When uncheck "Use default" for "Enable Guest Shopping List" field
    And I check "Enable Guest Shopping List"
    When I save form
    Then I should see "Configuration saved" flash message
    And the "Enable Guest Shopping List" checkbox should be checked

  Scenario: Set payment term for Non-Authenticated Visitors group
    Given I proceed as the Admin
    And go to Customers/ Customer Groups
    And I click Edit Non-Authenticated Visitors in grid
    And I fill form with:
      | Payment Term | net 10 |
    When I save form
    Then I should see "Customer group has been saved" flash message

  Scenario: Disable guest checkout setting
    Given I proceed as the Admin
    And go to System/ Configuration
    When I follow "Commerce/Sales/Checkout" on configuration sidebar
    Then the "Enable Guest Checkout" checkbox should not be checked

  Scenario: Create Shopping List as unauthorized user from product view page with disabled guest checkout
    Given I proceed as the User
    And I am on homepage
    And type "SKU123" in "search"
    And I click "Search Button"
    And I click "400-Watt Bulb Work Light"
    And I click "Add to Shopping List"
    And I should see "Product has been added to" flash message and I close it
    When I click "Shopping List"
    And I should see "400-Watt Bulb Work Light"
    Then I should not see following buttons:
      | Create Order |

  Scenario: Enable guest checkout setting
    Given I proceed as the Admin
    And uncheck "Use default" for "Enable Guest Checkout" field
    And I check "Enable Guest Checkout"
    When I save form
    Then the "Enable Guest Checkout" checkbox should be checked

  Scenario: Change default guest checkout user owner
    Given I proceed as the Admin
    And uncheck "Use default" for "Default guest checkout owner" field
    And I fill form with:
      | Default guest checkout owner | Charlie Sheen |
    When I save form
    Then I should see "Charlie Sheen"

  Scenario: Create order from guest shopping list without guest registration
    Given I proceed as the User
    And I reload the page
    And I should see following buttons:
      | Create Order |
    And I click "Create Order"
    And I keep in mind current path
    When I hover on "Shopping Cart"
    And click "View Details"
    And I click "Create Order"
    Then path remained the same
    Then I should see "Sign In and Continue" button
    And I should see "Create An Account"
    And I should see "Continue as a Guest" button
    And I click "Continue as a Guest"
    And I fill form with:
      | First Name      | Tester1         |
      | Last Name       | Testerson       |
      | Email           | tester@test.com |
      | Street          | Fifth avenue    |
      | City            | Berlin          |
      | Country         | Germany         |
      | State           | Berlin          |
      | Zip/Postal Code | 10115           |
    And I should not see "Save address"
    And click "Continue"
    And I fill form with:
      | First Name      | Tester1         |
      | Last Name       | Testerson       |
      | Street          | Fifth avenue    |
      | City            | Berlin          |
      | Country         | Germany         |
      | State           | Berlin          |
      | Zip/Postal Code | 10115           |
    And click "Continue"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I uncheck "Save my data and create an account" on the checkout page
    When I click "Submit Order"
    Then I should see "Thank You For Your Purchase!"

  Scenario: Checkout with shipping to billing address without guest registration
    Given I proceed as the User
    And I am on homepage
    And type "SKU123" in "search"
    And I click "Search Button"
    And I click "400-Watt Bulb Work Light"
    And I click "Add to Shopping List"
    And I click "Shopping List"
    And I click "Create Order"
    When I click "Continue as a Guest"
    Then I should not see "Back"
    When I fill form with:
      | First Name           | Tester1         |
      | Last Name            | Testerson       |
      | Email                | tester@test.com |
      | Street               | Fifth avenue    |
      | City                 | Berlin          |
      | Country              | Germany         |
      | State                | Berlin          |
      | Zip/Postal Code      | 10115           |
    And I click "Ship to This Address"
    And I click "Continue"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I uncheck "Save my data and create an account" on the checkout page
    And I click "Submit Order"
    Then I should see "Thank You For Your Purchase!"

  Scenario: Check guest orders on management console
    Given I proceed as the Admin
    When I go to Sales/ Orders
    And I should see "Tester Testerson" in grid with following data:
      | Customer      | Tester1 Testerson |
      | Customer User | Tester1 Testerson |
      | Owner         | Charlie Sheen    |

  Scenario: Registration with the same email
    Given I proceed as the User
    And I am on homepage
    And click "Sign In"
    And click "Create An Account"
    And I fill "Registration Form" with:
      | Company Name     | OroCommerce                |
      | First Name       | TesterFromRegistrationFlow |
      | Last Name        | Testerson                  |
      | Email Address    | tester@test.com            |
      | Password         | TesterT1@test.com          |
      | Confirm Password | TesterT1@test.com          |
    When I click "Create An Account"
    Then I should see "Please check your email to complete registration" flash message

  Scenario: Activate customer user
    Given  I proceed as the Admin
    And go to Customers/ Customer Users
    And click view "TesterFromRegistrationFlow" in grid
    And click "Confirm"

  Scenario: Create quote for registereg user with the same emeil as quest
    Given go to Sales/ Quotes
    And click "Create Quote"
    And click "Customer Users List button"
    When click on TesterFromRegistrationFlow in grid
    Then should not see "Tester1"

  Scenario: Login as customer user
    Given I proceed as the User
    And fill form with:
      | Email Address | tester@test.com   |
      | Password      | TesterT1@test.com |
    When click "Sign In"
    Then should see "Signed in as: TesterFromRegistrationFlow"
