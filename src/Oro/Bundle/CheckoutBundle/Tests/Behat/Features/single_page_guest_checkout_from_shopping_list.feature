@community-edition-only
@ticket-BB-11263
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroCheckoutBundle:GuestCheckout.yml
@fixture-OroCheckoutBundle:InventoryLevel.yml
@fixture-OroUserBundle:user.yml

Feature: Single Page Guest Checkout From Shopping List
  In order to complete the checkout process without going back and forth to various pages
  As a Guest customer
  I want to see all checkout information and be able to complete checkout on one page from "Shopping List" without having to register

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | User  | second_session |

  Scenario: Enable guest shopping list setting
    Given I proceed as the Admin
    And login as administrator
    And go to System/ Configuration
    And I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And fill "Shopping List Configuration Form" with:
      | Enable Guest Shopping List Default | false |
      | Enable Guest Shopping List         | true  |
    And click "Save settings"
    Then the "Enable Guest Shopping List" checkbox should be checked

  Scenario: Enable guest checkout setting
    Given I follow "Commerce/Sales/Checkout" on configuration sidebar
    And fill "Checkout Configuration Form" with:
      | Enable Guest Checkout Default | false |
      | Enable Guest Checkout         | true  |
    And click "Save settings"
    Then the "Enable Guest Checkout" checkbox should be checked

  Scenario: Change default guest checkout user owner
    Given uncheck "Use default" for "Default guest checkout owner" field
    And I fill form with:
      | Default guest checkout owner | Charlie Sheen |
    When I save form
    Then I should see "Charlie Sheen"

  Scenario: Set payment term for Non-Authenticated Visitors group
    Given go to Customers/ Customer Groups
    And I click Edit Non-Authenticated Visitors in grid
    And I fill form with:
      | Payment Term | net 10 |
    When I save form
    Then I should see "Customer group has been saved" flash message

  Scenario: Enable Single Page Checkout Workflow
    Given go to System/ Workflows
    When I click "Activate" on row "Single Page Checkout" in grid
    And I click "Activate" in modal window
    Then I should see "Workflow activated" flash message

  Scenario: Create order from shopping list
    Given I proceed as the User
    And I am on homepage
    And type "SKU123" in "search"
    And I click "Search Button"
    And I click "400-Watt Bulb Work Light"
    And I click "Add to Shopping List"
    And I follow "Shopping List" link within flash message "Product has been added to \"Shopping list\""
    And I click "Create Order"

  Scenario: Check save state works when "Late registration form" in not filled
    Given the "Save my data and create an account" checkbox should be checked
    And I check "Use billing address" on the checkout page
    And I reload the page
    Then the "Use billing address" checkbox should be checked

  Scenario: "Save my data and create an account" checkbox should be possible to save in unchecked state
    Given I uncheck "Save my data and create an account" on the checkout page
    And I reload the page
    Then the "Save my data and create an account" checkbox should be unchecked

  Scenario: Check "Late registration form" entered email saved on save state
    Given I check "Save my data and create an account" on the checkout page
    And I type "emailShouldBesaved@test.com" in "Email Address"
    And I type "PasswordShouldNot1" in "Password"
    And I type "PasswordShouldNot1" in "Confirm Password"
    And I reload the page
    Then the "Email Address" field should contain "emailShouldBesaved@test.com"

  Scenario: Create order from Shopping list as unauthorized user without guest registration
    And I uncheck "Use billing address" on the checkout page
    And I click on "Billing Address Select"
    And I click on "New Address Option"
    And I fill "New Address Popup Form" with:
      | First Name      | Tester          |
      | Last Name       | Testerson       |
      | Email           | tester@test.com |
      | Street          | Fifth avenue    |
      | City            | Berlin          |
      | Country         | Germany         |
      | State           | Berlin          |
      | Zip/Postal Code | 10115           |
    And I click "Continue"
    And I scroll to top
    And I click on "Shipping Address Select"
    And I click on "New Address Option"
    And I fill "New Address Popup Form" with:
      | First Name      | Tester       |
      | Last Name       | Testerson    |
      | Street          | Fifth avenue |
      | City            | Berlin       |
      | Country         | Germany      |
      | State           | Berlin       |
      | Zip/Postal Code | 10115        |
    And I click "Continue"
    And I check "Flat Rate" on the checkout page
    And I check "Payment Terms" on the checkout page
    And I wait "Submit Order" button
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Start order and fill shipping and billing addresses
    Given I proceed as the User
    And I am on homepage
    And type "SKU123" in "search"
    And I click "Search Button"
    And I click "400-Watt Bulb Work Light"
    And I click "Add to Shopping List"
    And I follow "Shopping List" link within flash message "Product has been added to \"Shopping list\""
    And I click "Create Order"
    And I click on "Billing Address Select"
    And I click on "New Address Option"
    And I fill "New Address Popup Form" with:
      | First Name      | July          |
      | Last Name       | Robertson     |
      | Email           | rob1@test.com |
      | Street          | Fifth avenue  |
      | City            | Berlin        |
      | Country         | Germany       |
      | State           | Berlin        |
      | Zip/Postal Code | 10115         |
    And I click "Continue"
    And I scroll to top
    And I click on "Shipping Address Select"
    And I click on "New Address Option"
    And I fill "New Address Popup Form" with:
      | First Name      | July         |
      | Last Name       | Robertson    |
      | Street          | Fifth avenue |
      | City            | Berlin       |
      | Country         | Germany      |
      | State           | Berlin       |
      | Zip/Postal Code | 10115        |
    And I click "Continue"

  Scenario: Check Late registration form validation errors
    And I type "not@valid" in "Email Address"
    And I type "1111" in "Password"
    And I type "1" in "Confirm Password"
    And I wait "Submit Order" button
    And I click "Submit Order"
    Then I should see validation errors:
      | Email Address    | This value is not a valid email address.                                                                  |
      | Password         | The password must be at least 8 characters long and include a lower case letter and an upper case letter  |
      | Confirm Password | The password fields must match.                                                                           |

  Scenario: Finish creating order from shopping List as unauthorized user from product view page with guest registration
    And I type "rob1@test.com" in "Email Address"
    And I type "Rob1@test.com" in "Password"
    And I type "Rob1@test.com" in "Confirm Password"
    And I check "Flat Rate" on the checkout page
    And I check "Payment Terms" on the checkout page
    And I wait "Submit Order" button
    And I click "Submit Order"
    Then I should see "Please check your email to complete registration" flash message
    And I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Check guest orders on management console
    Given I proceed as the Admin
    When I go to Sales/ Orders
    Then I should see following grid:
      | Order Number | Customer         | Customer User    | Owner         |
      | 1            | Tester Testerson | Tester Testerson | Charlie Sheen |
      | 2            | July Robertson   | July Robertson   | Charlie Sheen |

  Scenario: Check guest customers on management console
    Given I proceed as the Admin
    When go to Customers/ Customer Users
    Then I should see following grid:
      | Customer         | First Name | Last Name | Email Address           | Enabled | Confirmed | Guest |
      | Company A        | Amanda     | Cole      | AmandaRCole@example.org | Yes     | Yes       | No    |
      | Tester Testerson | Tester     | Testerson | tester@test.com         | No      | No        | Yes   |
      | July Robertson   | July       | Robertson | rob1@test.com           | No      | No        | Yes   |
      | July Robertson   | July       | Robertson | rob1@test.com           | Yes     | No        | No    |
