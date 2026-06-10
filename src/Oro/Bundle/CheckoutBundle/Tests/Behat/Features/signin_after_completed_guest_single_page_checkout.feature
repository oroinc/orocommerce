@ticket-BB-25897
@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroCheckoutBundle:CheckoutProductFixture.yml
@fixture-OroCheckoutBundle:CheckoutCustomerFixture.yml

Feature: Sign-in after completed guest single page checkout
  In order to keep order ownership stable after signing in
  As a Customer
  I want completed orders to stay attached only to the customer user who placed them

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I enable configuration options:
      | oro_shopping_list.availability_for_guests |
      | oro_checkout.guest_checkout               |

  Scenario: Set payment term for Non-Authenticated Visitors group
    Given I proceed as the Admin
    And I login as administrator
    And go to Customers/ Customer Groups
    And I click Edit Non-Authenticated Visitors in grid
    And I fill form with:
      | Payment Term | net 10 |
    When I save form
    Then I should see "Customer group has been saved" flash message

  Scenario: Activate Single Page Checkout workflow
    Given go to System/ Workflows
    When I click "Activate" on row "Single Page Checkout" in grid
    And I click "Activate" in modal window
    Then I should see "Workflow activated" flash message

  Scenario: Owner guest places single page checkout order with late registration
    Given I proceed as the Buyer
    And I am on the homepage
    When I type "SKU123" in "search"
    And I click "Search Button"
    And I click "400-Watt Bulb Work Light"
    And I click "Add to Shopping List"
    And I follow "Shopping List" link within flash message "Product has been added to \"Shopping list\""
    And I click "Create Order"
    And I click on "Add Address Single Page Checkout Btn" with title "Add" in element "Single Page Checkout Billing Section"
    And I fill "New Address Popup Form" with:
      | Email           | firstguest@example.com |
      | First Name      | FirstGuest             |
      | Last Name       | Buyer                  |
      | Street          | Fifth avenue           |
      | City            | Berlin                 |
      | Country         | Germany                |
      | State           | Berlin                 |
      | Zip/Postal Code | 10115                  |
    And I click "Add Address" in modal window
    And I scroll to top
    And I check "Use billing address" on the checkout page
    And I check "Flat Rate" on the checkout page
    And I check "Payment Terms" on the checkout page
    And the "Save my data and create an account" checkbox should be checked
    And I type "firstguest@example.com" in "Email Address"
    And I type "Strong1@Pass" in "Password"
    And I type "Strong1@Pass" in "Confirm Password"
    And I wait "Submit Order" button
    When I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Admin confirms the newly registered customer user
    Given I proceed as the Admin
    When go to Customers/ Customer Users
    And I click View "firstguest@example.com" in grid
    And I click "Confirm"
    Then I should see "Confirmation successful" flash message

  Scenario: Owner signs in from the Thank You page and lands on homepage with their order in history
    Given I proceed as the Buyer
    And I click "Log In"
    And I fill "Customer Login Form" with:
      | Email    | firstguest@example.com |
      | Password | Strong1@Pass           |
    When I click "Log In Button"
    And I should be on "/"
    And I click "Account Dropdown"
    And I click "Order History"
    Then there is no records in "OpenOrdersGrid"
    And records in "PastOrdersGrid" should be 1

  Scenario: Buyer signs out and places a second guest single page checkout order
    Given I proceed as the Buyer
    And I click "Account Dropdown"
    And I click "Sign Out"
    And I am on the homepage
    When I type "SKU123" in "search"
    And I click "Search Button"
    And I click "400-Watt Bulb Work Light"
    And I click "Add to Shopping List"
    And I follow "Shopping List" link within flash message "Product has been added to \"Shopping list\""
    And I click "Create Order"
    And I click on "Add Address Single Page Checkout Btn" with title "Add" in element "Single Page Checkout Billing Section"
    And I fill "New Address Popup Form" with:
      | Email           | secondguest@example.com |
      | First Name      | SecondGuest             |
      | Last Name       | Buyer                   |
      | Street          | Fifth avenue            |
      | City            | Berlin                  |
      | Country         | Germany                 |
      | State           | Berlin                  |
      | Zip/Postal Code | 10115                   |
    And I click "Add Address" in modal window
    And I scroll to top
    And I check "Use billing address" on the checkout page
    And I check "Flat Rate" on the checkout page
    And I check "Payment Terms" on the checkout page
    And the "Save my data and create an account" checkbox should be checked
    And I type "secondguest@example.com" in "Email Address"
    And I type "Strong1@Pass" in "Password"
    And I type "Strong1@Pass" in "Confirm Password"
    And I wait "Submit Order" button
    When I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: A different existing customer signs in from the Thank You page and does not inherit the guest order
    Given I proceed as the Buyer
    And I click "Log In"
    And I fill "Customer Login Form" with:
      | Email    | AmandaRCole@example.org |
      | Password | AmandaRCole@example.org |
    When I click "Log In Button"
    And I should be on "/"
    And I click "Account Dropdown"
    And I click "Order History"
    Then there is no records in "OpenOrdersGrid"
    And there is no records in "PastOrdersGrid"
