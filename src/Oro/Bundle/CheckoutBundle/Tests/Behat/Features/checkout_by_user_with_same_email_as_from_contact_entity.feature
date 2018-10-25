@ticket-BB-14713
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroCheckoutBundle:ShoppingListForCheckoutForNancy.yml

Feature: Checkout by user with same email as from contact entity
  In order to process checkout
  As a Buyer
  I need to have an ability to complete checkout without eny errors

  Scenario: Process checkout by user with same email as from contact entity
    Given I login as administrator
    And I go to Customers / Contacts
    And I click "Create Contact"
    And fill form with:
    | First name | Amandacontact |
    | Last name  | Colecontact   |
    | Emails     | [NancyJSallee@example.org] |
    And I save form
    When I signed in as NancyJSallee@example.org on the store frontend
    And I open page with shopping list Shopping List 6
    And I click "Create Order"
    And I select "2849 Junkins Avenue, ALBANY NY US 31707" on the "Billing Information" checkout step and press Continue
    And I select "2849 Junkins Avenue, ALBANY NY US 31707" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I click "Continue"
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
