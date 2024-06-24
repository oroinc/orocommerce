@feature-BB-21439
@fixture-OroProductBundle:products_grid_frontend.yml
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml

Feature: Search Term - with original search results

  Scenario: Feature background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Check validation errors
    Given I proceed as the Admin
    And I login as administrator
    When I go to Marketing / Search / Search Terms
    And click "Create Search Term"
    And I save and close form
    Then I should see validation errors:
      | Phrases | This value should not be blank. |

  Scenario: Create Search Term
    When I click "Add"
    And I fill "Search Term Form" with:
      | Phrases                      | [search_term, PSKU13]    |
      | Action                       | Show search results page |
      | Search Results               | Original search results  |
      | Restriction 1 Customer Group |                          |
      | Restriction 2 Customer Group | AmandaRColeGroup         |
    And I save and close form
    Then I should see "Search Term has been saved" flash message
    And should see Search Term with:
      | Phrases        | [search_term, PSKU13]    |
      | Action         | Show search results page |
      | Search Results | Original search results  |
    And I should see "Owner: Main"
    And should see a "Search Term Restrictions section" element
    And I should see next rows in "Search Term Restrictions Table" table
      | Localization | Website | Customer Group   | Customer |
      | Any          | Any     | Any              | Any      |
      | Any          | Any     | AmandaRColeGroup | Any      |

  Scenario: Check that original search results are not modified for an unauthorized user
    Given I proceed as the Buyer
    When I am on the homepage
    And I type "search_term" in "search"
    And I click "Search Button"
    Then I should see "There are no products"
    And number of records in "Product Frontend Grid" should be zero
    And the url should match "/product/search"

    When I type "PSKU13" in "search"
    And I click "Search Button"
    Then I should see "Product 13"
    And number of records in "Product Frontend Grid" should be 1
    And the url should match "/product/search"

  Scenario: Check that original search results are not modified for an authorized user
    When I signed in as AmandaRCole@example.org on the store frontend
    And I type "search_term" in "search"
    And I click "Search Button"
    Then I should see "There are no products"
    And number of records in "Product Frontend Grid" should be zero
    And the url should match "/product/search"

    When I type "PSKU13" in "search"
    And I click "Search Button"
    Then I should see "Product 13"
    And number of records in "Product Frontend Grid" should be 1
    And the url should match "/product/search"
