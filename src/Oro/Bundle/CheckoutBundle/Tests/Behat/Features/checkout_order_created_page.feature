@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:DefaultCheckoutFromShoppingList.yml

Feature: Checkout Order Created Page

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create order
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I click "Create Order"
    And I check "Ship to this address" on the checkout page
    And I press "Continue"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And on the "Payment" checkout step I press Continue
    And I press "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    And should see "Your order number is 1"

  Scenario: Check that the "Thank You" page is displayed correctly after an user go to it by direct link
    When I am on homepage
    And I am on "/customer/checkout/1"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    And should see "Your order number is 1"

  Scenario: Check that the "Thank You" page is displayed correctly even if the checkout workflow is deactivated
    Given I proceed as the Admin
    And I login as administrator
    And I go to System/Workflows
    And I check "Checkout" in Related Entity filter
    And I filter Exclusive Active Groups as is equal to "b2b_checkout_flow"
    And I click "Deactivate" on row "Checkout" in grid
    And I click "Yes, Deactivate" in modal window
    When I continue as the Buyer
    And I am on homepage
    And I am on "/customer/checkout/1"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    And should see "Your order number is 1"
