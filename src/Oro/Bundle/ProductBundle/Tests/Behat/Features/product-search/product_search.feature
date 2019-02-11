@regression
@ticket-BB-16057
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
@fixture-OroProductBundle:product_search/products.yml

Feature: Product search
  In order to be able to search for products on frontstore
  As a bayer
  I search for products through the main product search functionality

  Scenario: Feature Background
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I go to the homepage

  Scenario: Check the search results match the specified criteria (search text).
    Given I type "Description3" in "search"
    When click "Search Button"
    Then number of records in "Product Frontend Grid" should be 1

  Scenario: Check the product search through a filter from the product subsets found
    Given I type "Product" in "search"
    And click "Search Button"
    And number of records in "Product Frontend Grid" should be 3
    When I filter "Any Text" as contains "Description2"
    Then number of records in "Product Frontend Grid" should be 1

  Scenario: Results using the search and through the filter "all_text" should be equal
    Given I click "Search Button"
    When I filter "Any Text" as contains "Product1"
    Then number of records in "Product Frontend Grid" should be 1
    And I should see "Product1" in grid "Product Frontend Grid"
    When I type "Product1" in "search"
    And click "Search Button"
    Then number of records in "Product Frontend Grid" should be 1
    And I should see "Product1" in grid "Product Frontend Grid"

  Scenario: Check whether the text in header of the page matches the search text
    Given I type "Search string" in "search"
    And click "Search Button"
    Then I should see "Search Results for \"Search string\""
    And number of records in "Product Frontend Grid" should be 0

  Scenario: Check the text in header of the page for the search by empty string
    Given I type " " in "search"
    And click "Search Button"
    Then I should see "All Products"
    And number of records in "Product Frontend Grid" should be 3
