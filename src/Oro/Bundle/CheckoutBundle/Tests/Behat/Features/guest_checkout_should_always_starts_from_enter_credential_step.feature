@regression
@ticket-BB-16069
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroCheckoutBundle:GuestCheckout.yml
@fixture-OroUserBundle:user.yml
Feature: Guest Checkout should always starts from enter credential step
  As a Guest
  I want to see Enter Credential Step each time when I click on Create Order button

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | User  | second_session |
    And I enable configuration options:
      | oro_shopping_list.availability_for_guests |
      | oro_checkout.guest_checkout               |

  Scenario: Set payment term for Non-Authenticated Visitors group
    Given I proceed as the Admin
    And login as administrator
    And I go to Customers/ Customer Groups
    And I click Edit Non-Authenticated Visitors in grid
    And I fill form with:
      | Payment Term | net 10 |
    When I save form
    Then I should see "Customer group has been saved" flash message

  Scenario: Create order from guest shopping list
    Given I proceed as the User
    And I am on homepage
    And I type "SKU123" in "search"
    And I click "Search Button"
    And I click "400-Watt Bulb Work Light"
    And I click "Add to Shopping List"
    When I open shopping list widget
    And I click "View List"
    And I click on "Create Order"
    Then I should see "Sign In and Continue" button
    And I should see "Create An Account"
    And I should see "Continue as a Guest" button
    When I click "Continue as a Guest"
    Then I should see "Enter Billing Address"

  Scenario: Re-create order from guest shopping list after Billing Information step
    Given I open shopping list widget
    And I click "View List"
    And I click on "Create Order"
    Then I should see "Sign In and Continue" button
    And I should see "Create An Account"
    And I should see "Continue as a Guest" button
    When I click "Continue as a Guest"
    And I scroll to top
    Then I should see "Enter Billing Address"
    When I fill form with:
      | First Name      | Tester1         |
      | Last Name       | Testerson       |
      | Email           | tester@test.com |
      | Street          | Fifth avenue    |
      | City            | Berlin          |
      | Country         | Germany         |
      | State           | Berlin          |
      | Zip/Postal Code | 10115           |
    And click "Continue"
    And I scroll to top
    Then I should see "Enter Shipping Address"

  Scenario: Re-create order from guest shopping list after Shipping Information step
    Given I open shopping list widget
    And I click "View List"
    And I click on "Create Order"
    Then I should see "Sign In and Continue" button
    And I should see "Create An Account"
    And I should see "Continue as a Guest" button
    When I click "Continue as a Guest"
    And I scroll to top
    Then I should see "Enter Billing Address"
    When I fill form with:
      | First Name      | Tester1         |
      | Last Name       | Testerson       |
      | Email           | tester@test.com |
      | Street          | Fifth avenue    |
      | City            | Berlin          |
      | Country         | Germany         |
      | State           | Berlin          |
      | Zip/Postal Code | 10115           |
    And I click "Continue"
    And I scroll to top
    Then I should see "Enter Shipping Address"
    When I fill form with:
      | First Name      | Tester1         |
      | Last Name       | Testerson       |
      | Street          | Fifth avenue    |
      | City            | Berlin          |
      | Country         | Germany         |
      | State           | Berlin          |
      | Zip/Postal Code | 10115           |
    And click "Continue"
    And I scroll to top
    Then I should see "SELECT A SHIPPING METHOD"

  Scenario: Re-create order from guest shopping list after Shipping Method step
    Given I open shopping list widget
    And I click "View List"
    And I click on "Create Order"
    Then I should see "Sign In and Continue" button
    And I should see "Create An Account"
    And I should see "Continue as a Guest" button
    When I click "Continue as a Guest"
    And I scroll to top
    Then I should see "Enter Billing Address"
    When I fill form with:
      | First Name      | Tester1         |
      | Last Name       | Testerson       |
      | Email           | tester@test.com |
      | Street          | Fifth avenue    |
      | City            | Berlin          |
      | Country         | Germany         |
      | State           | Berlin          |
      | Zip/Postal Code | 10115           |
    And I click "Continue"
    And I scroll to top
    Then I should see "Enter Shipping Address"
    When I fill form with:
      | First Name      | Tester1         |
      | Last Name       | Testerson       |
      | Street          | Fifth avenue    |
      | City            | Berlin          |
      | Country         | Germany         |
      | State           | Berlin          |
      | Zip/Postal Code | 10115           |
    And I click "Continue"
    And I scroll to top
    Then I should see "SELECT A SHIPPING METHOD"
    When I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I scroll to top
    Then I should see "Select a Payment Method"

  Scenario: Re-create order from guest shopping list after Payment Method step
    Given I open shopping list widget
    And I click "View List"
    And I click on "Create Order"
    Then I should see "Sign In and Continue" button
    And I should see "Create An Account"
    And I should see "Continue as a Guest" button
    When I click "Continue as a Guest"
    And I scroll to top
    Then I should see "Enter Billing Address"
    When I fill form with:
      | First Name      | Tester1         |
      | Last Name       | Testerson       |
      | Email           | tester@test.com |
      | Street          | Fifth avenue    |
      | City            | Berlin          |
      | Country         | Germany         |
      | State           | Berlin          |
      | Zip/Postal Code | 10115           |
    And I click "Continue"
    And I scroll to top
    Then I should see "Enter Shipping Address"
    When I fill form with:
      | First Name      | Tester1         |
      | Last Name       | Testerson       |
      | Street          | Fifth avenue    |
      | City            | Berlin          |
      | Country         | Germany         |
      | State           | Berlin          |
      | Zip/Postal Code | 10115           |
    And I click "Continue"
    And I scroll to top
    Then I should see "SELECT A SHIPPING METHOD"
    When I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I scroll to top
    Then I should see "Select a Payment Method"
    And I check "Payment Terms" on the "Payment" checkout step and press Continue

  Scenario: Finish order from guest shopping list
    Given I open shopping list widget
    And I click "View List"
    And I click on "Create Order"
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
    And I click "Continue"
    And I fill form with:
      | First Name      | Tester1         |
      | Last Name       | Testerson       |
      | Street          | Fifth avenue    |
      | City            | Berlin          |
      | Country         | Germany         |
      | State           | Berlin          |
      | Zip/Postal Code | 10115           |
    And I click "Continue"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I uncheck "Save my data and create an account" on the checkout page
    When I click "Submit Order"
    Then I should see "Thank You For Your Purchase!"
