@regression
@ticket-BB-14735
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
@fixture-OroLocaleBundle:ZuluLocalization.yml

Feature: Translation cache for multi-language application
  In order to use multi-language application
  As a Buyer
  I want to see appropriate translations for each language without cache issues

  Scenario: Feature Background
    Given I login as administrator
    And I go to System/Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    When fill form with:
      | Enabled Localizations | [English (United States), Zulu] |
      | Default Localization  | English (United States)         |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Check Zulu on the storefront
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I click "Localization Switcher"
    When I select "Zulu" localization
    Then I should see that the page does not contain untranslated labels
