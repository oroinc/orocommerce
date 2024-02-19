@fixture-OroLocaleBundle:ZuluLocalization.yml
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml

Feature: Sidebar menu currency footer
  Double currency should renders inside a footer content

  Scenario: Create different window session
    Given sessions active:
      | Admin     | first_session  |
      | Buyer     | second_session |

  Scenario: Setup required two currency options and one localization
    Given I proceed as the Admin
    And I login as administrator
    Then I set configuration property "oro_shopping_list.shopping_lists_page_enabled" to "1"
    And I go to System/Configuration
    And I follow "Commerce/Catalog/Pricing" on configuration sidebar
    When fill "Pricing Form" with:
      | Enabled Currencies System | false                     |
      | Enabled Currencies        | [US Dollar ($), Euro (€)] |
    And click "Save settings"
    Then I should see "Configuration saved" flash message
    Then I follow "System Configuration/General Setup/Localization" on configuration sidebar

    And I fill form with:
      | Enabled Localizations | English (United States) |
      | Default Localization  | English (United States) |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Switch currency inside sidebar menu
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    And I am on homepage
    And I should see that "$" currency is active
    When I select "€" currency
    Then I should see that "€" currency is active

  Scenario: Check sidebar menu footer on mobile device
    Given I set window size to 375x640
    And I should see that "€" currency is active
    When I select "$" currency
    Then I should see that "$" currency is active
