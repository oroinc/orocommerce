@feature-BB-21439
@fixture-OroProductBundle:search_term__with_product_collection.yml

Feature: Search Term - with Product Collection

  Scenario: Feature background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    And I login as administrator
    And I go to System / Configuration
    And I follow "Commerce/Search/Search Terms" on configuration sidebar
    When uncheck "Use default" for "Enable Search Terms Management" field
    And I check "Enable Search Terms Management"
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check validation errors
    When I go to Marketing / Search / Search Terms
    And click "Create Search Term"
    And I fill "Search Term With Target Product Form" with:
      | Phrases        | [search_term]            |
      | Action         | Show search results page |
      | Search Results | Product Collection       |
    And I save and close form
    Then I should see "Search Term With Product Collection Form" validation errors:
      | Product Collection | This value should not be blank. |

  Scenario: Create Search Term
    When I click "Add"
    And I fill "Search Term With Target Product Form" with:
      | Product Collection    | Product Collection 1 |
      | Restriction 1 Website |                      |
      | Restriction 2 Website | Default              |
    And I save and close form
    Then I should see "Search Term has been saved" flash message
    And should see Search Term with:
      | Phrases            | [search_term]            |
      | Action             | Show search results page |
      | Search Results     | Product Collection       |
      | Product Collection | Product Collection 1     |
    And I should see "Owner: Main"
    And should see a "Search Term Restrictions section" element
    And I should see next rows in "Search Term Restrictions Table" table
      | Localization | Website | Customer Group | Customer |
      | Any          | Any     | Any            | Any      |
      | Any          | Default | Any            | Any      |

  Scenario: Check the search term record in datagrid
    When I go to Marketing / Search / Search Terms
    Then I should see following grid:
      | Phrases     | Action                                        | Restrictions                                                                     |
      | search_term | Show product collection: Product Collection 1 | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Any Any Any Any Default Any |
    And I set alias "main" for the current browser tab
    When I click "Product Collection 1"
    Then a new browser tab is opened and I switch to it
    And Page title equals to "Product Collection 1 - Products - Manage Segments - Reports &amp; Segments"
    And I switch to the browser tab "main"

  Scenario: Unauthorized user will see a product collection
    Given I proceed as the Buyer
    When I am on the homepage
    And I type "search_term" in "search"
    And I click "Search Button"
    Then I should see "PSKU1"
    And I should not see "PSKU2"
    And the url should match "/product/search"

  Scenario: Authorized user will see a product collection
    When I signed in as AmandaRCole@example.org on the store frontend
    And I type "search_term" in "search"
    And I click "Search Button"
    Then I should see "PSKU1"
    And I should not see "PSKU2"
    And the url should match "/product/search"
