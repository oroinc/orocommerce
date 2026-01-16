@ticket-BB-25100
@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroCheckoutBundle:CheckoutCustomerFixture.yml
@fixture-OroCheckoutBundle:CheckoutProductFixture.yml
@fixture-OroCheckoutBundle:CustomerAPriceList.yml

Feature: Minimum and maximum order amounts rechecked after login
  In order to not allow creating orders without passing minimum and maximum order amount check in case product prices are different for guest and registered user
  As a buyer
  I want order limits to be rechecked after user logs in from guest checkout

  Scenario: Feature Background
    Given sessions active:
      | Admin  | first_session  |
      | Buyer  | second_session |

  Scenario: Set minimum and maximum order amounts in the system config
    Given I proceed as the Admin
    And login as administrator
    And I go to System/Configuration
    And I follow "Commerce/Sales/Checkout" on configuration sidebar
    And uncheck "Use default" for "Minimum Order Amount" field
    And I fill in "Minimum Order Amount USD Config Field" with "18"
    When I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Enable guest shopping list and checkout
    Given I go to System/Configuration
    When I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And I fill "Shopping List Configuration Form" with:
      | Enable Guest Shopping List Default | false |
      | Enable Guest Shopping List         | true  |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message
    When I follow "Commerce/Sales/Checkout" on configuration sidebar
    And I fill "Checkout Configuration Form" with:
      | Enable Guest Checkout Default | false |
      | Enable Guest Checkout         | true  |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Add products to shopping list as guest exceeding minimum order amount
    Given I proceed as the Buyer
    And I am on homepage
    And I type "SKU123" in "search"
    And I click "Search Button"
    And I click "400-Watt Bulb Work Light"
    And I type "10" in "FrontendProductViewQuantityField"
    And I click "Add to Shopping List"
    And I should see "Product has been added to " flash message
    When I open shopping list widget
    And I click "Checkout"
    Then I should see "Already have an account?"
    And I click "Click here to Log In"
    And I type "AmandaRCole@example.org" in "Email"
    And I type "AmandaRCole@example.org" in "Password"
    When I click "Log In Button"
    And I wait for 1 seconds
    Then I should see "Amanda Cole"
    And I should see "Shopping List"
    And I should see "Total: $15.00"
    Then I should see "A minimum order subtotal of $18.00 is required to check out. Please add $3.00 more to proceed."
    And I should see a "Disabled Create Order From Shopping List Button" element
