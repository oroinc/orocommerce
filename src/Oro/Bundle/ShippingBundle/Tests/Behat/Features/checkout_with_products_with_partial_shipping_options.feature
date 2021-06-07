@ticket-BB-20677
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroShippingBundle:StartCheckoutWithPartialShippingOptions.yml

Feature: Checkout with Products with Partial Shipping Options
  In order to make checkout
  As a Buyer
  I want to start and finish checkout with products with partially filled shipping options

  Scenario: Checks if checkout can be started with products with partially filled shipping options
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I open page with shopping list ShoppingList1
    And I should see following grid:
      | SKU  | Item      | Price  | Subtotal |
      | BB01 | Product1  | $1.00  | $1.00    |
      | BB02 | Product2  | $2.00  | $4.00    |
      | BB03 | Product3  | $3.00  | $9.00    |
      | BB04 | Product4  | $4.00  | $16.00   |
      | BB05 | Product5  | $5.00  | $25.00   |
      | BB06 | Product6  | $6.00  | $36.00   |
      | BB07 | Product7  | $7.00  | $49.00   |
      | BB08 | Product8  | $8.00  | $64.00   |
      | BB09 | Product9  | $9.00  | $81.00   |
      | BB10 | Product10 | $10.00 | $100.00  |
      | BB11 | Product11 | $11.00 | $121.00  |
    When I click "Create Order"
    Then Page title equals to "Billing Information - Checkout"
    And I click "Continue"
    And I click "Continue"
    And I click "Continue"
    And I click "Continue"
    When I click "Submit Order"
    Then I should see "Thank You For Your Purchase!"
