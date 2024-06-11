@feature-BB-21439
@fixture-OroProductBundle:products_grid_frontend.yml
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml

Feature: Search Terms - with target Product

  Scenario: Feature background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    And I login as administrator
    When I go to System / Configuration
    And I follow "Commerce/Search/Search Terms" on configuration sidebar
    And uncheck "Use default" for "Enable Search Terms Management" field
    And I check "Enable Search Terms Management"
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Create a Search Term with default restrictions
    When I go to Marketing / Search / Search Terms
    And click "Create Search Term"
    And I fill "Search Term With Target Product Form" with:
      | Phrases     | [light]                      |
      | Action      | Redirect to a different page |
      | Target Type | Product                      |
      | Product     | PSKU13                       |
    And I save and close form
    Then I should see "Search Term has been saved" flash message

  Scenario: Create another Search Term with restriction by Website
    When I go to Marketing / Search / Search Terms
    And click "Create Search Term"
    And I fill "Search Term With Target Product Form" with:
      | Phrases               | [black]                      |
      | Action                | Redirect to a different page |
      | Target Type           | Product                      |
      | Product               | PSKU10                       |
      | Restriction 1 Website | Default                      |
    And I save and close form
    Then I should see "Search Term has been saved" flash message

  Scenario: Unauthorized user will be forwarded to the Product view page
    Given I proceed as the Buyer
    When I am on the homepage
    And I type "light" in "search"
    And I click "Search Button"
    Then Page title equals to "Product 13"
    And I should see "All Products / Category 3 / Product 13"
    And the url should match "/product/search"

  Scenario: Authorized user will be forwarded to the Product view page
    When I signed in as AmandaRCole@example.org on the store frontend
    And I type "light" in "search"
    And I click "Search Button"
    Then Page title equals to "Product 13"
    And I should see "All Products / Category 3 / Product 13"
    And the url should match "/product/search"
