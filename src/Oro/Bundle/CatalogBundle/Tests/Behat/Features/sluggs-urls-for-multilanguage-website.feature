@fixture-OroCatalogBundle:sluggs-urls-for-multilanguage-websites.yml
Feature: Sluggable URLs for multilanguage websites

  Scenario: Create different window session
    Given sessions active:
      | Admin  |first_session |
      | User   |second_session|

  Scenario: Check URL slug for multilanguage websites
    Given I proceed as the Admin
    And login as administrator
    And go to System / Localization/ Languages
    And click "Add Language"
    And fill "Language Form" with:
      |Language|French (France) - fr_FR|
    And click "Add Language"
    And click install "French (France)" in grid
    And click "Install"
    And click enable "French (France)" in grid
    And go to System / Localization/ Localizations
    And click "Create Localization"
    And fill "Localization Form" with:
      |Name               |French         |
      |Title              |French         |
      |Language           |French (France)|
      |Formatting         |French (France)|
      |Parent Localization|English        |
    And click "Save"
    And go to System/ Websites
    And click "Configuration" on row "Default" in grid
    And follow "System configuration/General setup/Localization" on configuration sidebar
    And fill form with:
      |Enabled Localizations|[French, English]|
      |Default Localization |English|
    And submit form
    And I should see "Configuration saved" flash message
    And go to Products/ Products
    And click edit "SKU1" in grid
    When click "URL Slug Fallback Status"
    And fill "URL Slug Form" with:
      |Default Value |EnglishProd|
      |French Default|false      |
      |French        |FrenchProd |
    And save and close form
    And I proceed as the User
    And I am on the homepage
    And type "SKU1" in "search"
    And click "Search Button"
    And click "Product1"
    And the url should match "/EnglishProd"
    And type "SKU1" in "search"
    And click "Search Button"
    And click on "Localization dropdown"
    And click "French"
    And click "Product1"
    And the url should match "/FrenchProd"
