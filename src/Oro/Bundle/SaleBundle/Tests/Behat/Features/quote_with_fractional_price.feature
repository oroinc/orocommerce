@ticket-BB-14800
@fixture-OroPricingBundle:FractionalProductPrices.yml

Feature: Quote with fractional price
  In order to use correct decimal separator for fractional prices in different locales
  As an Administrator
    I want to have ability to use fractional prices with appropriate decimal separator for create and edit Quotes in different locales.
  Scenario: Feature Background
    Given I login as administrator
    When I go to System/Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill "Configuration Localization Form" with:
      | Locale Use Default | false            |
      | Locale             | German (Germany) |
    And I click "Save settings"
    And I should see "Configuration saved" flash message

  Scenario: Create and view Quote with fractional price
    Given I go to Sales/Quotes

    When I click "Create Quote"
    And I fill "Quote Form" with:
      | LineItemProduct | psku1 |
    And I click "Tier prices button"
    Then I should see "Click to select price per unit"
    And I should see "10,99 $"

    When I click "10,99"
    Then "Quote Line Items" must contains values:
      | Unit Price | 10,99 |
    When I click "Save and Close"
    And I click "Save" in modal window
    Then I should see "Quote has been saved" flash message
    And I should see "10,99 $"
