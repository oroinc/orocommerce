@fixture-OroLocaleBundle:ZuluLocalization.yml
@fixture-OroAddressBundle:CountryNameTranslation.yml
@fixture-OroTaxBundle:LoadTaxEntitiesFixture.yml
Feature: Country and region translations for tax jurisdictions
  In order to manage Tax Jurisdictions
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

  Scenario: Check tax jurisdictions UI
    Given go to Taxes/ Tax Jurisdictions
    And click edit "tazJurisdiction" in grid
    When I fill "Tax Jurisdiction Form" with:
      | Country | GermanyZulu |
      | State   | BerlinZulu  |
    And save and close form
    Then I should see TaxJurisdiction with:
      | Code        | tazJurisdiction             |
      | Description | tazJurisdiction description |
      | State       | BerlinZulu                  |
      | Country     | GermanyZulu                 |

  Scenario: Check tax jurisdiction on "Create Tax Rule" page:
    Given I go to Taxes/ Tax Rules
    When I click "Create Tax Rule"
    And I fill "Tax Rule Form" with:
        | Customer Tax Code | customerTaxCode1 |
        | Product Tax Code  | productTaxCode1  |
        | Tax               | tax1             |
    And I click on "Tax Jurisdiction create new"
    And I click "maximize"
    And I fill "Tax Jurisdiction Form" with:
      | Code       | test_tax_jurisdiction |
      | Country    | GermanyZulu           |
      | State      | BerlinZulu            |
    And I click "Save" in modal window
    Then I should see "Saved successfully" flash message
    When I click on "Tax Jurisdiction hamburger"
    Then I should see following grid:
      | Code                  |
      | tazJurisdiction       |
      | test_tax_jurisdiction |
    When I click on test_tax_jurisdiction in grid
    And I save and close form
    Then I should see "Tax Rule has been saved"

  Scenario: Check tax jurisdiction on "Tax Rule Update" page:
    Given I go to Taxes/ Tax Rules
    When I click edit "test_tax_jurisdiction" in grid
    And I click on "Tax Jurisdiction create new"
    And I click "maximize"
    And I fill "Tax Jurisdiction Form" with:
      | Code       | another_test_tax_jurisdiction |
      | Country    | United StatesZulu             |
      | State      | FloridaZulu                   |
    And I click "Save" in modal window
    Then I should see "Saved successfully" flash message
    When I click on "Tax Jurisdiction hamburger"
    Then I should see following grid:
      | Code                          |
      | tazJurisdiction               |
      | test_tax_jurisdiction         |
      | another_test_tax_jurisdiction |
    When I click on test_tax_jurisdiction in grid
    And I save and close form
    Then I should see "Tax Rule has been saved"
