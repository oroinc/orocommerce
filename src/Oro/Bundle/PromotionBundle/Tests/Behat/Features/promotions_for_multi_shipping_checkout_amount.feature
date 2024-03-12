@ticket-BB-22722
@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:CheckoutCustomerFixture.yml
@fixture-OroCheckoutBundle:ProductsAndCategoriesForMultiShippingFixture.yml
@fixture-OroCheckoutBundle:ShoppingListForMultiShippingFixture.yml
@fixture-OroPromotionBundle:promotions-multishipping-line-items-discounts.yml
@fixture-OroPromotionBundle:promotions-with-coupon-multishipping-order-discount.yml
@fixture-OroPromotionBundle:promotions-multishipping-shipping-discounts-amount.yml

Feature: Promotions for multi shipping checkout amount
  In order to use discounts
  As a Buyer
  I need discounts to be applied to a multi shipping order (with fixed amount shipping discount)

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I change configuration options:
      | oro_checkout.enable_shipping_method_selection_per_line_item | true             |
      | oro_checkout.enable_line_item_grouping                      | true             |
      | oro_checkout.group_line_items_by                            | product.category |

  Scenario: Start checkout and check line items discount applied only for the "Lighting products" group.
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
    And I should see Checkout Totals with data:
      | Subtotal | $50.00 |

  Scenario: Apply coupon and check discount applied to the "Phones" group of line items.
    Given I scroll to "I have a Coupon Code"
    And I click "I have a Coupon Code"
    And I type "multi-shipping-coupon" in "CouponCodeInput"
    And I click "Apply"
    Then I should see "Coupon code has been applied successfully, please review discounts" flash message
    And I should see Checkout Totals with data:
      | Subtotal | $50.00 |
      | Discount | -$5.00 |
    And I click "Continue"
    Then Page title equals to "Shipping Information - Checkout"
    And I click "Continue"
    Then Page title equals to "Shipping Method - Checkout"
    And I should see Checkout Totals with data:
      | Subtotal          | $50.00 |
      | Discount          | -$5.00 |
      | Shipping          | $9.00  |
      | Shipping Discount | -$2.00 |
    When I click "Continue"
    Then Page title equals to "Payment - Checkout"
    And I click "Continue"
    Then Page title equals to "Order Review - Checkout"
    When I click "Submit Order"
    Then I should see "Thank You For Your Purchase!"

  Scenario: Check discounts in created order and sub orders on order history page.
    Given I open Order History page on the store frontend
    Then I should see following "Past Orders Grid" grid:
      | Order Number | Total  |
      | 1            | $52.00 |
    And records in "Past Orders Grid" should be 1
    And I click view "1" in grid
    Then I should see "Subtotal $50.00" in the "Subtotals" element
    And I should see "Discount -$5.00" in the "Subtotals" element
    And I should see "Shipping $9.00" in the "Subtotals" element
    And I should see "Shipping Discount -$2.00" in the "Subtotals" element
    And I should see "Tax $0.00" in the "Subtotals" element
    And I should see "Total $52.00" in the "Subtotals" element

  Scenario: Check discounts in created order and sub orders in admin
    Given I proceed as the Admin
    And I login as administrator
    And I go to Sales/Orders
    Then I should see following grid:
      | Order Number | Total  |
      | 1            | $52.00 |
    And number of records should be 1
    When I click view "$52.00" in grid
    And I click "Promotions and Discounts"
    Then I should see following rows in "Promotions" table
      | Code                  | Promotion                               | Type        | Status | Discount |
      |                       | Shipping Discount                       | Shipping    | Active | -$2.00   |
      | multi-shipping-coupon | MultiShippingOrderDiscountPromotionRule | Order Total | Active | -$5.00   |
    And I see following subtotals for "Backend Order":
      | Subtotal          | Amount |
      | Subtotal          | $50.00 |
      | Discount          | -$5.00 |
      | Shipping          | $9.00  |
      | Shipping Discount | -$2.00 |
      | Total             | $52.00 |
