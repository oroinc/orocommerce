@regression
@fixture-OroCatalogBundle:category-title-check-at-search.yml
Feature: Invalid category title at search results

  Scenario: Search
    Given I login as administrator
    When click on "Search"
    And type "New" in "search"
    And click "Search Submit"
    Then should not see "1_2"
    And should see "NewCategory"
