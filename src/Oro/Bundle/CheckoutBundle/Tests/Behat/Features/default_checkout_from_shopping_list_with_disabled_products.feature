@ticket-BB-24149
@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroCheckoutBundle:InventoryLevel.yml

Feature: Default checkout from shopping list with disabled products
  As an administrator I want to make sure that I cannot create an order if the products are disabled.

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Start creating order
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I open page with shopping list List 1
    And I click "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue

  Scenario: Disable product
    Given I proceed as the Admin
    And login as administrator
    When I go to Products/Products
    And click edit SKU123 in grid
    And fill "ProductForm" with:
      | Status | Disabled |
    And save and close form
    Then I should see "Product has been saved" flash message

  Scenario:
    Given I proceed as the Buyer
    When I reload the page
    Then I should see "No products can be added to this order." flash message
    And on the "Order Review" checkout step I go back to "Edit Payment"
    And should see "No products can be added to this order." flash message
    And on the "Payment" checkout step I go back to "Edit Shipping Method"
    And should see "No products can be added to this order." flash message
    And on the "Shipping Method" checkout step I go back to "Edit Shipping Information"
    And should see "No products can be added to this order." flash message
    And on the "Shipping Information" checkout step I go back to "Edit Billing Information"
    And should see "No products can be added to this order." flash message
