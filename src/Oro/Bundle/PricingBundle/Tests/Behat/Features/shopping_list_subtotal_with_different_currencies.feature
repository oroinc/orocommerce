@regression
@fixture-OroPricingBundle:ProductPricesWithDifferentCurrencies.yml

Feature: Shopping list subtotal with different currencies
  In order to use shopping list in the selected currency
  As a Buyer
  I want to see the subtotals in the shopping list in correct currency

  Scenario: Create different window session
    Given sessions active:
      | Admin     | first_session  |
      | Buyer     | second_session |

  Scenario: Check subtotal currency after user switches to second currency
    Given I proceed as the Admin
    And I login as administrator
    And I go to System/Configuration
    And I follow "Commerce/Catalog/Pricing" on configuration sidebar
    And fill "Pricing Form" with:
      | Enabled Currencies System | false                     |
      | Enabled Currencies        | [US Dollar ($), Euro (€)] |
    And click "Save settings"
    And I should see "Configuration saved" flash message
    Then I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    And type "PSKU1" in "search"
    And I click "Search Button"
    And I click "Add to Shopping List" for "PSKU1" product
    And I follow "Shopping List" link within flash message "Product has been added to \"Shopping list\""
    Then I should see "Subtotal $13.00"
    And I should see "Total $13.00"
    Then I am on homepage
    And I click "Currency Switcher"
    And I click "Euro"
    And I open shopping list widget
    And I click "Shopping List" on shopping list widget
    Then I should not see "Subtotal $13.00"
    And I should not see "Total $13.00"
    And I should see "Subtotal €10.00"
    And I should see "Total €10.00"
    Then I click "Currency Switcher"
    And I click "US Dollar"

  Scenario: Check subtotal currency after disabling first currency
    Given I proceed as the Admin
    And I go to System/Configuration
    And I follow "Commerce/Catalog/Pricing" on configuration sidebar
    And fill "Pricing Form" with:
      | Enabled Currencies | Euro (€) |
      | Default Currency   | Euro (€) |
    And click "Save settings"
    And I should see "Configuration saved" flash message
    Then I proceed as the Buyer
    Then I am on homepage
    And I open shopping list widget
    And I click "Shopping List" on shopping list widget
    Then I should not see "Subtotal $13.00"
    And I should not see "Total $13.00"
    And I should see "Subtotal €10.00"
    And I should see "Total €10.00"

