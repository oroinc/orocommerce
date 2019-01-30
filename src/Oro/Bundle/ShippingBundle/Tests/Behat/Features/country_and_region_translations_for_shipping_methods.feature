@fixture-OroLocaleBundle:ZuluLocalization.yml
@fixture-OroAddressBundle:CountryNameTranslation.yml
@fixture-OroCustomerBundle:LoadCustomerCustomerUserEntitiesFixture.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroShippingBundle:LoadShippingRulesEntitiesFixture.yml
Feature: Country and region translations for Shipping methods
  In order to manage Shipping methods
  As a Administrator
  I want to see translated country and region names in UI

  Scenario: Feature Background
    Given I login as administrator
    And I go to System / Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill form with:
      | Enabled Localizations | [English, Zulu_Loc] |
      | Default Localization  | Zulu_Loc            |
    And I submit form
    When I go to System / Localization / Translations
    And I click "Update Cache"
    Then I should see "Translation Cache has been updated" flash message

  Scenario: Check Shipping methods UI
    Given go to System/ Shipping Rules
    And there is two records in grid
    Then should see following grid:
      | Name          | Destinations                         |
      | shippingRule1 | FloridaZulu, United StatesZulu 10001 |
      | shippingRule2 | BerlinZulu, GermanyZulu 10002        |
    When click edit "shippingRule1" in grid
    And fill form with:
      | Country         | GermanyZulu |
      | State           | BerlinZulu  |
      | Zip/Postal Code | 10003       |
    And save and close form
    And go to System/ Shipping Rules
    Then should see following grid:
      | Name          | Destinations                  |
      | shippingRule1 | BerlinZulu, GermanyZulu 10003 |
      | shippingRule2 | BerlinZulu, GermanyZulu 10002 |
