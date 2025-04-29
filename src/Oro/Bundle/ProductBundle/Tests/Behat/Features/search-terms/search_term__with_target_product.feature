@feature-BB-21439
@fixture-OroProductBundle:products_grid_frontend.yml
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml

Feature: Search Term - with target Product

  Scenario: Feature background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Check validation errors
    Given I proceed as the Admin
    And I login as administrator
    When I go to Marketing / Search / Search Terms
    And click "Create Search Term"
    And I fill "Search Term With Target Product Form" with:
      | Phrases     | [search_term]                |
      | Action      | Redirect to a different page |
      | Target Type | Product                      |
    And I save and close form
    Then I should see "Search Term With Target Product Form" validation errors:
      | Product | This value should not be blank. |

  Scenario: Create Search Term (301 Redirect - false)
    When I click "Add"
    And I fill "Search Term With Target Product Form" with:
      | Product               | PSKU13  |
      | 301 Redirect          | false   |
      | Restriction 1 Website |         |
      | Restriction 2 Website | Default |
    And I save and close form
    Then I should see "Search Term has been saved" flash message
    And should see Search Term with:
      | Phrases      | [search_term]                |
      | Action       | Redirect to a different page |
      | Target Type  | Product                      |
      | 301 Redirect | No                           |
      | Product      | Product 13                   |
    And I should see "Owner: Main"
    And should see a "Search Term Restrictions section" element
    And I should see next rows in "Search Term Restrictions Table" table
      | Localization | Website | Customer Group | Customer |
      | Any          | Any     | Any            | Any      |
      | Any          | Default | Any            | Any      |

  Scenario: Check the search term record in datagrid
    When I go to Marketing / Search / Search Terms
    Then I should see following grid:
      | Phrases     | Action                          | Restrictions                                                                     |
      | search_term | Redirect to product: Product 13 | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Any Any Any Any Default Any |
    And I set alias "main" for the current browser tab
    When I click "Product 13"
    Then a new browser tab is opened and I switch to it
    And I should be on Product View page
    And I switch to the browser tab "main"

  Scenario: Unauthorized user will be forwarded to the Product view page
    Given I proceed as the Buyer
    When I am on the homepage
    And I type "search_term" in "search"
    And I click "Search Button"
    Then Page title equals to "Product 13"
    And I should see "All Products Category 3"
    And the url should match "/product/search"

  Scenario: Authorized user will be forwarded to the Product view page
    When I signed in as AmandaRCole@example.org on the store frontend
    And I type "search_term" in "search"
    And I click "Search Button"
    Then Page title equals to "Product 13"
    And I should see "All Products Category 3"
    And the url should match "/product/search"

  Scenario: Update the Search Term (301 Redirect - true)
    Given I proceed as the Admin
    When I click edit "search_term" in grid
    And I fill "Search Term With Target Product Form" with:
      | 301 Redirect | true |
    And I save and close form
    Then I should see "Search Term has been saved" flash message
    And should see Search Term with:
      | 301 Redirect | Yes |

  Scenario: Authorized user will be redirected to the Product view page
    Given I proceed as the Buyer
    When I reload the page
    And I type "search_term" in "search"
    And I click "Search Button"
    Then Page title equals to "Product 13"
    And I should see "All Products Category 3"
    And the url should match "/product/view/\d+"

  Scenario: Unauthorized user will be redirected to the Product view page
    When I click "Account Dropdown"
    And click "Sign Out"
    And I type "search_term" in "search"
    And I click "Search Button"
    Then Page title equals to "Product 13"
    And I should see "All Products Category 3"
    And the url should match "/product/view/\d+"


