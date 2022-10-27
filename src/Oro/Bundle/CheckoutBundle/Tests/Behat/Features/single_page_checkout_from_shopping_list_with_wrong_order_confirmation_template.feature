@regression
@ticket-BB-16109
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml

Feature: Single Page Checkout From Shopping List With Wrong Order Confirmation Template
  In order to to create order from Shopping List on front store
  As a Buyer
  I want to start and complete single-page checkout from shopping list with wrong order confirmation template, email will not be sent

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I activate "Single Page Checkout" workflow

  Scenario: Edit order confirmation template
    Given I proceed as the Admin
    And login as administrator
    And I go to System/ Emails/ Templates
    And I filter Template Name as Contains "order_confirmation_email"
    And I click edit "order_confirmation_email" in grid
    And I fill "Email Template Form" with:
      | Content | {{ item.product_name_with_error }} |
    When I save form
    Then I should see "Template saved" flash message

  Scenario: Create order from Shopping List 1
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    When I open page with shopping list List 1
    And I click "Create Order"
    And I select "ORO, Fifth avenue, 10115 Berlin, Germany" from "Select Billing Address"
    And I select "ORO, Fifth avenue, 10115 Berlin, Germany" from "Select Shipping Address"
    And I check "Flat Rate" on the checkout page
    And I check "Payment Terms" on the checkout page
    And I check "Delete this shopping list after submitting order" on the checkout page
    And I press "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    And email with Subject "Your Store Name order has been received." was not sent
