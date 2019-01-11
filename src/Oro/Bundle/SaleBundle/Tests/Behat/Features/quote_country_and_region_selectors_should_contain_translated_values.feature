@ticket-BAP-17369
@fixture-OroLocaleBundle:ZuluLocalization.yml
@fixture-OroAddressBundle:CountryNameTranslation.yml
@fixture-OroSaleBundle:Quote.yml
@fixture-OroSaleBundle:QuoteProductFixture.yml

Feature: Quote Country and region selectors should contain translated values
  In order to manage quotes
  As an Administrator
  I want to to see correctly translated names of country and region during creation and editing of quote

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

  Scenario: Create Quote - Country/region selector should contain translated values
    Given I go to Sales/Quotes
    When I click "Create Quote"
    And I fill "Quote Form" with:
      | LineItemProduct | psku1       |
      | Country         | GermanyZulu |
      | State           | BerlinZulu  |
    And I click "Save and Close"
    And I click "Save" in modal window
    Then I should see "Quote has been saved" flash message
    And I should see Quote with:
      | State           | BerlinZulu  |
      | Country         | GermanyZulu |

  Scenario: Edit Quote - Country/region selector should contain translated values
    Given I go to Sales/Quotes
    When I click Edit Quote1 in grid
    And I fill "Quote Form" with:
      | LineItemProduct | psku1       |
      | Country         | GermanyZulu |
      | State           | BerlinZulu  |
    And I click "Submit"
    And I click "Save" in modal window
    Then I should see "Quote #Quote1 successfully updated" flash message
    When I click View Quote1 in grid
    And I should see Quote with:
      | State           | BerlinZulu  |
      | Country         | GermanyZulu |
