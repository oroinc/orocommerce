@ticket-BB-24255
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroFlatRateShippingBundle:FlatRate2Integration.yml
@fixture-OroCheckoutBundle:ShippingRuleFreeShipping.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:CheckoutCustomerFixture.yml
@fixture-OroCheckoutBundle:ProductsAndCategoriesForMultiShippingFixture.yml
@fixture-OroCheckoutBundle:ShoppingListForMultiShippingFixture.yml

Feature: Checkout With Line Items Grouping
  In order to control shipping methods for each group of order line items
  As a Customer User
  I want to see shipping method selection for each group of order line items in checkout

  Scenario: Create sessions
    Given sessions active:
      | Buyer | second_session |
    And I change configuration options:
      | oro_checkout.enable_line_item_grouping | true |

  Scenario: Checkout with shipping method selection per line item group
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    Then I should see following "Multi Shipping Shopping List" grid:
      | SKU  | Item                                | Price  | Subtotal |
      | SKU1 | 400-Watt Bulb Work Light            | $2.00  | $10.00   |
      | SKU2 | iPhone 13                           | $2.00  | $20.00   |
      | SKU3 | iPhone X                            | $2.00  | $20.00   |
      | SKU4 | Round Meeting Table, 30 in. x 30in. |        |          |
    When I click "Create Order"
    Then Page title equals to "Billing Information - Checkout"
    And I should see "Lighting Products" in the "First Checkout Shipping Grid Title" element
    And I click "Lighting Products"
    And I should see following "First Checkout Shipping Grid" grid:
      | SKU  | Item                     | Qty | Price  | Subtotal |
      | SKU1 | 400-Watt Bulb Work Light | 5   | $2.00  | $10.00   |
    And records in "First Checkout Shipping Grid" should be 1
    And I should see "Phones" in the "Second Checkout Shipping Grid Title" element
    And I click "Phones"
    And I should see following "Second Checkout Shipping Grid" grid:
      | SKU  | Item                     | Qty | Price  | Subtotal |
      | SKU2 | iPhone 13                | 10  | $2.00  | $20.00   |
      | SKU3 | iPhone X                 | 10  | $2.00  | $20.00   |
    And records in "Second Checkout Shipping Grid" should be 2
    When I click "Continue"
    Then Page title equals to "Shipping Information - Checkout"
    And I should see "Lighting Products" in the "First Checkout Shipping Grid Title" element
    And I click "Lighting Products"
    And I should see following "First Checkout Shipping Grid" grid:
      | SKU  | Item                     | Qty | Price  | Subtotal |
      | SKU1 | 400-Watt Bulb Work Light | 5   | $2.00  | $10.00   |
    And records in "First Checkout Shipping Grid" should be 1
    And I should see "Phones" in the "Second Checkout Shipping Grid Title" element
    And I click "Phones"
    And I should see following "Second Checkout Shipping Grid" grid:
      | SKU  | Item                     | Qty | Price  | Subtotal |
      | SKU2 | iPhone 13                | 10  | $2.00  | $20.00   |
      | SKU3 | iPhone X                 | 10  | $2.00  | $20.00   |
    And records in "Second Checkout Shipping Grid" should be 2
    When I click "Continue"
    Then Page title equals to "Shipping Method - Checkout"
    And I should see "Lighting Products" in the "First Checkout Shipping Grid Title" element
    And I click "Lighting Products"
    And I should see following "First Checkout Shipping Grid" grid:
      | SKU  | Item                     | Qty | Price  | Subtotal |
      | SKU1 | 400-Watt Bulb Work Light | 5   | $2.00  | $10.00   |
    And records in "First Checkout Shipping Grid" should be 1
    And I should see "Phones" in the "Second Checkout Shipping Grid Title" element
    And I click "Phones"
    And I should see following "Second Checkout Shipping Grid" grid:
      | SKU  | Item      | Qty | Price  | Subtotal |
      | SKU2 | iPhone 13 | 10  | $2.00  | $20.00   |
      | SKU3 | iPhone X  | 10  | $2.00  | $20.00   |
    And records in "Second Checkout Shipping Grid" should be 2
    And I click "Expand Checkout Footer"
    And I should see Checkout Totals with data:
      | Subtotal | $50.00 |
      | Shipping | $3.00  |
    When I type "Flat Rate" in "Second Checkout Shipping Grid Flat Rate Shipping Method"
    And I should see Checkout Totals with data:
      | Subtotal | $50.00 |
      | Shipping | $6.00  |
    When I click "Continue"
    Then Page title equals to "Payment - Checkout"
    And I should see "Lighting Products" in the "First Checkout Shipping Grid Title" element
    And I click "Lighting Products"
    And I should see following "First Checkout Shipping Grid" grid:
      | SKU  | Item                     | Qty | Price  | Subtotal |
      | SKU1 | 400-Watt Bulb Work Light | 5   | $2.00  | $10.00   |
    And records in "First Checkout Shipping Grid" should be 1
    And I should see "Phones" in the "Second Checkout Shipping Grid Title" element
    And I click "Phones"
    And I should see following "Second Checkout Shipping Grid" grid:
      | SKU  | Item      | Qty | Price  | Subtotal |
      | SKU2 | iPhone 13 | 10  | $2.00  | $20.00   |
      | SKU3 | iPhone X  | 10  | $2.00  | $20.00   |
    And records in "Second Checkout Shipping Grid" should be 2
    And I click "Expand Checkout Footer"
    And I should see Checkout Totals with data:
      | Subtotal | $50.00 |
      | Shipping | $6.00  |
    When I click "Continue"
    Then Page title equals to "Order Review - Checkout"
    And I should see "Lighting Products" in the "First Checkout Shipping Grid Title" element
    And I should see following "First Checkout Shipping Grid" grid:
      | SKU  | Item                     | Qty | Price  | Subtotal |
      | SKU1 | 400-Watt Bulb Work Light | 5   | $2.00  | $10.00   |
    And records in "First Checkout Shipping Grid" should be 1
    And I should see "Phones" in the "Second Checkout Shipping Grid Title" element
    And I should see following "Second Checkout Shipping Grid" grid:
      | SKU  | Item      | Qty | Price  | Subtotal |
      | SKU2 | iPhone 13 | 10  | $2.00  | $20.00   |
      | SKU3 | iPhone X  | 10  | $2.00  | $20.00   |
    And records in "Second Checkout Shipping Grid" should be 2
    And I should see Checkout Totals with data:
      | Subtotal | $50.00 |
      | Shipping | $6.00  |
    When I click "Submit Order"
    Then I should see "Thank You For Your Purchase!"
    When I open Order History page on the store frontend
    Then I should see following "Past Orders Grid" grid:
      | Order Number |
      | 1            |
    And records in "Past Orders Grid" should be 1
