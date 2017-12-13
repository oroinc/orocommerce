@ticket-BB-13090
@fixture-OroProductBundle:Products_quick_order_form.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroUserBundle:user.yml
Feature: Login on guest checkout with shopping list limit
  In order to spend less time typing my credentials
  As a Guest customer
  I want an option to sign in to existing account

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
      |Shopping List Limit Default       |false|
      |Shopping List Limit               |1    |
    And click "Save settings"
    And follow "Commerce/Sales/Checkout" on configuration sidebar
    And fill "Checkout Configuration Form" with:
      |Enable Guest Checkout Default|false|
      |Enable Guest Checkout        |true |
    And click "Save settings"

  Scenario: Sign in to existing account during checkout when shopping list limit is reached
    Given I proceed as the Guest
    And I am on the homepage
    When type "PSKU1" in "search"
    And I click "Search Button"
    Then I should see "Product1"
    When I click "Add to Shopping List"
    Then I should see "Product has been added to " flash message
    And I click "Shopping List"
    When I click "Create Order"
    And I type "AmandaRCole@example.org" in "Email Address"
    And I type "AmandaRCole@example.org" in "Password"
    When I click "Sign In and Continue"
    Then I should see "Signed in as: Amanda Cole"
    And I should see "Billing Information"
    And I should see "2 Shopping Lists"
