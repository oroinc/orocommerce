@ticket-BB-15796
@fixture-OroPricingBundle:PriceListsWithPrices.yml
@fixture-OroLocaleBundle:LocalizationFixture.yml

Feature: Product prices localized on price list view page
  In order to have prices displayed correctly on price list view page
  As an Administrator
  I want to see datagrid with correctly formatted prices on price list view page

  Scenario: Feature Background
    Given I login as administrator
    And I go to System/Configuration
    And follow "System Configuration/General Setup/Localization" on configuration sidebar
    When fill "Configuration Localization Form" with:
      | Enabled Localizations | German Localization |
      | Default Localization  | German Localization |
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check product prices are localized on price list view page
    Given I go to Sales/ Price Lists
    When I click view First Price List in grid
    Then I should see following "Price list Product prices Grid" grid:
      | Product SKU | Product name | Quantity | Unit  | Value | Currency | Type   |
      | PSKU1       | Product 1    | 5        | item  | 15,00 | USD      | Manual |
      | PSKU2       | Product 2    | 10       | piece | 30,00 | USD      | Manual |
