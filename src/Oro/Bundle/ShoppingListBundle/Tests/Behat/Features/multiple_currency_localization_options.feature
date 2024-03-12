@fixture-OroLocaleBundle:LocalizationFixture.yml
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml

Feature: Sidebar menu footer with multiple currency and multiple localization options
  Sidebar footer should contain switcher with currency options and select with localization options

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Setup required multiple currency options and multiple localization options
    Given I proceed as the Admin
    And I login as administrator
    Then I go to System/ Configuration
    And follow "System Configuration/General Setup/Currency" on configuration sidebar
    And fill "Currency Form" with:
      | Allowed Currencies | GBP |
    And click "Add"
    And type "1" in "Rate From 1"
    And click on empty space
    And type "1" in "Rate To 1"
    And click on empty space
    And fill "Currency Form" with:
      | Allowed Currencies | AWG |
    And click "Add"
    And type "1" in "Rate From 1"
    And click on empty space
    And type "1" in "Rate To 1"
    And click on empty space
    And fill "Currency Form" with:
      | Allowed Currencies | AFN |
    And click "Add"
    And type "1" in "Rate From 1"
    And click on empty space
    And type "1" in "Rate To 1"
    And click on empty space
    When click "Save settings"
    Then I should see "Configuration saved" flash message
    And go to System/ Websites
    And click "Configuration" on row "Default" in grid
    And follow "Commerce/Catalog/Pricing" on configuration sidebar
    And fill "Pricing Form" with:
      | Enabled Currencies System | false                                                                                   |
      | Enabled Currencies        | [Afghan Afghani (AFN), Aruban Florin (AWG), British Pound (£), US Dollar ($), Euro (€)] |
    And click "Save settings"
    Then I set configuration property "oro_shopping_list.shopping_lists_page_enabled" to "1"
    And I go to System/Configuration
    Then I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill form with:
      | Enabled Localizations | [English (United States), Localization1, Localization2, Localization3] |
      | Default Localization  | English (United States)                                                |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: "Check sidebar menu footer"
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    And I am on homepage
    When I select "AFN" currency
    Then I should see that "AFN" currency is active
    When I select "Localization 1" localization
    Then I should see that "Localization 1" localization is active

  Scenario: "Check sidebar menu footer on mobile device"
    Given I set window size to 375x640
    When I select "AWG" currency
    Then I should see that "AWG" currency is active
    When I select "Localization 2" localization
    Then I should see that "Localization 2" localization is active
