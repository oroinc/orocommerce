@regression
@ticket-BB-27519

Feature: Localized URL slug for existing master catalog category

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session |

  Scenario: Create a master catalog subcategory
    Given I proceed as the Admin
    And I login as administrator
    When I go to Products/Master Catalog
    And I click "All Products"
    And I click "Create Subcategory"
    And I fill "Category Form" with:
      | Title    | Test Localized Slug Category |
      | URL Slug | test-localized-slug-category |
    And I click "Save"
    Then I should see "Category has been saved" flash message

  Scenario: Add a localized URL slug when editing an existing master catalog category
    When I go to Products/Master Catalog
    And I click "Test Localized Slug Category"
    And I click "URL Slug Fallback Status"
    And I fill "Category URL Slug Form" with:
      | URL Slug English (United States) use fallback | false                            |
      | URL Slug English (United States) value        | test-localized-slug-category-us  |
    And I click "Save"
    Then I should see "Category has been saved" flash message
    When I go to Products/Master Catalog
    And I click "Test Localized Slug Category"
    And I click "URL Slug Fallback Status"
    Then "Category URL Slug Form" must contains values:
      | URL Slug English (United States) value | test-localized-slug-category-us |
