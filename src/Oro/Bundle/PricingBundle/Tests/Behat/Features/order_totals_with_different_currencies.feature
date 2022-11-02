@regression
@ticket-BB-15521
@fixture-OroPricingBundle:Order.yml

Feature: Order totals with different currencies
  In order to check my expenses
  As a Buyer
  I want to see order totals in the correct currency

  Scenario: Create different window session
    Given sessions active:
      | Admin     | first_session  |
      | Buyer     | second_session |

  Scenario: Check totals currency after user switches to second currency
    Given I proceed as the Admin
    And I login as administrator
    And I go to System/Configuration
    And I follow "Commerce/Catalog/Pricing" on configuration sidebar
    And fill "Pricing Form" with:
      | Enabled Currencies System | false                     |
      | Enabled Currencies        | [US Dollar ($), Euro (€)] |
    And click "Save settings"
    Then I should see "Configuration saved" flash message
    When I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    And I click "Order"
    And I click view "SimpleOrder" in grid
    Then I should see "Subtotal $5.00"
    And I should see "Discount $0.00"
    And I should see "Shipping $0.00"
    And I should see "Shipping Discount $0.00"
    And I should see "Tax $0.00"
    And I should see "Total $5.00"
    When I click "Currency Switcher"
    And I click "Euro"
    Then I should see "Subtotal $5.00"
    And I should see "Discount $0.00"
    And I should see "Shipping $0.00"
    And I should see "Shipping Discount $0.00"
    And I should see "Tax $0.00"
    And I should see "Total $5.00"
