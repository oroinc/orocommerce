@ticket-BB-27337
@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroCheckoutBundle:CheckoutProductFixture.yml

Feature: Late registration during guest checkout starts a fresh guest session for subsequent checkouts
  In order to isolate orders placed before and after a guest registers mid-checkout
  As an Administrator
  I want a guest who registers via "Save my data" on the 1st checkout to start a brand-new guest customer user on the next checkout in the same browser session

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

  Scenario: Guest places the first checkout with late registration enabled
    Given I proceed as the Guest
    And I am on the homepage
    When I type "SKU123" in "search"
    And I click "Search Button"
    And I click "Add to Shopping List" for "SKU123" product
    Then I should see "Product has been added to " flash message and I close it
    When I open shopping list widget
    And I click "Open List"
    And click on "Create Order"
    And I click "Continue as a Guest"
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
    And I check "Save my data and create an account" on the checkout page
    And I type "first.guest@example.com" in "Email Address"
    And I type "Strong1@Pass" in "Password"
    And I type "Strong1@Pass" in "Confirm Password"
    And I wait "Submit Order" button
    When I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    And email with Subject "Confirmation of account registration on checkout" containing the following was sent:
      | Body | Please follow this link to confirm your email address and continue checkout: Confirm |

  Scenario: Guest places a second checkout in the same session as a brand-new guest
    Given I proceed as the Guest
    And I am on the homepage
    When I type "SKU123" in "search"
    And I click "Search Button"
    And I click "Add to Shopping List" for "SKU123" product
    Then I should see "Product has been added to " flash message and I close it
    When I open shopping list widget
    And I click "Open List"
    And click on "Create Order"
    And I click "Continue as a Guest"
    And I fill form with:
      | Email           | second.guest@example.com |
      | First Name      | SecondGuest              |
      | Last Name       | Buyer                    |
      | Street          | Fifth avenue             |
      | City            | Berlin                   |
      | Country         | Germany                  |
      | State           | Berlin                   |
      | Zip/Postal Code | 10115                    |
    And I click "Ship to This Address"
    And I click "Continue"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I uncheck "Save my data and create an account" on the checkout page
    When I click "Submit Order"
    Then I should see "Thank You For Your Purchase!"

  # Two customer users are expected here — the registered one from the 1st checkout and a fresh guest one from the 2nd.
  # Attaching the 2nd order to the 1st (registered) customer user would cause several business problems:
  #   1. Anyone using the same browser session after signout would silently take over the registered identity.
  #   2. The 2nd order would be attributed to a user who never confirmed their email and never explicitly placed it.
  #   3. Logging out is expected to reset the session; silently keeping the previous customer user breaks that.
  #   4. Clicking the pending confirmation link would grant the registering user ownership of in-between orders.
  #   5. other potential issues may arise from a registered identity staying linked to an anonymous session.
  Scenario: Admin verifies that two separate customer users were created
    Given I proceed as the Admin
    When I go to Customers/ Customer Users
    Then I should see following grid containing rows:
      | Email Address            | First Name  | Last Name |
      | first.guest@example.com  | FirstGuest  | Buyer     |
      | second.guest@example.com | SecondGuest | Buyer     |
