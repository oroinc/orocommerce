@feature-BB-21439
@fixture-OroProductBundle:products_grid_frontend.yml
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
@fixture-OroCMSBundle:content_block.yml

Feature: Search Term - with additional Content Block

  Scenario: Feature background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create Search Term
    Given I proceed as the Admin
    And I login as administrator
    When I go to Marketing / Search / Search Terms
    And click "Create Search Term"
    And I fill "Search Term Form" with:
      | Phrases                  | [search_term, PSKU13]     |
      | Action                   | Show search results page  |
      | Search Results           | Original search results   |
      | Additional Content Block | Promotional Content Block |
    And I save and close form
    Then I should see "Search Term has been saved" flash message
    And should see Search Term with:
      | Phrases                  | [search_term, PSKU13]     |
      | Action                   | Show search results page  |
      | Search Results           | Original search results   |
      | Additional Content Block | promotional-content-block |
    And I should see "Owner: Main"
    And should see a "Search Term Restrictions section" element
    And I should see "LOCALIZATION WEBSITE CUSTOMER GROUP CUSTOMER Any Any Any Any Run Original Search" in the "Search Term Restrictions section" element

  Scenario: Check the search term record in datagrid
    When I go to Marketing / Search / Search Terms
    Then I should see following grid:
      | Phrases            | Action                                                   | Restrictions                                                 |
      | search_term PSKU13 | Show additional content block: Promotional Content Block | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Any Any |
    And I set alias "main" for the current browser tab
    When I click "Promotional Content Block"
    Then a new browser tab is opened and I switch to it
    And Page title equals to "promotional-content-block - Content Blocks - Marketing"
    And I switch to the browser tab "main"

  Scenario: Unauthorized user will see Additional Content Block
    Given I proceed as the Buyer
    When I am on the homepage
    And I type "search_term" in "search"
    And I click "Search Button"
    Then I should see "There are no products"
    And number of records in "Product Frontend Grid" should be zero
    And I should see "Fast & Free Shipping for orders over $45"
    And the url should match "/product/search"

    When I type "PSKU13" in "search"
    And I click "Search Button"
    Then I should see "Product 13"
    And number of records in "Product Frontend Grid" should be 1
    And I should see "Fast & Free Shipping for orders over $45"
    And the url should match "/product/search"

  Scenario: Authorized user will see Additional Content Block
    When I signed in as AmandaRCole@example.org on the store frontend
    And I type "search_term" in "search"
    And I click "Search Button"
    Then I should see "There are no products"
    And number of records in "Product Frontend Grid" should be zero
    And I should see "Fast & Free Shipping for orders over $45"
    And the url should match "/product/search"

    When I type "PSKU13" in "search"
    And I click "Search Button"
    Then I should see "Product 13"
    And number of records in "Product Frontend Grid" should be 1
    And I should see "Fast & Free Shipping for orders over $45"
    And the url should match "/product/search"

