@ticket-BB-27337
@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroCheckoutBundle:CheckoutProductFixture.yml

Feature: Guest checkout reuses customer user across multiple checkouts in same visitor session
  In order to keep one customer and customer user per guest browsing session
  As an Administrator
  I want subsequent guest checkouts in the same session to reuse the customer user already linked to the visitor

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Guest | second_session |
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

  Scenario: Guest places the first checkout as a guest
    Given I proceed as the Guest
    And I am on the homepage
    When I type "SKU123" in "search"
    And I click "Search Button"
    And I click "Add to Shopping List" for "SKU123" product
    Then I should see "Product has been added to " flash message and I close it
    When I open shopping list widget
    And I click "Open List"
    And click on "Create Order"
    And I click "Proceed to Guest Checkout?"
    And I fill form with:
      | Email           | first.guest@example.com |
      | First Name      | FirstGuest              |
      | Last Name       | Buyer                   |
      | Street          | Fifth avenue            |
      | City            | Berlin                  |
      | Country         | Germany                 |
      | State           | Berlin                  |
      | Zip/Postal Code | 10115                   |
    And I click "Ship to This Address"
    And I click "Continue"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I click "Expand Checkout Footer"
    And I uncheck "Save my data and create an account" on the checkout page
    When I click "Submit Order"
    Then I should see "Thank You For Your Purchase!"

  Scenario: Admin verifies one guest customer user exists after first checkout
    Given I proceed as the Admin
    When I go to Customers/ Customer Users
    Then I should see following grid containing rows:
      | Email Address           | First Name | Last Name |
      | first.guest@example.com | FirstGuest | Buyer     |

  Scenario: Guest places a second checkout in the same session with different email and address
    Given I proceed as the Guest
    And I am on the homepage
    When I type "SKU123" in "search"
    And I click "Search Button"
    And I click "Add to Shopping List" for "SKU123" product
    Then I should see "Product has been added to " flash message and I close it
    When I open shopping list widget
    And I click "Open List"
    And click on "Create Order"
    And I click "Proceed to Guest Checkout?"
    And I fill form with:
      | Email           | second.guest@example.com |
      | First Name      | SecondGuest              |
      | Last Name       | Buyer                    |
      | Street          | Sixth street             |
      | City            | Berlin                   |
      | Country         | Germany                  |
      | State           | Berlin                   |
      | Zip/Postal Code | 10115                    |
    And I click "Ship to This Address"
    And I click "Continue"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I click "Expand Checkout Footer"
    And I uncheck "Save my data and create an account" on the checkout page
    When I click "Submit Order"
    Then I should see "Thank You For Your Purchase!"

  Scenario: Admin verifies the same customer user was reused (no duplicate created)
    Given I proceed as the Admin
    When I go to Customers/ Customer Users
    Then I should see following grid containing rows:
      | Email Address            | First Name  | Last Name |
      | second.guest@example.com | SecondGuest | Buyer     |
    When I filter "Email Address" as contains "first.guest@example.com"
    Then there is no records in grid

  Scenario: Guest places a third checkout in the same session and registers via late registration
    Given I proceed as the Guest
    And I am on the homepage
    When I type "SKU123" in "search"
    And I click "Search Button"
    And I click "Add to Shopping List" for "SKU123" product
    Then I should see "Product has been added to " flash message and I close it
    When I open shopping list widget
    And I click "Open List"
    And click on "Create Order"
    And I click "Proceed to Guest Checkout?"
    And I fill form with:
      | Email           | third.guest@example.com |
      | First Name      | ThirdGuest              |
      | Last Name       | Buyer                   |
      | Street          | Fifth avenue            |
      | City            | Berlin                  |
      | Country         | Germany                 |
      | State           | Berlin                  |
      | Zip/Postal Code | 10115                   |
    And I click "Ship to This Address"
    And I click "Continue"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I click "Expand Checkout Footer"
    And I check "Save my data and create an account" on the checkout page
    And I type "Strong1@Pass" in "Password"
    And I type "Strong1@Pass" in "Confirm Password"
    When I click "Submit Order"
    Then I should see "Thank You For Your Purchase!"
    And email with Subject "Confirmation of account registration on checkout" containing the following was sent:
      | Body | Please follow this link to confirm your email address and continue checkout: Confirm |
    And I remember "Confirm" link from the email

  Scenario: Guest confirms email, logs in, and sees all three orders in their account
    Given I proceed as the Guest
    When I follow remembered "Confirm" link from the email
    And I click "Log In"
    And I fill "Customer Login Form" with:
      | Email    | third.guest@example.com |
      | Password | Strong1@Pass            |
    And I click "Log In Button"
    Then I should see "My Account"
    When I click "Account Dropdown"
    And I click "Order History"
    Then records in "Past Orders Grid" should be 3
