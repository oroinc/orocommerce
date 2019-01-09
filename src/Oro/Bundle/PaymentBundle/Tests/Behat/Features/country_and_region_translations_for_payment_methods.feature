@fixture-OroLocaleBundle:ZuluLocalization.yml
@fixture-OroAddressBundle:CountryNameTranslation.yml
@fixture-OroCustomerBundle:LoadCustomerCustomerUserEntitiesFixture.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroPaymentBundle:LoadPaymentRulesEntitiesFixture.yml
Feature: Country and region translations for Payment methods
  In order to manage Payment methods
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

  Scenario: Check Payment methods UI
    Given go to System/ Payment Rules
    And there is two records in grid
    Then should see following grid:
      | Name         | Destinations                         |
      | paymentRule1 | FloridaZulu, United StatesZulu 10001 |
      | paymentRule2 | BerlinZulu, GermanyZulu 10002        |
    When click edit "paymentRule2" in grid
    And fill form with:
      | Country      | United StatesZulu |
      | State        | FloridaZulu       |
      | Postal Codes | 10003             |
    And save and close form
    And go to System/ Payment Rules
    Then should see following grid:
      | Name         | Destinations                         |
      | paymentRule1 | FloridaZulu, United StatesZulu 10001 |
      | paymentRule2 | FloridaZulu, United StatesZulu 10003 |
