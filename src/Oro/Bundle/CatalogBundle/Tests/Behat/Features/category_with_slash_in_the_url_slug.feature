@ticket-BB-17440

Feature: Category with slash in the url slug
  In order to manage Master Catalog
  As an Administrator
  I want to be able to use slashes in URL slugs of categories in Master Catalog

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create new subcategory with error
    Given I proceed as the Admin
    And I login as administrator
    When I go to Products/Master Catalog
    And I click "All Products"
    And I click "Create Subcategory"
    And I fill "Category Form" with:
      | Title    | SubCategory  |
      | URL Slug | /foo/bar/baz |
    And I click "Save"
    Then I should see "This value should not start or end with \"/\" and should contain only latin letters, numbers and symbols \"-._~/\""

  Scenario: Create new subcategory
    Given I fill "Category Form" with:
      | URL Slug | foo/bar/baz |
    When I click "Save"
    Then I should see "Category has been saved" flash message

  Scenario: Check subcategory
    Given I proceed as the Buyer
    And I am on the homepage
    When I go to "/foo/bar/baz"
    Then I should see "SubCategory"
    And I should not see "404 Not Found"

  Scenario: Update slug of subcategory
    Given I proceed as the Admin
    And I fill "Category Form" with:
      | URL Slug | foo/bar/baz/new |
    When I click "Save"
    And I check "Create 301 Redirect from old to new URLs"
    And I click "Apply" in modal window
    Then I should see "Category has been saved" flash message

  Scenario: Check changed slug of subcategory
    Given I proceed as the Buyer
    When I go to "foo/bar/baz"
    Then I should see "SubCategory"
    And I should not see "404 Not Found"
    And the url should match "/foo/bar/baz/new"
