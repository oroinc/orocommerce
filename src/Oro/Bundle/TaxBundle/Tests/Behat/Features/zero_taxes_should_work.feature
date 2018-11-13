@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroTaxBundle:ProductsAndShoppingListsAndTaxesFixture.yml
Feature: Checkout from Shopping List with zero tax
  In order to create order from Shopping List on front store
  As a buyer
  I want to start checkout from Shopping List view page and use zero taxes

  Scenario: Feature Background
    Given Base tax value is set to "Destination"
    When I login as administrator
    And I go to Taxes/ Taxes
    And I click edit berlin_sales in grid
    And I fill form with:
      | Rate | 0 |
    And save and close form
    Then I should see "Tax has been saved" flash message

  Scenario: Create order from Shopping List with zero tax
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I hover on "Shopping Cart"
    And click "View Details"
    When I click "Create Order"
    And I click "Ship to This Address"
    And I click "Continue"
    Then I should see Checkout Totals with data:
      | Subtotal | $10.00 |
      | Shipping | $3.00  |
    And I should not see "Tax"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    When I click "Submit Order"
    Then I should see "Thank You For Your Purchase!"
