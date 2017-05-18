@ticket-BAP-14671
@fixture-frontstore-localizations.yml
Feature: FrontStore language switcher
  In order to manage available localizations for language switcher
  As Administrator
  I need to enable Localizations at System Configuration
  As Frontend User
  I need to be able to switch between Localizations

  Scenario: Manage localizations
    Given I login as administrator
    And I go to System/Localization/Localizations
    And I click Edit Netherlands in grid
    And click "Fallback Status"
    When I fill "Localization Create Form" with:
      | Name                | Dutch |
      | Title Default Value | Dutch |
      | Title Use           | false |
      | Title English       | NL    |
    And I save and close form
    Then go to System/Localization/Localizations
    And I should see "Netherlands" in grid with following data:
      | Title               | Dutch                       |
      | Parent localization | N/A                         |
      | Language            | Dutch (Netherlands) - nl_NL |
      | Formatting          | Dutch (Netherlands) - nl_NL |

  Scenario: Enable Localizations at System Configuration
    Given I open Localization Config page
    And I fill "System Config Form" with:
      | Enabled Localizations | [English,  Dutch, Japanese] |
    And I save form
    Then Enabled Localizations field should has [English,  Dutch, Japanese] value

  Scenario: Verify Switcher for anonymous front-end user
    Given I am on homepage
    When I press "Localization Switcher"
    Then I should see that localization switcher contains localizations:
      | English  |
      | NL       |
      | Japanese |
    And I should see that "English" localization is active

    When I select "NL" localization
    And I press "Localization Switcher"
    Then I should see that localization switcher contains localizations:
      | English  |
      | Dutch    |
      | Japanese |
    And I should see that "Dutch" localization is active

    When I select "Japanese" localization
    And I press "Localization Switcher"
    Then I should see that localization switcher contains localizations:
      | English  |
      | Dutch    |
      | Japanese |
    And I should see that "Japanese" localization is active

  Scenario: Verify Switcher for logged in front-end user
    Given I signed in as AmandaRCole@example.org on the store frontend
    When I press "Localization Switcher"
    Then I should see that localization switcher contains localizations:
      | English  |
      | NL       |
      | Japanese |
    And I should see that "English" localization is active

    When I select "NL" localization
    And I press "Localization Switcher"
    Then I should see that localization switcher contains localizations:
      | English  |
      | Dutch    |
      | Japanese |
    And I should see that "Dutch" localization is active

    When I select "Japanese" localization
    And I press "Localization Switcher"
    Then I should see that localization switcher contains localizations:
      | English  |
      | Dutch    |
      | Japanese |
    And I should see that "Japanese" localization is active