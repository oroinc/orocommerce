@feature-BB-26023-enabled
@regression
@fixture-OroOrderBundle:OrderPriceOverriddenOnCustomerChange.yml

Feature: Price overridden hint in the order line items grid - Order Draft Edit Mode

  Scenario: Enable Order Draft Edit Mode
    Given I set configuration property "oro_order.enable_order_draft_edit_mode" to "1"

  Scenario: Enable required currencies
    When I login as administrator
    And I go to System/Configuration
    And I follow "Commerce/Catalog/Pricing" on configuration sidebar
    And fill "Pricing Form" with:
      | Enabled Currencies System | false                     |
      | Enabled Currencies        | [US Dollar ($), Euro (€)] |
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Price overridden hint appears after Currency is changed
    When I go to Sales / Orders
    And I click "Create Order"
    And I fill "Order Form" with:
      | Customer | first customer |
    And I fill "Order Line Item Draft Create Form" with:
      | Product | PSKU1 |
    And I click on "Order Line Item Draft Create Form Add Product"
    Then I should see following grid:
      | SKU   | Product   | Price  |
      | PSKU1 | Product 1 | $13.00 |

    When I fill "Order Form" with:
      | Currency | Euro (€) |
    Then I should see "Prices for line items may have changed. Please review the prices before saving the order." flash message and I close it
    And I should see an "Order Line Item Tier Prices Hint" element
    When I click on "Order Line Item Tier Prices Hint"
    Then I should see "Price is overridden"
    And I should see "€10.00"

  Scenario: Price overridden hint appears after Customer is changed
    When I fill "Order Form" with:
      | Currency | US Dollar ($)  |
    Then I should see "Prices for line items may have changed. Please review the prices before saving the order." flash message and I close it
    And I should not see an "Order Line Item Tier Prices Hint" element

    When I fill "Order Form" with:
      | Customer | Wholesaler B |
    Then I should see "Prices for line items may have changed. Please review the prices before saving the order." flash message and I close it
    And I should see an "Order Line Item Tier Prices Hint" element
    When I click on "Order Line Item Tier Prices Hint"
    Then I should see "Price is overridden"
    And I should see "$11.00"
