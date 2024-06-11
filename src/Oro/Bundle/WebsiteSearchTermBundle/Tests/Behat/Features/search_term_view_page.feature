@feature-BB-21439
@fixture-OroWebsiteSearchTermBundle:search_term_view_page.yml

Feature: Search Term View Page

  Scenario: Feature Background
    Given I login as administrator
    And I go to System / Configuration
    And I follow "Commerce/Search/Search Terms" on configuration sidebar
    When uncheck "Use default" for "Enable Search Terms Management" field
    And I check "Enable Search Terms Management"
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Open search term view page
    When I go to Marketing / Search / Search Terms
    And I click view "Product" in grid
    Then I should see Search Term with:
      | Phrases        | [Product]                |
      | Partial match  | No                       |
      | Action         | Show search results page |
      | Search Results | Original search results  |
    And I should see "LOCALIZATION WEBSITE CUSTOMER GROUP CUSTOMER Any Any Any Any Run Original Search" in the "Search Term Restrictions section" element

  Scenario: Check original search results
    When I click "Run Original Search on View Page"
    And I click "Run Original Search on View Page - Product"
    Then I should see "UiDialog" with elements:
      | Title | Original Search Results for "Product" |
    When I sort grid by "SKU"
    Then I should see following grid:
      | SKU    | NAME       | INVENTORY STATUS |
      | PSKU1  | Product 1  | In Stock         |
      | PSKU10 | Product 10 | In Stock         |
      | PSKU11 | Product 11 | In Stock         |
      | PSKU12 | Product 12 | In Stock         |
      | PSKU13 | Product 13 | In Stock         |
      | PSKU14 | Product 14 | In Stock         |
      | PSKU15 | Product 15 | In Stock         |
      | PSKU16 | Product 16 | In Stock         |
      | PSKU17 | Product 17 | In Stock         |
      | PSKU18 | Product 18 | In Stock         |
      | PSKU19 | Product 19 | In Stock         |
      | PSKU2  | Product 2  | In Stock         |
      | PSKU20 | Product 20 | In Stock         |
      | PSKU3  | Product 3  | In Stock         |
      | PSKU4  | Product 4  | In Stock         |
      | PSKU5  | Product 5  | In Stock         |
      | PSKU7  | Product 7  | Out of Stock     |
      | PSKU8  | Product 8  | In Stock         |
      | PSKU9  | Product 9  | In Stock         |
