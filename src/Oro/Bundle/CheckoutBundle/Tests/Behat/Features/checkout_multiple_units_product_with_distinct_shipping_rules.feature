@ticket-BB-21098
@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:CheckoutMultipleUnitsProduct.yml

Feature: Checkout multiple units product with distinct shipping rules
  In order to checkout a product with multiple units to have correct shipping price
  As a Buyer
  I need to have two distinct shipping rules and watch a rule be applied correctly

  Scenario: I Login in frontend store and prepare for following test
    Given I signed in as AmandaRCole@example.org on the store frontend

  Scenario Outline: Checkout every shipping list to check every shipping price are correctly calculated
    When I open page with shopping list <shoppingList>
    And I click "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I should see "Flat Rate: <shippingPrice>"
    Then I should see Checkout Totals with data:
      | Shipping | <shippingPrice>   |
      | Total    | <total>           |
    And I should see "Total <total>"
    Examples:
      | shoppingList | shippingPrice | total   |
      | List 1       | $100.00       | $100.00 |
      | List 2       | $100.00       | $100.00 |
      | List 3       | $10.00        | $10.00  |
      | List 4       | $100.00       | $100.00 |
