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

Feature: Checkout With Multi Shipping And Returns To Checkout From Other Pages
  In order to create separate sub orders
  As a Customer User
  I want to see that selected line items shipping methods are saved when I follow to other pages and return back to checkout

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I change configuration options:
      | oro_checkout.enable_shipping_method_selection_per_line_item | true             |
      | oro_checkout.enable_line_item_grouping                      | true             |
      | oro_checkout.group_line_items_by                            | product.category |
      | oro_checkout.create_suborders_for_each_group                | true             |

  Scenario: Start checkout with shipping method selection per line item and grouping line items
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
    And I click "Continue"
    Then Page title equals to "Shipping Information - Checkout"
    And I click "Continue"
    Then Page title equals to "Shipping Method - Checkout"
    And I should see an "Lighting Products Checkout Category Name" element
    And I should see following "First Checkout Shipping Grid" grid:
      | SKU  | Item                     | Qty | Price  | Subtotal | Shipping         |
      | SKU1 | 400-Watt Bulb Work Light | 5   | $2.00  | $10.00   | Flat Rate: $3.00 |
    And records in "First Checkout Shipping Grid" should be 1
    And I should see an "Phones Checkout Category Name" element
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
    And I should see following "First Checkout Shipping Grid" grid:
      | SKU  | Item                     | Qty | Price  | Subtotal |
      | SKU1 | 400-Watt Bulb Work Light | 5   | $2.00  | $10.00   |
    And records in "First Checkout Shipping Grid" should be 1
    And I should see an "Phones Checkout Category Name" element
    And I should see following "Second Checkout Shipping Grid" grid:
      | SKU  | Item      | Qty | Price  | Subtotal |
      | SKU2 | iPhone 13 | 10  | $2.00  | $20.00   |
      | SKU3 | iPhone X  | 10  | $2.00  | $20.00   |
    And records in "Second Checkout Shipping Grid" should be 2
    And I should see Checkout Totals with data:
      | Subtotal | $50.00 |
      | Shipping | $9.00  |

  Scenario: Follow to other page and return back to checkout.
    When I go to homepage
    And I open page with shopping list List 1
    And I click "Create Order"
    Then Page title equals to "Payment - Checkout"
    And I should see following "First Checkout Shipping Grid" grid:
      | SKU  | Item                     | Qty | Price  | Subtotal |
      | SKU1 | 400-Watt Bulb Work Light | 5   | $2.00  | $10.00   |
    And records in "First Checkout Shipping Grid" should be 1
    And I should see an "Phones Checkout Category Name" element
    And I should see following "Second Checkout Shipping Grid" grid:
      | SKU  | Item      | Qty | Price  | Subtotal |
      | SKU2 | iPhone 13 | 10  | $2.00  | $20.00   |
      | SKU3 | iPhone X  | 10  | $2.00  | $20.00   |
    And records in "Second Checkout Shipping Grid" should be 2
    And I should see Checkout Totals with data:
      | Subtotal | $50.00 |
      | Shipping | $9.00  |

  Scenario: Make changes in shopping list and checkout should start from the first step.
    When I go to homepage
    And I open page with shopping list List 1
    And I should see following "Multi Shipping Shopping List" grid:
      | SKU  | Item                                | Price  | Subtotal |
      | SKU1 | 400-Watt Bulb Work Light            | $2.00  | $10.00   |
      | SKU2 | iPhone 13                           | $2.00  | $20.00   |
      | SKU3 | iPhone X                            | $2.00  | $20.00   |
      | SKU4 | Round Meeting Table, 30 in. x 30in. |        |          |
    And I click on "Shopping List Line Item 1 Quantity"
    And I type "6" in "Shopping List Line Item 1 Quantity Input"
    And I click on "Shopping List Line Item 1 Save Changes Button"
    And I click "Create Order"
    Then Page title equals to "Billing Information - Checkout"
    And I click "Continue"
    Then Page title equals to "Shipping Information - Checkout"
    And I click "Continue"
    Then Page title equals to "Shipping Method - Checkout"
    And I should see an "Lighting Products Checkout Category Name" element
    And I should see following "First Checkout Shipping Grid" grid:
      | SKU  | Item                     | Qty | Price  | Subtotal | Shipping                            |
      | SKU1 | 400-Watt Bulb Work Light | 6   | $2.00  | $12.00   | Flat Rate 2: $0.00 Flat Rate: $3.00 |
    And records in "First Checkout Shipping Grid" should be 1
    And I should see an "Phones Checkout Category Name" element
    And I should see following "Second Checkout Shipping Grid" grid:
      | SKU  | Item      | Qty | Price  | Subtotal | Shipping                            |
      | SKU2 | iPhone 13 | 10  | $2.00  | $20.00   | Flat Rate 2: $0.00 Flat Rate: $3.00 |
      | SKU3 | iPhone X  | 10  | $2.00  | $20.00   | Flat Rate 2: $0.00 Flat Rate: $3.00 |
    And records in "Second Checkout Shipping Grid" should be 2
    When I click on "Second Line Item Flat Rate Shipping Method"
    And I click on "Third Line Item Flat Rate Shipping Method"
    Then I should see Checkout Totals with data:
      | Subtotal | $52.00 |
      | Shipping | $6.00  |
    When I click "Continue"
    Then Page title equals to "Payment - Checkout"
    And I should see following "First Checkout Shipping Grid" grid:
      | SKU  | Item                     | Qty | Price  | Subtotal |
      | SKU1 | 400-Watt Bulb Work Light | 6   | $2.00  | $12.00   |
    And records in "First Checkout Shipping Grid" should be 1
    And I should see an "Phones Checkout Category Name" element
    And I should see following "Second Checkout Shipping Grid" grid:
      | SKU  | Item      | Qty | Price  | Subtotal |
      | SKU2 | iPhone 13 | 10  | $2.00  | $20.00   |
      | SKU3 | iPhone X  | 10  | $2.00  | $20.00   |
    And records in "Second Checkout Shipping Grid" should be 2
    And I should see Checkout Totals with data:
      | Subtotal | $52.00 |
      | Shipping | $6.00  |
    And I click "Continue"
    Then Page title equals to "Order Review - Checkout"

  Scenario: Follow to order history page and return back to checkout.
    When I open Order History page on the store frontend
    Then I should see following "Open Orders Grid" grid:
      | Started From |
      | List 1       |
    And I click "Check Out" on row "List 1" in grid "OpenOrdersGrid"
    Then Page title equals to "Order Review - Checkout"
    And I should see following "First Checkout Shipping Grid" grid:
      | SKU  | Item                     | Qty | Price  | Subtotal |
      | SKU1 | 400-Watt Bulb Work Light | 6   | $2.00  | $12.00   |
    And records in "First Checkout Shipping Grid" should be 1
    And I should see an "Phones Checkout Category Name" element
    And I should see following "Second Checkout Shipping Grid" grid:
      | SKU  | Item      | Qty | Price  | Subtotal |
      | SKU2 | iPhone 13 | 10  | $2.00  | $20.00   |
      | SKU3 | iPhone X  | 10  | $2.00  | $20.00   |
    And records in "Second Checkout Shipping Grid" should be 2
    And I should see Checkout Totals with data:
      | Subtotal | $52.00 |
      | Shipping | $6.00  |

  Scenario: Finish checkout and ensure suborders created.
    When I click "Submit Order"
    Then I should see "Thank You For Your Purchase!"
    When I open Order History page on the store frontend
    Then I should see following "Past Orders Grid" grid:
      | Order Number |
      | 1-2          |
      | 1-1          |
      | 1            |
    And records in "Past Orders Grid" should be 3
    When I proceed as the Admin
    And I login as administrator
    And I go to Sales/Orders
    Then I should see following grid:
      | Order Number | Owner    |
      | 1            | John Doe |
      | 1-1          | John Doe |
      | 1-2          | John Doe |
    And number of records should be 3
    When I click View "$58.00" in grid
    Then I should see "Sub-Orders"
    When I scroll to "SubOrders Grid"
    Then I should see following "SubOrders Grid" grid:
      | Order Number | Total  |
      | 1-1          | $12.00 |
      | 1-2          | $46.00 |
