@regression
@feature-BB-23028
@fixture-OroWebsiteSearchSuggestionBundle:product_search_autocomplete_suggestions_localizations.yml

Feature: Product Search Autocomplete Suggestions Localizations

  Scenario: Feature Background
    Given I login as administrator
    And I enable the existing localizations
    And I go to System / Configuration
    And I follow "Commerce/Product/Product Search" on configuration sidebar
    When uncheck "Use default" for "Enable Automatic Phrase Suggestions in Search Autocomplete" field
    And I check "Enable Automatic Phrase Suggestions in Search Autocomplete"
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check the search suggestion autocomplete contains default localization suggestions
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I go to the homepage
    When I type "Numeric" in "search"
    Then I should see an "Search Autocomplete" element
    And I should see 4 elements "Search Suggestion Autocomplete Item"
    And I should see "numeric" in the "Search Suggestion Autocomplete Item" element
    And I click on empty space

  Scenario: Check the search suggestion autocomplete contains Zulu localization suggestions
    Given I select "Zulu" localization
    And I go to the homepage
    When I type "ZuluProduct" in "search"
    Then I should see an "Search Autocomplete" element
    And I should see 4 elements "Search Suggestion Autocomplete Item"
    And I should see "ZuluProduct" in the "Search Suggestion Autocomplete Item" element
