@fixture-OroLocaleBundle:ZuluLocalization.yml
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml

Feature: Sidebar menu localization footer
  Double localization should renders inside a footer content

  Scenario: Create different window session
    Given sessions active:
      | Admin     | first_session  |
      | Buyer     | second_session |

  Scenario: Setup required one currency and two localization options
    Given I proceed as the Admin
    And I login as administrator
    Then I set configuration property "oro_shopping_list.shopping_lists_page_enabled" to "1"
    And I go to System/Configuration
    And I follow "Commerce/Catalog/Pricing" on configuration sidebar
    When fill "Pricing Form" with:
      | Enabled Currencies System | false           |
      | Enabled Currencies        | US Dollar ($)   |
    And click "Save settings"
    Then I should see "Configuration saved" flash message
    Then I follow "System Configuration/General Setup/Localization" on configuration sidebar

    And I fill form with:
      | Enabled Localizations | [English (United States), Zulu_Loc] |
      | Default Localization  | English (United States)             |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: "Switch localization inside sidebar menu"
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    And I am on homepage
    And I should see that "English (United States)" localization is active
    When I select "Zulu" localization
    Then I should see that "Zulu" localization is active

  Scenario: "Check sidebar menu footer on mobile device"
    Given I set window size to 375x640
    Then I should see that "Zulu" localization is active
    When I select "English (United States)" localization
    Then I should see that "English (United States)" localization is active
