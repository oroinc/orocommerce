@regression
@ticket-BB-24332
@fixture-OroLocaleBundle:ZuluLocalization.yml

Feature: Check localization change for a non existent page
  As a user, I want to be able to switch localization on a non-existent page without errors.

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Add Zulu localization
    Given I proceed as the Admin
    And login as administrator
    And go to System / Configuration
    And follow "System Configuration/General Setup/Localization" on configuration sidebar
    When I fill form with:
      | Enabled Localizations | [English (United States), Zulu_Loc] |
      | Default Localization  | Zulu_Loc                            |
    And submit form
    Then I should see "Configuration saved" flash message

  Scenario: Change the translation
    Given I go to System / Configuration
    And go to System/Localization/Translations
    And filter Translated Value as is empty
    And filter Key as is equal to "oro_frontend.exception.code.404"
    And edit "oro_frontend.exception.code.404" Translated Value as "Page not found in Zulu"

  Scenario: Checking localization for the 404 page
    Given I proceed as the Buyer
    And I am on homepage
    And click "Localization Switcher"
    And select "English (United States)" localization

    When I am on "/not_found_page"
    Then I should see "The page you requested could not be found. Please make sure the path you used is correct."

    When I click "Localization Switcher"
    And select "Zulu" localization
    Then I should see "Page not found in Zulu"

