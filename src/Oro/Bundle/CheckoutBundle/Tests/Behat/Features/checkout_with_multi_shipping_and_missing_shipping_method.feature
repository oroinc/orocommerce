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

Feature: Checkout With Multi Shipping And Missing Shipping Method
  In order to control shipping methods for each order line item
  As a Customer User
  I want to see shipping method selection for each line item in checkout

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I change configuration options:
      | oro_checkout.enable_shipping_method_selection_per_line_item | true |

  Scenario: Set stricter rule for the shipping method
    Given I proceed as the Admin
    And I login as administrator
    When I go to System/Shipping Rules
    And I click Edit "Default" in grid
    And fill "Shipping Rule" with:
      | Expression | subtotal.value >= 12 |
    And I save and close form
    Then I should see "Shipping rule has been saved" flash message

  Scenario: Checkout with shipping method selection per line item and missing shipping method
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
    And I should not see an "Lighting Products Checkout Category Name" element
    And I should see following "MultiShipping Checkout Line Items Grid" grid:
      | SKU  | Item                     | Qty | Price  | Subtotal | Shipping                            |
      | SKU1 | 400-Watt Bulb Work Light | 5   | $2.00  | $10.00   |                                     |
      | SKU2 | iPhone 13                | 10  | $2.00  | $20.00   | Flat Rate 2: $0.00 Flat Rate: $3.00 |
      | SKU3 | iPhone X                 | 10  | $2.00  | $20.00   | Flat Rate 2: $0.00 Flat Rate: $3.00 |
    And records in "MultiShipping Checkout Line Items Grid" should be 3
    And I should see notification "This product will be available later" for "SKU1" line item "Checkout Line Item"
    And I should see notification "This product will be available later" for "SKU3" line item "Checkout Line Item"
    And I should not see an "Phones Checkout Category Name" element
    When I click "Continue"
    Then Page title equals to "Shipping Method - Checkout"
    And I should see "The selected shipping method is not available. Please return to the shipping method selection step and select a different one."
