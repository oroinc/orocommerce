@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
@fixture-OroUserBundle:UserLocalizations.yml
@regression

Feature: Language and currency switcher on hamburger menu

  Scenario: Feature background
    Given sessions active:
      | Admin       | first_session  |
      | Guest       | second_session |
      | user_mobile | mobile_session |
      | tablet_view | system_session |

  Scenario: Check with 1 localization, 1 currency: no any switchers (desktop)
    Given I proceed as the Guest
    When I am on homepage
    And I open main menu
    Then I should not see an "LocalizationCurrencySwitcher" element
    And I close main menu

  Scenario: Check with 1 localization, 1 currency: no any switchers (tablet view)
    Given I proceed as the tablet_view
    When I am on homepage
    And I set window size to 640x1100
    And I open main menu
    Then I should not see an "LocalizationCurrencySwitcher" element
    And I close main menu

  Scenario: Check with 1 localization, 1 currency: no any switchers (mobile)
    Given I proceed as the user_mobile
    When I am on homepage
    And I open main menu
    Then I should not see an "LocalizationCurrencySwitcher" element
    And I close main menu

  Scenario: Enable 2 localizations, 1 currency
    Given I proceed as the Admin
    And I login as administrator
    When go to System/ Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill form with:
      | Enabled Localizations | [English (United States), German Localization] |
      | Default Localization  | English (United States)                        |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Check with 2 localizations and 1 currency: there are two localization links (desktop)
    Given I proceed as the Guest
    When I reload the page
    And I open main menu
    Then I should see an "LocalizationCurrencySwitcher" element
    And I should see 2 elements "Localization Switcher"
    And I should not see an "Currency Switcher" element
    And I close main menu
    And I should see that the LocalizationCurrencySwitcher element has a type "toggle"

  Scenario: Check with 2 localizations and 1 currency: there are two localization links (tablet view)
    Given I proceed as the tablet_view
    When I reload the page
    And I set window size to 640x1100
    And I open main menu
    Then I should see an "LocalizationCurrencySwitcher" element
    And I should see 2 elements "Localization Switcher"
    And I should not see an "Currency Switcher" element
    And I close main menu
    And I should see that the LocalizationCurrencySwitcher element has a type "toggle"

  Scenario: Check with 2 localizations and 1 currency: there are two localization links (mobile)
    Given I proceed as the user_mobile
    When I reload the page
    And I open main menu
    Then I should see an "LocalizationCurrencySwitcher" element
    And I should see 2 elements "Localization Switcher"
    And I should not see an "Currency Switcher" element
    And I close main menu
    And I should see that the LocalizationCurrencySwitcher element has a type "toggle"

  Scenario: Enable 3 localizations, 1 currency
    Given I proceed as the Admin
    When I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill form with:
      | Enabled Localizations | [English (United States), German Localization, French Localization] |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Check with 3 localizations and 1 currency: there is select2 (desktop)
    Given I proceed as the Guest
    When I reload the page
    And I open main menu
    Then I should see an "LocalizationCurrencySwitcher" element
    And I should not see an "Currency Switcher" element
    And I close main menu
    And I should see that the LocalizationCurrencySwitcher element has a type "select"
    And I should see that the Localization Switcher has a type "select"

  Scenario: Check with 3 localizations and 1 currency: there is select2 (tablet view)
    Given I proceed as the tablet_view
    When I reload the page
    And I set window size to 640x1100
    And I open main menu
    Then I should see an "LocalizationCurrencySwitcher" element
    And I should not see an "Currency Switcher" element
    And I close main menu
    And I should see that the LocalizationCurrencySwitcher element has a type "select"
    And I should see that the Localization Switcher has a type "toggle_vertical"

  Scenario: Check with 3 localizations and 1 currency: there is select2 (mobile)
    Given I proceed as the user_mobile
    When I reload the page
    And I open main menu
    Then I should see an "LocalizationCurrencySwitcher" element
    And I should not see an "Currency Switcher" element
    And I close main menu
    And I should see that the LocalizationCurrencySwitcher element has a type "select"
    And I should see that the Localization Switcher has a type "toggle_vertical"

  Scenario: Enable 1 localization and 2 currencies
    Given I proceed as the Admin
    When I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill form with:
      | Enabled Localizations | [English (United States)] |
    And I submit form
    Then I should see "Configuration saved" flash message

    When I follow "Commerce/Catalog/Pricing" on configuration sidebar
    And fill "Pricing Form" with:
      | Enabled Currencies System | false                     |
      | Enabled Currencies        | [US Dollar ($), Euro (€)] |
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check with 1 localization and 2 currencies:  there are two currency links (desktop)
    Given I proceed as the Guest
    When I reload the page
    And I open main menu
    Then I should see an "LocalizationCurrencySwitcher" element
    And I should see 2 elements "Currency Switcher"
    And I should not see an "Localization Switcher" element
    And I close main menu
    And I should see that the LocalizationCurrencySwitcher element has a type "toggle"

  Scenario: Check with 1 localization and 2 currencies:  there are two currency links (tablet view)
    Given I proceed as the tablet_view
    When I reload the page
    And I set window size to 640x1100
    And I open main menu
    Then I should see an "LocalizationCurrencySwitcher" element
    And I should see 2 elements "Currency Switcher"
    And I should not see an "Localization Switcher" element
    And I close main menu
    And I should see that the LocalizationCurrencySwitcher element has a type "toggle"

  Scenario: Check with 1 localization and 2 currencies:  there are two currency links (mobile)
    Given I proceed as the user_mobile
    When I reload the page
    And I open main menu
    Then I should see an "LocalizationCurrencySwitcher" element
    And I should see 2 elements "Currency Switcher"
    And I should not see an "Localization Switcher" element
    And I close main menu
    And I should see that the LocalizationCurrencySwitcher element has a type "toggle"

  Scenario: Enable with 2 localizations and 4 currencies: there are switchers for language and currencies
    Given I proceed as the Admin
    When follow "System Configuration/General Setup/Currency" on configuration sidebar
    And fill "Currency Form" with:
      | Allowed Currencies | British Pound (GBP) |
    And click "Add"
    And fill "Currency Form" with:
      | Allowed Currencies | Ukrainian Hryvnia (UAH) |
    And click "Add"
    And fill "Currency Form" with:
      | Allowed Currencies | Canadian Dollar (CAD) |
    And click "Add"
    And I type "1" in "Rate From 1"
    And click on empty space
    And type "1" in "Rate From 2"
    And click on empty space
    And type "1" in "Rate From 3"
    And click "Save settings"
    Then I should see "Configuration saved" flash message

    When I follow "Commerce/Catalog/Pricing" on configuration sidebar
    And fill "Pricing Form" with:
      | Enabled Currencies System | false                                                                 |
      | Enabled Currencies        | [US Dollar ($), Euro (€), Ukrainian Hryvnia (UAH), British Pound (£)] |
    And click "Save settings"
    Then I should see "Configuration saved" flash message

    When I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill form with:
      | Enabled Localizations | [English (United States), German Localization] |
      | Default Localization  | English (United States)                        |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Check with 2 localizations and 4 currencies: there are switchers for language and currencies (desktop)
    Given I proceed as the Guest
    When I reload the page
    Then I should see that the LocalizationCurrencySwitcher element has a type "select"
    And I should see that the Localization Switcher has a type "toggle"
    And I should see that the Currency Switcher has a type "toggle"

  Scenario: Check with 2 localizations and 4 currencies: there are switchers for language and currencies (tablet view)
    Given I proceed as the tablet_view
    When I reload the page
    And I set window size to 640x1100
    Then I should see that the LocalizationCurrencySwitcher element has a type "select"
    And I should see that the Localization Switcher has a type "toggle"
    And I should see that the Currency Switcher has a type "toggle"

  Scenario: Check with 2 localizations and 4 currencies: there are switchers for language and currencies (mobile)
    Given I proceed as the user_mobile
    When I reload the page
    Then I should see that the LocalizationCurrencySwitcher element has a type "select"
    And I should see that the Localization Switcher has a type "toggle"
    And I should see that the Currency Switcher has a type "toggle"

  Scenario: Enable 5 localizations and 4 currencies
    Given I proceed as the Admin
    When I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill form with:
      | Enabled Localizations | [English (United States), German Localization, French Localization, Localization1, Localization2] |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Check with 5 localizations and 4 currencies: the select2 for language and switcher for currencies (desktop)
    Given I proceed as the Guest
    When I reload the page
    Then I should see that the LocalizationCurrencySwitcher element has a type "select"
    And I should see that the Localization Switcher has a type "select"
    And I should see that the Currency Switcher has a type "toggle"

  Scenario: Check with 5 localizations and 4 currencies: the select2 for language and switcher for currencies (tablet view)
    Given I proceed as the tablet_view
    When I reload the page
    And I set window size to 640x1100
    Then I should see that the LocalizationCurrencySwitcher element has a type "select"
    And I should see that the Localization Switcher has a type "select"
    And I should see that the Currency Switcher has a type "toggle"

  Scenario: Check with 5 localizations and 4 currencies: the select2 for language and switcher for currencies (mobile)
    Given I proceed as the user_mobile
    When I reload the page
    Then I should see that the LocalizationCurrencySwitcher element has a type "select"
    And I should see that the Localization Switcher has a type "select"
    And I should see that the Currency Switcher has a type "toggle"

  Scenario: Enable 5 localizations and 5 currency
    Given I proceed as the Admin
    When I follow "Commerce/Catalog/Pricing" on configuration sidebar
    And fill "Pricing Form" with:
      | Enabled Currencies System | false                                                                                        |
      | Enabled Currencies        | [US Dollar ($), Euro (€), Ukrainian Hryvnia (UAH), British Pound (£), Canadian Dollar (CA$)] |
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check with 5 localizations and >4 currencies: the select2 for language and for currencies (desktop)
    Given I proceed as the Guest
    When I reload the page
    Then I should see that the LocalizationCurrencySwitcher element has a type "select"
    And I should see that the Localization Switcher has a type "select"
    And I should see that the Currency Switcher has a type "select"

  Scenario: Check with 5 localizations and >4 currencies: the select2 for language and for currencies (tablet view)
    Given I proceed as the tablet_view
    When I reload the page
    And I set window size to 640x1100
    Then I should see that the LocalizationCurrencySwitcher element has a type "select"
    And I should see that the Localization Switcher has a type "select"
    And I should see that the Currency Switcher has a type "select"

  Scenario: Check with 5 localizations and >4 currencies: the select2 for language and for currencies (mobile)
    Given I proceed as the user_mobile
    When I reload the page
    Then I should see that the LocalizationCurrencySwitcher element has a type "select"
    And I should see that the Localization Switcher has a type "select"
    And I should see that the Currency Switcher has a type "select"
