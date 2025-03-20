@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
@fixture-OroUserBundle:UserLocalizations.yml
@regression

Feature: Language and currency switcher above the header

  Scenario: Feature background
    Given sessions active:
      | Admin | first_session  |
      | Guest | second_session |
    And I proceed as the Admin
    And I login as administrator

  Scenario: Create Content Block
    When go to Marketing / Content Blocks
    And click "Create Content Block"
    And fill "Content Block Form" with:
      | Owner   | Main                |
      | Alias   | promotional-content |
      | Titles  | Test Title          |
      | Enabled | True                |
    And I click "Add Content"
    And I fill "Content Block Form" with:
      | Content Variant | <div>Some Content</div> |
    And I save and close form
    Then I should see "Content block has been saved" flash message

  Scenario: Add currencies
    When I go to System / Configuration
    And follow "System Configuration/General Setup/Currency" on configuration sidebar
    And fill "Currency Form" with:
      | Allowed Currencies | British Pound (GBP) |
    And click "Add"
    And fill "Currency Form" with:
      | Allowed Currencies | Ukrainian Hryvnia (UAH) |
    And click "Add"
    And I type "1" in "Rate From 1"
    And click on empty space
    And type "1" in "Rate From 2"
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: No promo block and no top nav menu
    When I go to System / Theme Configurations
    And I click Edit "Golden Carbon" in grid
    And I fill "Theme Configuration Form" with:
      | Language and Currency Switchers | Above the header |
      | Quick Access Button Label       | Test Label       |
      | Quick Access Button Type        | Storefront Menu  |
    And I save and close form
    Then I should see "Theme Configuration has been saved" flash message

  Scenario: Enable 3 localizations, 1 currency
    When I go to System / Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill form with:
      | Enabled Localizations | [English (United States) , German Localization, French Localization] |
      | Default Localization  | English (United States)                                              |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Check switcher with 3 localizations, 1 currency (No promo block and no top nav menu)
    Given I proceed as the Guest
    When I am on homepage
#   screen width >768px. Switcher above the header, separate lang and currency switchers
    Then I should see the location of the Language and Currency Switchers "above the header, separate switchers"

#   screen width 460px-768px. Switcher is in burger menu
    When I set window size to 640x1100
    Then I should see the location of the Language and Currency Switchers "in the hamburger menu"

  Scenario: Enable 5 localizations and 4 currencies
    Given I proceed as the Admin
    When I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill form with:
      | Enabled Localizations | [English (United States), German Localization, French Localization, Localization1, Localization2] |
    And I submit form
    Then I should see "Configuration saved" flash message

    When I follow "Commerce/Catalog/Pricing" on configuration sidebar
    And fill "Pricing Form" with:
      | Enabled Currencies System | false                                                                 |
      | Enabled Currencies        | [US Dollar ($), Euro (€), Ukrainian Hryvnia (UAH), British Pound (£)] |
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check switcher with 5 localizations and 4 currencies (No promo block and no top nav menu)
    Given I proceed as the Guest
    When I reload the page
#   screen width >768px. Switcher above the header, separate lang and currency switchers
    Then I should see the location of the Language and Currency Switchers "above the header, separate switchers"

#   screen width 460px-768px. Switcher is in burger menu
    When I set window size to 640x1100
    Then I should see the location of the Language and Currency Switchers "in the hamburger menu"

  Scenario: Only one - promo block or top nav menu is enabled
    Given I proceed as the Admin
    When I go to System / Theme Configurations
    And I click Edit "Golden Carbon" in grid
    And I fill "Theme Configuration Form" with:
      | Promotional Content | promotional-content |
    And I save and close form
    Then I should see "Theme Configuration has been saved" flash message

  Scenario: Check switcher with 5 localizations and 4 currencies (Only one - promo block or top nav menu is enabled)
    Given I proceed as the Guest
    When I reload the page
#   screen width >768px. Switcher above the header, separate lang and currency switchers
    Then I should see the location of the Language and Currency Switchers "above the header, separate switchers"

#   screen width 460px-768px. Switcher above the header, as single “globe” button
    When I set window size to 640x1100
    Then I should see the location of the Language and Currency Switchers "above the header, as single switcher"

  Scenario: Enable 3 localizations, 1 currency
    Given I proceed as the Admin
    When I go to System / Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill form with:
      | Enabled Localizations | [English (United States) , German Localization, French Localization] |
      | Default Localization  | English (United States)                                              |
    And I submit form
    Then I should see "Configuration saved" flash message

    When I follow "Commerce/Catalog/Pricing" on configuration sidebar
    And fill "Pricing Form" with:
      | Enabled Currencies | [US Dollar ($)] |
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check switcher with 3 localizations, 1 currency (Only one - promo block or top nav menu is enabled)
    Given I proceed as the Guest
    When I reload the page
#   screen width >768px. Switcher above the header, separate lang and currency switchers
    Then I should see the location of the Language and Currency Switchers "above the header, separate switchers"

#   screen width 460px-768px. Switcher above the header, as single “globe” button
    When I set window size to 640x1100
    Then I should see the location of the Language and Currency Switchers "above the header, as single switcher"

  Scenario: Both promo block and top nav menu are enabled
    Given I proceed as the Admin
    When I go to System / Theme Configurations
    And I click Edit "Golden Carbon" in grid
    And I fill "Theme Configuration Form" with:
      | Top Navigation Menu | commerce_top_nav |
    And I save and close form
    Then I should see "Theme Configuration has been saved" flash message

  Scenario: Check switcher with 3 localizations, 1 currency (Both promo block and top nav menu are enabled)
    Given I proceed as the Guest
    When I reload the page
#   screen width >768px. Switcher is above the header, separate lang and currency switchers
    Then I should see the location of the Language and Currency Switchers "above the header, separate switchers"

#   screen width 460px-768px. Switcher above the header, as single “globe” button
    When I set window size to 640x1100
    Then I should see the location of the Language and Currency Switchers "above the header, as single switcher"

  Scenario: Enable 5 localizations and 4 currencies
    Given I proceed as the Admin
    When I go to System / Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill form with:
      | Enabled Localizations | [English (United States), German Localization, French Localization, Localization1, Localization2] |
    And I submit form
    Then I should see "Configuration saved" flash message

    When I follow "Commerce/Catalog/Pricing" on configuration sidebar
    And fill "Pricing Form" with:
      | Enabled Currencies System | false                                                                 |
      | Enabled Currencies        | [US Dollar ($), Euro (€), Ukrainian Hryvnia (UAH), British Pound (£)] |
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check switcher with 5 localizations and 4 currencies (Both promo block and top nav menu are enabled)
    Given I proceed as the Guest
    When I reload the page
#   screen width >768px. Switcher is above the header, separate lang and currency switchers"
    Then I should see the location of the Language and Currency Switchers "above the header, separate switchers"

#   screen width 460px-768px. Switcher above the header, as single “globe” button
    When I set window size to 640x1100
    Then I should see the location of the Language and Currency Switchers "above the header, as single switcher"
