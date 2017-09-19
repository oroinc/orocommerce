@regression
@ticket-BB-9852
@fixture-OroProductBundle:Products_quick_order_form.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroUserBundle:user.yml

Feature: Guest checkout with option to register
  In order to spend less time typing my credentials
  As a Guest customer
  I want an option to register and login within guest checkout

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Guest | second_session |

  Scenario: Enable guest shopping list and guest checkout settings
    Given I proceed as the Admin
    And I login as administrator
    And go to System/ Configuration
    And follow "Commerce/Sales/Shopping List" on configuration sidebar
    And fill "Shopping List Configuration Form" with:
      |Enable Guest Shopping List Default|false|
      |Enable Guest Shopping List        |true |
    And click "Save settings"
    And follow "Commerce/Sales/Checkout" on configuration sidebar
    And I should see "Allow Checkout Without Email Confirmation"
    And fill "Checkout Configuration Form" with:
      |Enable Guest Checkout Default|false|
      |Enable Guest Checkout        |true |
    And click "Save settings"
    And I enable the existing warehouses
    And go to Customers/ Customer Groups
    And I click Edit Non-Authenticated Visitors in grid
    And I fill form with:
      | Payment Term | net 10 |
    And I save form

  Scenario: Disable customer user registration confirmation on management console
    Given I proceed as the Admin
    And go to System/ Configuration
    And follow "Commerce/Customer/Customer Users" on configuration sidebar
    And fill "Customer Users Registration Form" with:
      |Confirmation Required Default|false|
      |Confirmation Required        |false|
    And click "Save settings"
    And follow "Commerce/Sales/Checkout" on configuration sidebar
    And I should not see "Allow Checkout Without Email Confirmation"

  Scenario: Register new customer during checkout with autologin, without email confirmation
    Given I proceed as the Guest
    And I am on the homepage
    When type "PSKU1" in "search"
    And I click "Search Button"
    Then I should see "Product1"
    When I click "Add to Shopping List"
    Then I should see "Product has been added to " flash message
    And I open page with shopping list Shopping List
    And press "Create Order"
    And I click "Create An Account"
    And I fill "Registration Form" with:
      | Company          | Company                |
      | First Name       | Charlie                |
      | Last Name        | Sheen                  |
      | Email Address    | Charlie001@example.com |
      | Password         | Charlie001@example.com |
      | Confirm Password | Charlie001@example.com |
    When I click "Create an Account and Continue"
    Then I should see "Signed in as: Charlie Sheen"
    Then I should see "Registration successful" flash message
    And I should see "Billing Information"
    And I open page with shopping list Shopping List
    And click "Delete"
    And I click "Yes, Delete"
    And I should see "Shopping List deleted" flash message
    And I click "Sign Out"

  Scenario: Create order from guest shopping list using late registration with autologin
    Given I proceed as the Guest
    And I am on the homepage
    When type "PSKU1" in "search"
    And I click "Search Button"
    Then I should see "Product1"
    When I click "Add to Shopping List"
    Then I should see "Product has been added to " flash message
    And I open page with shopping list Shopping List
    When I press "Create Order"
    Then I should see "Sign In and Continue" button
    Then I should see "Continue as a Guest" button
    And I click "Continue as a Guest"
    And I fill form with:
      |Email           |rob@test.com    |
      |First Name      |Rob             |
      |Last Name       |Halford         |
      |Organization    |TestComapany    |
      |Street          |Fifth avenue    |
      |City            |Berlin          |
      |Country         |Germany         |
      |State           |Berlin          |
      |Zip/Postal Code |10115           |
    And press "Continue"
    And I fill form with:
      |First Name      |Tester          |
      |Last Name       |Testerson       |
      |Street          |Fifth avenue    |
      |City            |Berlin          |
      |Country         |Germany         |
      |State           |Berlin          |
      |Zip/Postal Code |10115           |
    And click "Continue"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I check "Save my data and create an account" on the checkout page
    And I type "rob1@test.com" in "Email Address"
    And I type "Rob1@test.com" in "Password"
    And I type "Rob1@test1.com" in "Confirm Password"
    When I press "Submit Order"
    Then I should see "The password fields must match."
    And I type "Rob1@test.com" in "Confirm Password"
    When I press "Submit Order"
    Then I should see "Thank You For Your Purchase!"
    And I should see "Signed in as: Rob Halford"
    And I should see "You will receive a confirmation email with your order details."
    And I click "Sign Out"

  Scenario: Create account during checkout with Email confirmation with same emails with autologin
    Given I proceed as the Guest
    And I am on the homepage
    When type "PSKU1" in "search"
    And I click "Search Button"
    Then I should see "Product1"
    When I click "Add to Shopping List"
    Then I should see "Product has been added to " flash message
    And I open page with shopping list Shopping List
    And press "Create Order"
    And I click "Continue as a Guest"
    And I fill form with:
      |Email           |Andy001@example.com |
      |First Name      |Andy                |
      |Last Name       |Derrick             |
      |Organization    |TestComapany        |
      |Street          |Fifth avenue        |
      |City            |Berlin              |
      |Country         |Germany             |
      |State           |Berlin              |
      |Zip/Postal Code |10115               |
    And I click "Ship to This Address"
    And press "Continue"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I check "Save my data and create an account" on the checkout page
    And I type "Andy001@example.com" in "Email Address"
    And I type "Andy001@example.com" in "Password"
    And I type "Andy001@example.com" in "Confirm Password"
    When I press "Submit Order"
    Then I should see "Thank You For Your Purchase!"
    And I should see "Signed in as: Andy Derrick"
    And I should see "You will receive a confirmation email with your order details."
    And I click "Sign Out"

  Scenario: Check created customers on management console
    Given I proceed as the Admin
    When go to Customers/ Customer Users
    Then I should see following grid:
      |Customer         |First Name|Last Name  |Email Address           |
      |Company A        |Amanda    |Cole       |AmandaRCole@example.org |
      |Company          |Charlie   |Sheen      |Charlie001@example.com  |
      |Rob Halford      |Rob       |Halford    |rob@test.com            |
      |Rob Halford      |Rob       |Halford    |rob1@test.com           |
      |Andy Derrick     |Andy      |Derrick    |Andy001@example.com     |
      |Andy Derrick     |Andy      |Derrick    |Andy001@example.com     |

  Scenario: Reset password during checkout
    Given I proceed as the Guest
    And I am on the homepage
    When type "PSKU1" in "search"
    And I click "Search Button"
    Then I should see "Product1"
    When I click "Add to Shopping List"
    Then I should see "Product has been added to " flash message
    And I open page with shopping list Shopping List
    And I press "Create Order"
    And I click "Forgot Your Password?"
    And I type "AmandaRCole@example.org" in "Email Address"
    When I click "Request"
    Then I should see "It contains a link you must click to reset your password."

  Scenario: Sign in to existing account during checkout with validation
    Given I proceed as the Guest
    And I open page with shopping list Shopping List
    When I press "Create Order"
    And I type "AmandaRCole@example.org" in "Email Address"
    And I type "AmandaRCole1@example.org" in "Password"
    When I click "Sign In and Continue"
    Then I should see "Bad credentials."
    And I type "AmandaRCole@example.org" in "Password"
    And I click "Sign In and Continue"
    Then I should see "Signed in as: Amanda Cole"
    And I should see "Billing Information"
    And I open page with shopping list Shopping List
    And click "Delete"
    And I click "Yes, Delete"
    And I should see "Shopping List deleted" flash message
    And I click "Sign Out"

  Scenario: Enable customer user registration on management console
    Given I proceed as the Admin
    And go to System/ Configuration
    And follow "Commerce/Customer/Customer Users" on configuration sidebar
    And fill "Customer Users Registration Form" with:
      |Confirmation Required Default|true |
      |Confirmation Required        |true |
    And click "Save settings"
    And follow "Commerce/Sales/Checkout" on configuration sidebar
    And I should see "Allow Checkout Without Email Confirmation"

  Scenario: Create new customer with disabled "Allow Checkout Without Email Confirmation" option
    Given I proceed as the Guest
    And I am on the homepage
    When type "PSKU1" in "search"
    And I click "Search Button"
    Then I should see "Product1"
    When I click "Add to Shopping List"
    Then I should see "Product has been added to " flash message
    And I open page with shopping list Shopping List
    And press "Create Order"
    And I click "Create An Account"
    And I fill "Registration Form" with:
      | Company          | Company                |
      | First Name       | Sue                    |
      | Last Name        | Jackson                |
      | Email Address    | Sue001@example.com     |
      | Password         | Sue001@example.com     |
      | Confirm Password | Sue001@example.com     |
    When I click "Create an Account and Continue"
    Then I should not see "Signed in as: Sue Jackson"
    And I should see "Billing Information"
    And I fill form with:
      |Email           |rob@test.com    |
      |First Name      |Rob             |
      |Last Name       |Halford         |
      |Organization    |TestComapany    |
      |Street          |Fifth avenue    |
      |City            |Berlin          |
      |Country         |Germany         |
      |State           |Berlin          |
      |Zip/Postal Code |10115           |
    And press "Continue"
    Then I should see "Please confirm your email before continue checkout" flash message

  Scenario: Check created customer on management console
    Given I proceed as the Admin
    When go to Customers/ Customer Users
    And I should see "Sue001@example.com" in grid with following data:
      |Customer      |Company             |
      |Email Address |Sue001@example.com  |
      |First Name    |Sue                 |
      |Last Name     |Jackson             |

  Scenario: Allow Checkout Without Email Confirmation on management console
    Given I proceed as the Admin
    And go to System/ Configuration
    And follow "Commerce/Sales/Checkout" on configuration sidebar
    And I should see "Allow Checkout Without Email Confirmation"
    And fill "Checkout Configuration Form" with:
      |Allow Checkout Without Email Confirmation Default|false|
      |Allow Checkout Without Email Confirmation        |true |
    And click "Save settings"

  Scenario: Create new customer with enabled "Allow Checkout Without Email Confirmation" option
    Given I proceed as the Guest
    And I open page with shopping list Shopping List
    And press "Create Order"
    And I click "Create An Account"
    And I fill "Registration Form" with:
      | Company          | Company                |
      | First Name       | Andy                   |
      | Last Name        | Johnson                |
      | Email Address    | Andy001@example.com    |
      | Password         | Andy001@example.com    |
      | Confirm Password | Andy001@example.com    |
    When I click "Create an Account and Continue"
    Then I should see "This email is already used."
    And I fill "Registration Form" with:
      | Email Address    | Andy002@example.com    |
      | Password         | Andy002@example.com    |
      | Confirm Password | Andy002@example.com    |
    When I click "Create an Account and Continue"
    And I should see "Please check your email to complete registration" flash message
    And I should not see "Signed in as: Andy Johnson"
    And I should see "Billing Information"
    And I fill form with:
      |Email           |rob@test.com    |
      |First Name      |Rob             |
      |Last Name       |Halford         |
      |Organization    |TestComapany    |
      |Street          |Fifth avenue    |
      |City            |Berlin          |
      |Country         |Germany         |
      |State           |Berlin          |
      |Zip/Postal Code |10115           |
    And I click "Ship to This Address"
    And press "Continue"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    Then I should not see "Save my data and create an account"
    When I press "Submit Order"
    Then I should see "Thank You For Your Purchase!"
    And I should see "You will receive a confirmation email with your order details."

  Scenario: Check on management console that order belongs to registered user
    Given I proceed as the Admin
    When I go to Sales/ Orders
    And I should see "Andy Johnson" in grid with following data:
      | Customer      | Company          |
      | Customer User | Andy Johnson     |
      | Owner         | John Doe         |

  Scenario: Disable customer user registration on checkout
    Given I proceed as the Admin
    And go to System/ Configuration
    And follow "Commerce/Sales/Checkout" on configuration sidebar
    And fill "Checkout Configuration Form" with:
      |Allow Registration Default |false|
      |Allow Registration         |false|
    And click "Save settings"

  Scenario: Try to create customer with disabled "Allow Registration" option
    Given I proceed as the Guest
    And I am on the homepage
    When type "PSKU1" in "search"
    And I click "Search Button"
    Then I should see "Product1"
    When I click "Add to Shopping List"
    Then I should see "Product has been added to " flash message
    And I open page with shopping list Shopping List
    When I press "Create Order"
    Then I should not see "Create An Account"
