@regression
@ticket-BB-20557
@fixture-OroPricingBundle:ProductPricesWithMultipleCurrencies.yml

Feature: Backoffice Quote Create Product offer with price in different currencies
  In order to create a quote
  As an administrator
  I should be able to add product offer using prices in different currencies

  Scenario: Create window sessions
    Given sessions active:
      | Admin   | first_session  |
    And I proceed as the Admin
    And I login as administrator

  Scenario: Enable required currencies
    When I go to System/Configuration
    And I follow "Commerce/Catalog/Pricing" on configuration sidebar
    When fill "Pricing Form" with:
      | Enabled Currencies System | false                     |
      | Enabled Currencies        | [US Dollar ($), Euro (€)] |
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check price suggestion for a quote
    Then I go to Sales / Quotes
    And I click "Create Quote"
    And I fill "Quote Form" with:
      | LineItemProduct | PSKU1 |
    And I click "Tier prices button"
    Then I should see "Click to select price per unit"
    And I should see "$13.00"
    When I fill "Quote Form" with:
      | LineItemCurrency | € |
    And I click "Tier prices button"
    Then I should see "Click to select price per unit"
    And I should see "€10.00"
    And I should not see "$13.00"
