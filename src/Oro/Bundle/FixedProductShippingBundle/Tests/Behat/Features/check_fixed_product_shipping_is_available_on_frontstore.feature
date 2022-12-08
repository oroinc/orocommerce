@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroFixedProductShippingBundle:FixedProductIntegration.yml
@fixture-OroFixedProductShippingBundle:Checkout.yml
@fixture-OroCheckoutBundle:InventoryLevel.yml

Feature: Check Fixed Product Shipping is available on frontstore

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Add Shipping Cost for product
    Given I proceed as the Admin
    And login as administrator
    And I go to Products/ Products
    When I check "USD"
    And I should see "Shipping Cost (USD)" column in grid
    And click edit "SKU123" in grid
    When I click "Shipping Options"
    Then I should see "Shipping Cost"
    And fill "Shipping Cost Attribute Product Form" with:
      | Shipping Cost USD | 5.44 |
      | Shipping Cost EUR | 5.44 |
    When I save and close form
    Then I should see "Product has been saved" flash message
    When I click "Shipping Options"
    Then I should see following "Shipping Cost Attribute Grid" grid:
      | UNIT | EUR  | USD  |
      | item | 5.44 | 5.44 |
    And I go to Products/ Products
    When I check "USD"
    Then I should see following grid:
      | Shipping Cost (USD) |
      | Item $5.44          |

  Scenario: Check availability Fixed Product Shipping Integration on checkout create page
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I scroll to top
    And I wait line items are initialized
    And I click "Create Order"
    And I click "Ship to This Address"
    And I click "Continue"
    And I should see "Fixed Product: $28.20"
    And I check "Fixed Product" on the "Shipping Method" checkout step and press Continue
    And on the "Payment" checkout step I press Continue
    And I should see "Shipping $28.20"
