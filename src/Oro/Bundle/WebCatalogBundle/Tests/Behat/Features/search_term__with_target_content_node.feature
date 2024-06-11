@feature-BB-21439
@fixture-OroWebCatalogBundle:content_nodes_for_search_terms.yml

Feature: Search Term - with target Content Node

  Scenario: Feature background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Set default node slugs
    Given I proceed as the Admin
    And I login as administrator
    When I go to Marketing/ Web Catalogs
    And click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Check validation errors
    When I go to Marketing / Search / Search Terms
    And click "Create Search Term"
    And I fill "Search Term Form" with:
      | Phrases | [search_term]                |
      | Action  | Redirect to a different page |
    Then should see the following options for "Target Type" select in form "Search Term Form" pre-filled with "Content Node":
      | Content Node |
    And I should see "Please choose a Web Catalog"
    When I save and close form
    Then I should see "Search Term With Target Content Node Form" validation errors:
      | Content Node | This value should not be blank. |
    When I fill "Search Term With Target Content Node Form" with:
      | Web Catalog | Default Web Catalog |
    And I save and close form
    Then I should see "Search Term With Target Content Node Form" validation errors:
      | Content Node | This value should not be blank. |

  Scenario: Create Search Term (301 Redirect - false)
    When I set "Default Web Catalog" as default web catalog
    And I go to Marketing / Search / Search Terms
    And click "Create Search Term"
    And I fill "Search Term Form" with:
      | Phrases | [search_term]                |
      | Action  | Redirect to a different page |
    Then I should not see "Please choose a Web Catalog"
    When I click "Clearance"
    And I fill "Search Term With Target Content Node Form" with:
      | 301 Redirect | false |
    And I save and close form
    Then I should see "Search Term has been saved" flash message
    And should see Search Term with:
      | Phrases      | [search_term]                |
      | Action       | Redirect to a different page |
      | Target Type  | Content Node                 |
      | 301 Redirect | No                           |
      | Content Node | Clearance                    |
    And I should see "Owner: Main"
    And should see a "Search Term Restrictions section" element
    And I should see "LOCALIZATION WEBSITE CUSTOMER GROUP CUSTOMER Any Any Any Any Run Original Search" in the "Search Term Restrictions section" element

  Scenario: Check the search term record in datagrid
    When I go to Marketing / Search / Search Terms
    Then I should see following grid:
      | Phrases     | Action                              | Restrictions                                                 |
      | search_term | Redirect to content node: Clearance | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Any Any |
    And I set alias "main" for the current browser tab
    When I click "Clearance"
    Then a new browser tab is opened and I switch to it
    And I should see a "Content Node Form" element
    And I switch to the browser tab "main"

  Scenario: Unauthorized user will be forwarded to the Content node
    Given I proceed as the Buyer
    When I am on the homepage
    And I type "search_term" in "search"
    And I click "Search Button"
    Then Page title equals to "Clearance"
    And I should see "Default Web Catalog Clearance"
    And the url should match "/product/search"

  Scenario: Authorized user will be forwarded to the Content node
    When I signed in as AmandaRCole@example.org on the store frontend
    And I type "search_term" in "search"
    And I click "Search Button"
    Then Page title equals to "Clearance"
    And I should see "Default Web Catalog Clearance"
    And the url should match "/product/search"

  Scenario: Update the Search Term (301 Redirect - true)
    Given I proceed as the Admin
    When I click edit "search_term" in grid
    And I fill "Search Term Form" with:
      | 301 Redirect | true |
    And I save and close form
    Then I should see "Search Term has been saved" flash message
    And should see Search Term with:
      | 301 Redirect | Yes |

  Scenario: Authorized user will be redirected to the Content node
    Given I proceed as the Buyer
    When I reload the page
    And I type "search_term" in "search"
    And I click "Search Button"
    Then Page title equals to "Clearance"
    And I should see "Default Web Catalog Clearance"
    And the url should match "/clearance"

  Scenario: Unauthorized user will be redirected to the Content node
    When I click "Account Dropdown"
    And click "Sign Out"
    And I type "search_term" in "search"
    And I click "Search Button"
    Then Page title equals to "Clearance"
    And I should see "Default Web Catalog Clearance"
    And the url should match "/clearance"
