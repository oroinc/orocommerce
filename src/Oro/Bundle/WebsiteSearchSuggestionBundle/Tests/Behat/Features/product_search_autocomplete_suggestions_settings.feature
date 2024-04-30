@regression
@feature-BB-23028
@fixture-OroWebsiteSearchSuggestionBundle:product_search_autocomplete_suggestions.yml

Feature: Product Search Autocomplete Suggestions Settings

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer  | second_session |
    And I proceed as the Admin
    And I login as administrator

  Scenario: Change the number of automatically suggested phrases to 2
    Given I go to System / Configuration
    And I follow "Commerce/Product/Product Search" on configuration sidebar
    And I should see "Number Of Automatically Suggested Phrases In Search Autocomplete"
    And uncheck "Use default" for "Number Of Automatically Suggested Phrases In Search Autocomplete" field
    When fill form with:
      | Number Of Automatically Suggested Phrases In Search Autocomplete | 2 |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check the search suggestion autocomplete contains 2 suggestions
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I go to the homepage
    When I type "Numeric" in "search"
    Then I should see an "Search Autocomplete" element
    And I should see 2 elements "Search Suggestion Autocomplete Item"
    And I should see "numeric" in the "Search Suggestion Autocomplete Item" element

  Scenario: Try to change the number of automatically suggested phrases to -1
    Given I proceed as the Admin
    When fill form with:
      | Number Of Automatically Suggested Phrases In Search Autocomplete | -1 |
    And I click "Save settings"
    Then I should see "This value should be between 0 and 100"

  Scenario: Change the number of automatically suggested phrases to 0
    When fill form with:
      | Number Of Automatically Suggested Phrases In Search Autocomplete | 0 |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check the search suggestion autocomplete not contain suggestions
    Given I proceed as the Buyer
    And I go to the homepage
    When I type "Numeric" in "search"
    Then I should see an "Search Autocomplete" element
    And I should not see an "Search Suggestion Autocomplete Item" element
