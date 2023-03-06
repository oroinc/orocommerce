@ticket-BB-21182
@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroFlatRateShippingBundle:FlatRate2Integration.yml
@fixture-OroCheckoutBundle:ShippingRuleFreeShipping.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:CheckoutCustomerFixture.yml
@fixture-OroCheckoutBundle:ProductsAndCategoriesForMultiShippingFixture.yml
@fixture-OroCheckoutBundle:ShoppingListForMultiShippingFixture.yml

Feature: Checkout With Multi Shipping
  In order to control shipping methods for each order line item
  As a Customer User
  I want to see shipping method selection for each line item in checkout

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I change configuration options:
      | oro_checkout.enable_shipping_method_selection_per_line_item | true |

  Scenario: Checkout with shipping method selection per line item
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I open page with shopping list List 1
    And I should see following "Multi Shipping Shopping List" grid:
      | SKU  | Item                                | Price  | Subtotal |
      | SKU1 | 400-Watt Bulb Work Light            | $2.00  | $10.00   |
      | SKU2 | iPhone 13                           | $2.00  | $20.00   |
      | SKU3 | iPhone X                            | $2.00  | $20.00   |
      | SKU4 | Round Meeting Table, 30 in. x 30in. |        |          |
    And I should see notification "This product will be available later" for "SKU1" line item "Checkout Line Item"
    And I should see notification "This product will be available later" for "SKU3" line item "Checkout Line Item"
    When I click "Create Order"
    Then Page title equals to "Billing Information - Checkout"
    And I should not see an "Lighting Products Checkout Category Name" element
    And I should see following "MultiShipping Checkout Line Items Grid" grid:
      | SKU  | Item                     | Qty | Price  | Subtotal |
      | SKU1 | 400-Watt Bulb Work Light | 5   | $2.00  | $10.00   |
      | SKU2 | iPhone 13                | 10  | $2.00  | $20.00   |
      | SKU3 | iPhone X                 | 10  | $2.00  | $20.00   |
    And records in "MultiShipping Checkout Line Items Grid" should be 3
    And I should see notification "This product will be available later" for "SKU1" line item "Checkout Line Item"
    And I should see notification "This product will be available later" for "SKU3" line item "Checkout Line Item"
    And I should not see an "Phones Checkout Category Name" element

  Scenario: Enable line items grouping by id
    Given I proceed as the Admin
    And I login as administrator
    When I go to System/Configuration
    And I follow "Commerce/Sales/Multi Shipping Options" on configuration sidebar
    And uncheck "Use default" for "Enable grouping of line items during checkout" field
    And I fill form with:
      | Enable grouping of line items during checkout | true |
    And uncheck "Use default" for "Group line items by" field
    And I fill form with:
      | Group line items by | Id |
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: When grouping by product id, group titles should show product SKUs instead
    Given I proceed as the Buyer
    When I reload the page
    # Grouped line items datagrids should be visible on each step.
    Then Page title equals to "Billing Information - Checkout"
    And I should see "SKU1" in the "First Checkout Shipping Grid Title" element
    And I should see following "First Checkout Shipping Grid" grid:
      | SKU  | Item                     | Qty | Price  | Subtotal |
      | SKU1 | 400-Watt Bulb Work Light | 5   | $2.00  | $10.00   |
    And records in "First Checkout Shipping Grid" should be 1
    And I should see "SKU2" in the "Second Checkout Shipping Grid Title" element
    And I should see following "Second Checkout Shipping Grid" grid:
      | SKU  | Item      | Qty | Price  | Subtotal |
      | SKU2 | iPhone 13 | 10  | $2.00  | $20.00   |
    And records in "Second Checkout Shipping Grid" should be 1
    And I should see "SKU3" in the "Third Checkout Shipping Grid Title" element
    And I should see following "Third Checkout Shipping Grid" grid:
      | SKU  | Item      | Qty | Price  | Subtotal |
      | SKU3 | iPhone X  | 10  | $2.00  | $20.00   |
    And records in "Third Checkout Shipping Grid" should be 1

  Scenario: Enable line items grouping by category
    Given I proceed as the Admin
    And I fill form with:
      | Group line items by | Category |
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: Checkout with line items grouping
    Given I proceed as the Buyer
    When I reload the page
    Then Page title equals to "Billing Information - Checkout"
    And I should see "Lighting Products" in the "First Checkout Shipping Grid Title" element
    And I should see following "First Checkout Shipping Grid" grid:
      | SKU  | Item                     | Qty | Price  | Subtotal |
      | SKU1 | 400-Watt Bulb Work Light | 5   | $2.00  | $10.00   |
    And records in "First Checkout Shipping Grid" should be 1
    And I should see notification "This product will be available later" for "SKU1" line item "Checkout Line Item"
    And I should see "Phones" in the "Second Checkout Shipping Grid Title" element
    And I should see following "Second Checkout Shipping Grid" grid:
      | SKU  | Item      | Qty | Price  | Subtotal |
      | SKU2 | iPhone 13 | 10  | $2.00  | $20.00   |
      | SKU3 | iPhone X  | 10  | $2.00  | $20.00   |
    And records in "Second Checkout Shipping Grid" should be 2
    And I should see notification "This product will be available later" for "SKU3" line item "Checkout Line Item"
    And I click "Continue"
    Then Page title equals to "Shipping Information - Checkout"
    And I should see "Lighting Products" in the "First Checkout Shipping Grid Title" element
    And I should see following "First Checkout Shipping Grid" grid:
      | SKU  | Item                     | Qty | Price  | Subtotal |
      | SKU1 | 400-Watt Bulb Work Light | 5   | $2.00  | $10.00   |
    And records in "First Checkout Shipping Grid" should be 1
    And I should see notification "This product will be available later" for "SKU1" line item "Checkout Line Item"
    And I should see "Phones" in the "Second Checkout Shipping Grid Title" element
    And I should see following "Second Checkout Shipping Grid" grid:
      | SKU  | Item      | Qty | Price  | Subtotal |
      | SKU2 | iPhone 13 | 10  | $2.00  | $20.00   |
      | SKU3 | iPhone X  | 10  | $2.00  | $20.00   |
    And records in "Second Checkout Shipping Grid" should be 2
    And I should see notification "This product will be available later" for "SKU3" line item "Checkout Line Item"
    And I click "Continue"
    Then Page title equals to "Shipping Method - Checkout"
    And I should see "Lighting Products" in the "First Checkout Shipping Grid Title" element
    And I should see following "First Checkout Shipping Grid" grid:
      | SKU  | Item                     | Qty | Price  | Subtotal | Shipping         |
      | SKU1 | 400-Watt Bulb Work Light | 5   | $2.00  | $10.00   | Flat Rate: $3.00 |
    And records in "First Checkout Shipping Grid" should be 1
    And I should see "Phones" in the "Second Checkout Shipping Grid Title" element
    And I should see following "Second Checkout Shipping Grid" grid:
      | SKU  | Item      | Qty | Price  | Subtotal | Shipping                            |
      | SKU2 | iPhone 13 | 10  | $2.00  | $20.00   | Flat Rate 2: $0.00 Flat Rate: $3.00 |
      | SKU3 | iPhone X  | 10  | $2.00  | $20.00   | Flat Rate 2: $0.00 Flat Rate: $3.00 |
    And records in "Second Checkout Shipping Grid" should be 2
    When I click on "Second Line Item Flat Rate Shipping Method"
    And I click on "Third Line Item Flat Rate Shipping Method"
    Then I should see Checkout Totals with data:
      | Subtotal | $50.00 |
      | Shipping | $9.00  |
    When I click "Continue"
    Then Page title equals to "Payment - Checkout"
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
    And I click "Continue"
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
    When I click "Submit Order"
    Then I should see "Thank You For Your Purchase!"
    When I open Order History page on the store frontend
    Then I should see following "Past Orders Grid" grid:
      | Order Number |
      | 1            |
    And records in "Past Orders Grid" should be 1
    Given I proceed as the Admin
    And I go to Sales/Orders
    Then I should see following grid:
      | Order Number |
      | 1            |
    And there is one record in grid
    When I click view "1" in grid
    Then I should see "Order #1"
    And I should see following "BackendOrderLineItemsGrid" grid:
      | SKU  | Product                  | Quantity |
      | SKU1 | 400-Watt Bulb Work Light | 5        |
      | SKU2 | iPhone 13                | 10       |
      | SKU3 | iPhone X                 | 10       |
    When click "Grid Settings"
    Then I should see following columns in the grid settings:
      | Shipping Method |
      | Shipping Cost   |
    And I check "Shipping Method"
    And I check "Shipping Cost"
    When I click "Grid Settings"
    Then I should see following "BackendOrderLineItemsGrid" grid:
      | SKU  | Product                  | Quantity | Shipping Method | Shipping Cost |
      | SKU1 | 400-Watt Bulb Work Light | 5        | Flat Rate       |  $3.00        |
      | SKU2 | iPhone 13                | 10       | Flat Rate       |  $3.00        |
      | SKU3 | iPhone X                 | 10       | Flat Rate       |  $3.00        |
