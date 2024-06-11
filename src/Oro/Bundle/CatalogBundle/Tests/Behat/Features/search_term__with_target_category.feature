@feature-BB-21439
@fixture-OroWebCatalogBundle:empty_web_catalog.yml
@fixture-OroCatalogBundle:all_products_page.yml

Feature: Search Term - with target Category

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
    And I fill "Search Term Form" with:
      | Phrases     | [search_term]                |
      | Action      | Redirect to a different page |
      | Target Type | Category                     |
    And I save and close form
    Then I should see validation errors:
      | Category | This value should not be blank. |

  Scenario: Create Search Term (301 Redirect - false)
    When I click "NewCategory"
    And I click "Add"
    And I fill "Search Term Form" with:
      | Restriction 1 Customer |                |
      | Restriction 2 Customer | first customer |
      | 301 Redirect           | false          |
    And I save and close form
    Then I should see "Search Term has been saved" flash message
    And should see Search Term with:
      | Phrases      | [search_term]                |
      | Action       | Redirect to a different page |
      | Target Type  | Category                     |
      | 301 Redirect | No                           |
      | Category     | NewCategory                  |
    And I should see "Owner: Main"
    And should see a "Search Term Restrictions section" element
    And I should see next rows in "Search Term Restrictions Table" table
      | Localization | Website | Customer Group | Customer       |
      | Any          | Any     | Any            | Any            |
      | Any          | Any     | Any            | first customer |

  Scenario: Check the search term record in datagrid
    When I go to Marketing / Search / Search Terms
    Then I should see following grid:
      | Phrases     | Action                            | Restrictions                                                                            |
      | search_term | Redirect to category: NewCategory | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Any Any first customer Any Any Any |
    And I set alias "main" for the current browser tab
    When I click "NewCategory"
    Then a new browser tab is opened and I switch to it
    And Page title equals to "NewCategory - Edit - Master Catalog - Products"
    And I should see a "Category Form" element
    And I switch to the browser tab "main"

  Scenario: Unauthorized user will be forwarded to the NewCategory
    Given I proceed as the Buyer
    When I am on the homepage
    And I type "search_term" in "search"
    And I click "Search Button"
    Then Page title equals to "NewCategory"
    And I should see "All Products / NewCategory"
    And number of records in "Product Frontend Grid" should be 3
    And I should see "PSKU3" product
    And I should see "PSKU2" product
    And I should see "PSKU1" product
    And the url should match "/product/search"

  Scenario: Authorized user will be forwarded to the NewCategory
    When I signed in as AmandaRCole@example.org on the store frontend
    And I type "search_term" in "search"
    And I click "Search Button"
    Then Page title equals to "NewCategory"
    And I should see "All Products / NewCategory"
    And number of records in "Product Frontend Grid" should be 3
    And I should see "PSKU3" product
    And I should see "PSKU2" product
    And I should see "PSKU1" product
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

  Scenario: Authorized user will be redirected to the NewCategory
    Given I proceed as the Buyer
    When I reload the page
    And I type "search_term" in "search"
    And I click "Search Button"
    Then Page title equals to "NewCategory"
    And I should see "All Products / NewCategory"
    And number of records in "Product Frontend Grid" should be 3
    And I should see "PSKU3" product
    And I should see "PSKU2" product
    And I should see "PSKU1" product
    And the url should match "/product/"

  Scenario: Unauthorized user will be redirected to the NewCategory
    When I click "Sign Out"
    And I type "search_term" in "search"
    And I click "Search Button"
    Then Page title equals to "NewCategory"
    And I should see "All Products / NewCategory"
    And number of records in "Product Frontend Grid" should be 3
    And I should see "PSKU3" product
    And I should see "PSKU2" product
    And I should see "PSKU1" product
    And the url should match "/product/"
