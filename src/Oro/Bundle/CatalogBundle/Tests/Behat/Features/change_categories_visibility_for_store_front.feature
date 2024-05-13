@regression
@ticket-BB-10960
@ticket-BB-22268
@automatically-ticket-tagged
@fixture-OroCatalogBundle:categories-for-visibility.yml
Feature: Change categories visibility for store front
  In order to make categories invisible at store front
  As site administrator
  I need to be able to change category visibility

  Scenario: Logged in as buyer and manager on different window sessions
    Given sessions active:
      | Admin  | first_session  |
      | Buyer  | second_session |

  Scenario: Change "Medical Apparel" category visibility
    Given I proceed as the Admin
    And I login as administrator
    When I go to Products/Master Catalog
    And I click "Medical Apparel"
    And I click "Visibility" in scrollspy
    And I fill form with:
      | Visibility to All | Hidden |
    And I save form
    Then I should see "Category has been saved" flash message

  Scenario: Change "Retail Supplies > Fax" category visibility
    Given I should see "Products/ Master Catalog" in breadcrumbs
    And I expand "Retail Supplies" in tree
    And I click "Fax"
    And I click "Visibility" in scrollspy
    And I fill form with:
      | Visibility to All | Hidden |
    And I save form
    Then I should see "Category has been saved" flash message

  Scenario: Hidden categories are not available in menu on homepage
    Given I proceed as the Buyer
    When I am on the homepage
    Then I should not see "Medical Apparel" in main menu
    And I should see "Retail Supplies" in main menu
    And I should see "Retail Supplies/ Printers" in main menu
    And I should not see "Retail Supplies/ Fax" in main menu

  Scenario: Set default category visibility as hidden
    Given I proceed as the Admin
    When I go to System / Configuration
    And follow "Commerce/Customer/Visibility" on configuration sidebar
    And fill "Visibility Settings Form" with:
      |Category Visibility Use Default|false  |
      |Category Visibility            |hidden |
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: Set All Product category is visible to all
    When I go to Products/Master Catalog
    And I click "All Products"
    And I click "Visibility" in scrollspy
    And I fill form with:
      | Visibility to All | Visible |
    And I save form
    Then I should see "Category has been saved" flash message

  Scenario: Create subcategory with visibility fallback to parent's one
    When I go to Products/Master Catalog
    And I click "All Products"
    And I click "Create Subcategory"
    And I fill "Category Form" with:
      | Title    | Should be visible |
      | URL Slug | should-be-visible |
    And I click "Visibility" in scrollspy
    And I fill form with:
      | Visibility to All | Parent Category |
    And I save form
    Then I should see "Category has been saved" flash message

  Scenario: New created subcategory shows up in menu on homepage
    Given I proceed as the Buyer
    When I am on the homepage
    Then I should see "Should be visible" in main menu

  Scenario: Import categories
    Given I proceed as the Admin
    When I go to Products/Master Catalog
    And I click "Import file"
    And I upload "import_categories_form_visibility.csv" file to "Import Choose File"
    And I click "Import file"
    Then Email should contains the following "Errors: 0 processed: 4, read: 4, added: 4, updated: 0, replaced: 0" text
    When I reload the page
    Then I should see "Accessories Cabinets Carts"
    When I expand "Accessories" in tree
    Then I should see "Accessories Subcategory"

  Scenario: New importing subcategories are also show up in main menu
    Given I proceed as the Buyer
    When I am on the homepage
    Then I should see "Accessories" in main menu
    And I should see "Accessories/ Accessories Subcategory" in main menu
    And I should see "Cabinets"
    And I should see "Carts"
