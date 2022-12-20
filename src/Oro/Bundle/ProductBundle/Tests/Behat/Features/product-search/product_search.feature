@regression
@ticket-BB-16057
@ticket-BB-16275
@ticket-BB-17352
@ticket-BB-21709
@fixture-OroProductBundle:product_search/products.yml

Feature: Product search
  In order to be able to search for products on frontstore
  As a buyer
  I search for products through the main product search functionality

  Scenario: Feature Background
    Given I enable the existing localizations
    And sessions active:
      | Admin   | first_session  |
      | User    | second_session |
    And I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
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
    When I filter "Any Text" as contains "Product1`\"'&йёщ®"
    Then number of records in "Product Frontend Grid" should be 1
    And I should see "Product1`\"'&йёщ®&reg;>" in grid "Product Frontend Grid"
    And I should not see "Product1`\"'&йёщ®®>"
    When I type "Product1`\"'&йёщ®" in "search"
    And click "Search Button"
    Then number of records in "Product Frontend Grid" should be 1
    And I should see "Product1`\"'&йёщ®&reg;>" in grid "Product Frontend Grid"

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

  Scenario: Check the search by one symbol
    Given I type "Z" in "search"
    When click "Search Button"
    Then I should see "Search Results for \"Z\""
    And number of records in "Product Frontend Grid" should be 0

  Scenario: Change translation key
    Given I proceed as the Admin
    And I login as administrator
    And I go to System / Localization / Translations
    And I filter Key as equal to "oro.product.search.search_title.title"
    And I edit "oro.product.search.search_title.title" Translated Value as "{0} All Products|]1,Inf] Search Results for %text%"

  Scenario: Check the search by one symbol with incorrect transchoice
    Given I proceed as the User
    And I type "Z" in "search"
    When click "Search Button"
    Then I should see "Search Results for Z"
    And number of records in "Product Frontend Grid" should be 0

  Scenario: Check sku attribute searchable
    Given I proceed as the Admin
    And I go to System/Entities/Entity Management
    And filter Name as is equal to "Product"
    And click View Product in grid
    And click Edit sku in grid
    And I fill form with:
      | Searchable | Yes |
    And I save form
    Then I should see "Field saved" flash message
    And I run Symfony "oro:website-search:reindex" command in "prod" environment

  Scenario: I should find product by sku
    Given I proceed as the User
    And I type "PSKU1" in "search"
    And I click "Search Button"
    Then I should see "PSKU1" product

  Scenario: Uncheck sku attribute searchable
    Given I proceed as the Admin
    And I fill form with:
      | Searchable | No |
    And I save form
    Then I should see "Field saved" flash message
    And I run Symfony "oro:website-search:reindex" command in "prod" environment

  Scenario: I should not find product by sku
    Given I proceed as the User
    And I type "PSKU1" in "search"
    And I click "Search Button"
    Then I should not see "PSKU1" product
