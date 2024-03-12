@regression
@ticket-BAP-14671
@ticket-BB-14857
@fixture-OroFrontendLocalizationBundle:frontstore-customer.yml

@skip
# wil be fixed in BAP-22475
Feature: FrontStore language switcher
  In order to manage available localizations for language switcher
  As Administrator
  I need to enable Localizations at System Configuration
  As Frontend User
  I need to be able to switch between Localizations

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | User  | second_session |

  Scenario: Feature Background
    Given I proceed as the Admin
    And I login as administrator
    And I disable configuration options:
      | oro_frontend.guest_access_enabled |
    And go to System/ Localization/ Languages

  Scenario Outline: Add Languages
    And click "Add Language"
    And fill in "Language" with "<Language Full Name>"
    And click "Add Language" in modal window
    Then I should see "Language has been added" flash message
    And click "Enable" on row "<Language Short Name>" in grid
    Then I should see "Language has been enabled" flash message

    Examples:
      | Language Full Name          | Language Short Name |
      | Dutch (Netherlands) - nl_NL | Dutch               |
      | Japanese (Japan) - ja_JP    | Japanese            |

  Scenario Outline: Add Localizations
    Given I go to System/ Localization/ Localizations
    And click "Create Localization"
    And fill "Create Localization Form" with:
      | Name       | <Language Short Name> |
      | Title      | <Language Short Name> |
      | Language   | <Language Full Name>  |
      | Formatting | <Language Full Name>  |
    When I save and close form
    Then I should see "Localization has been saved" flash message

    Examples:
      | Language Full Name  | Language Short Name |
      | Dutch (Netherlands) | Dutch               |
      | Japanese (Japan)    | Japanese            |

  Scenario: Enable Localizations at System Configuration
    Given I go to System/Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill "System Config Form" with:
      | Enabled Localizations | [English (United States), Dutch, Japanese] |
    And I save form
    Then Enabled Localizations field should has [English (United States), Dutch, Japanese] value

  Scenario: Verify Switcher for anonymous front-end user
    Given I proceed as the User
    When I am on homepage
    Then I should be on Customer User Login page
    And I should see that "English (United States)" localization is active
    And I should see that localization switcher contains localizations:
      | Dutch                   |
      | English (United States) |
      | Japanese                |

    When I select "Dutch" localization
    Then I should see that localization switcher contains localizations:
      | Dutch                   |
      | English (United States) |
      | Japanese                |
    Then I should see that "Dutch" localization is active

    When I select "Japanese" localization
    Then I should see that localization switcher contains localizations:
      | Dutch                   |
      | English (United States) |
      | Japanese                |
    Then I should see that "Japanese" localization is active

  Scenario: Verify Switcher for logged in front-end user
    Given I signed in as AmandaRCole@example.org on the store frontend
    When I should see that localization switcher contains localizations:
      | Dutch                   |
      | English (United States) |
      | Japanese                |
    Then I should see that "English (United States)" localization is active

    When I select "Dutch" localization
    Then I should see that localization switcher contains localizations:
      | Dutch                   |
      | English (United States) |
      | Japanese                |
    Then I should see that "Dutch" localization is active

    When I select "Japanese" localization
    Then I should see that localization switcher contains localizations:
      | Dutch                   |
      | English (United States) |
      | Japanese                |
    Then I should see that "Japanese" localization is active
