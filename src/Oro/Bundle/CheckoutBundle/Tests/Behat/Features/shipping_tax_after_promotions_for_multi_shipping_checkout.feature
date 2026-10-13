@regression
@ticket-BB-23754
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:multi_shipping_shipping_tax.yml
@fixture-OroCheckoutBundle:ProductsAndCategoriesForMultiShippingFixture.yml
@fixture-OroCheckoutBundle:ShoppingListForMultiShippingFixture.yml
@fixture-OroPromotionBundle:promotions-multishipping-shipping-discounts-amount.yml

Feature: Shipping tax after promotions for multi shipping checkout
  In order to see the correct total before submitting a multi shipping order
  As a buyer
  I need shipping tax to account for shipping promotions applied to suborders

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I change configuration options:
      | oro_checkout.enable_shipping_method_selection_per_line_item | true                    |
      | oro_checkout.enable_line_item_grouping                      | true                    |
      | oro_checkout.group_line_items_by                            | product.category        |
      | oro_checkout.create_suborders_for_each_group                | true                    |
      | oro_tax.tax_enable                                          | true                    |
      | oro_tax.use_as_base_by_default                              | destination             |
      | oro_tax.shipping_rates_include_tax                          | false                   |
      | oro_tax.calculate_taxes_after_promotions                    | false                   |

  Scenario: Configure shipping tax code
    Given I proceed as the Admin
    And I login as administrator
    And I go to System/Configuration
    And I follow "Commerce/Taxation/Shipping" on configuration sidebar
    And I uncheck "Use default" for "Tax Code" field
    And I fill "Tax Shipping Form" with:
      | Tax Code | MULTI_SHIPPING_SHIPPING |
    When I save form
    Then I should see "Configuration saved" flash message

  Scenario: Calculate shipping tax before promotions on the Shipping Method step
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I open page with shopping list List 1
    Then I should see following "Multi Shipping Shopping List" grid:
      | SKU  | Product                             | Price | Subtotal |
      | SKU1 | 400-Watt Bulb Work Light            | $2.00 | $10.00   |
      | SKU2 | iPhone 13                           | $2.00 | $20.00   |
      | SKU3 | iPhone X                            | $2.00 | $20.00   |
      | SKU4 | Round Meeting Table, 30 in. x 30in. | $0.00 | $0.00    |
    When I click "Create Order"
    Then Page title equals to "Billing Information - Checkout"
    When I click "Continue"
    Then Page title equals to "Shipping Information - Checkout"
    When I click "Continue"
    Then Page title equals to "Shipping Method - Checkout"
    And I click "Expand Checkout Footer"
    # Four line items cost $3 each to ship; two Phones line items receive a $1 shipping discount each.
    And I should see Checkout Totals with data:
      | Subtotal          | $50.00 |
      | Shipping          | $12.00 |
      | Shipping Discount | -$2.00 |
      | Tax               | $3.60  |
    And I should see "Total: $63.60"

  Scenario: Recalculate shipping tax after promotions before submitting the order
    Given I enable configuration options:
      | oro_tax.calculate_taxes_after_promotions |
    When I reload the page
    And I click "Expand Checkout Footer"
    # The taxable shipping amount must be $12 - $2 = $10, including discounts from suborders.
    Then I should see Checkout Totals with data:
      | Subtotal          | $50.00 |
      | Shipping          | $12.00 |
      | Shipping Discount | -$2.00 |
      | Tax               | $3.00  |
    And I should see "Total: $63.00"
    When I click "Continue"
    Then Page title equals to "Payment - Checkout"
    When I click "Continue"
    Then Page title equals to "Order Review - Checkout"
    And I click "Expand Checkout Footer"
    And I should see Checkout Totals with data:
      | Shipping          | $12.00 |
      | Shipping Discount | -$2.00 |
      | Tax               | $3.00  |
    And I should see "Total: $63.00"
    When I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Checkout totals match the created order and suborders
    Given I open Order History page on the store frontend
    Then I should see following "Past Orders Grid" grid:
      | Order Number | Total  |
      | 1-2          | $49.10 |
      | 1-1          | $13.90 |
      | 1            | $63.00 |
    And records in "Past Orders Grid" should be 3
    When I show filter "Order Number" in "Past Orders Grid" frontend grid
    And I filter Order Number as is equal to "1" in "Past Orders Grid"
    And I click view "1" in grid
    Then I should see "Shipping $12.00" in the "Subtotals" element
    And I should see "Shipping Discount -$2.00" in the "Subtotals" element
    And I should see "Tax $3.00" in the "Subtotals" element
    And I should see "Total $63.00" in the "Subtotals" element
    When I open Order History page on the store frontend
    And I filter Order Number as is equal to "1-1" in "Past Orders Grid"
    And I click view "1-1" in grid
    Then I should see "Shipping $3.00" in the "Subtotals" element
    And I should see "Shipping Discount $0.00" in the "Subtotals" element
    And I should see "Tax $0.90" in the "Subtotals" element
    When I open Order History page on the store frontend
    And I filter Order Number as is equal to "1-2" in "Past Orders Grid"
    And I click view "1-2" in grid
    Then I should see "Shipping $9.00" in the "Subtotals" element
    And I should see "Shipping Discount -$2.00" in the "Subtotals" element
    And I should see "Tax $2.10" in the "Subtotals" element

