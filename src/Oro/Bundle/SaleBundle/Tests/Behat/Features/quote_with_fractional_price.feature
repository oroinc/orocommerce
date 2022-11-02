@ticket-BB-14800
@ticket-BB-16295
@fixture-OroPricingBundle:FractionalProductPrices.yml
@fixture-OroLocaleBundle:GermanLocalization.yml

Feature: Quote with fractional price
  In order to use correct decimal separator for fractional prices in different locales
  As an Administrator
  I want to have ability to use fractional prices with appropriate decimal separator for create and edit Quotes in different locales.

  Scenario: Feature Background
    Given I enable the existing localizations
    And I login as administrator
    And I go to System/Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill "Configuration Localization Form" with:
      | Enabled Localizations | German_Loc |
      | Default Localization  | German_Loc |
    When I click "Save settings"
    Then I should see "Configuration saved" flash message

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

  Scenario: Edit and view Quote with fractional price
    When I click "Edit"
    And I fill "Quote Form" with:
      | LineItemPrice | 14.503,99 |
    When I click "Submit"
    And I click "Save" in modal window
    Then I should see "Quote #1 successfully updated" flash message
    And I should see "14.503,99 $"

    When I click "Edit"
    Then "Quote Form" must contains values:
      | LineItemPrice | 14.503,9900 |
