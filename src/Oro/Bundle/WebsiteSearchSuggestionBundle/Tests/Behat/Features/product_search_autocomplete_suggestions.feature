@feature-BB-23028
@fixture-OroWebsiteSearchSuggestionBundle:product_search_autocomplete_suggestions.yml

Feature: Product Search Autocomplete Suggestions

  Scenario: Feature Background
    Given I signed in as AmandaRCole@example.org on the store frontend

  Scenario: Check the search suggestion autocomplete is empty when no products are found
    Given I go to the homepage
    When I type "Search string" in "search"
    And I should see an "Search Autocomplete" element
    Then I should see "No products were found to match your search" in the "Search Autocomplete No Found" element
    And I should not see an "Search Suggestion Autocomplete Item" element
    And I should not see an "Search Autocomplete Submit" element

  Scenario: Check the search suggestion autocomplete is not empty when products are found
    Given I go to the homepage
    When I type "Numeric" in "search"
    Then I should see an "Search Autocomplete" element
    And I should see an "Search Suggestion Autocomplete Item" element
    And I should see 4 elements "Search Suggestion Autocomplete Item"
    And I should see "numeric" in the "Search Suggestion Autocomplete Item" element

  Scenario: Check the search suggestion autocomplete navigation works via the arrow down key
    When I click on "Search Suggestion Autocomplete Item"
    Then I should see "Search Results for \"numeric\""
    And number of records in "Product Frontend Grid" should be 2
