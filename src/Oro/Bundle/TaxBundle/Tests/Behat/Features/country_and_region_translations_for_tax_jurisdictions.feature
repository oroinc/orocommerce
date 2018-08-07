@fixture-OroLocaleBundle:ZuluLocalization.yml
@fixture-OroAddressBundle:CountryNameTranslation.yml
@fixture-OroTaxBundle:LoadTaxJurisdictionsEntitiesFixture.yml
Feature: Country and region translations for tax jurisdictions
  In order to manage Tax Jurisdictions
  As a Administrator
  I want to see translated country and region names in UI

  Scenario: Feature Background
    Given I login as administrator
    And I go to System / Configuration
    And I follow "System Configuration/General Setup/Language Settings" on configuration sidebar
    And I fill form with:
      | Supported Languages | [English, Zulu] |
      | Use Default         | false           |
      | Default Language    | Zulu            |
    And I submit form
    When I go to System / Localization / Translations
    And I click "Update Cache"
    Then I should see "Translation Cache has been updated" flash message

  Scenario: Check tax jurisdictions UI
    Given go to Taxes/ Tax Jurisdictions
    And click edit "tazJurisdiction" in grid
    When fill form with:
      | Country | GermanyZulu |
      | State   | BerlinZulu  |
    And save and close form
    Then I should see TaxJurisdiction with:
      | Code        | tazJurisdiction             |
      | Description | tazJurisdiction description |
      | State       | BerlinZulu                  |
      | Country     | GermanyZulu                 |
