@regression
@ticket-BB-20500

Feature: Product with slug same to web catalog node
  In order to have the ability to display a "friendly URL" address for customers
  As an administrator
  I want to be able to add and modify a "slug" to a product same to web catalog node slug

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create category
    Given I proceed as the Admin
    And login as administrator
    When I go to Products/Master Catalog
    And I click "All Products"
    And I click "Create Subcategory"
    And I fill "Category Form" with:
      | Title    | Test Category |
      | URL Slug | test          |
    And I click "Save"
    Then I should see "Category has been saved" flash message

  Scenario: Create web catalog node
    Given I go to Marketing/Web Catalogs
    And click "Create Web Catalog"
    And fill form with:
      | Name | Default Web Catalog |
    When I click "Save and Close"
    Then I should see "Web Catalog has been saved" flash message

    When I click "Edit Content Tree"
    And I click "Add System Page"
    And I fill "Content Node Form" with:
      | Titles            | Home page                               |
      | System Page Route | Oro Frontend Root (Welcome - Home page) |
    And I save form
    Then I should see "Content Node has been saved" flash message

    When I click "Create Content Node"
    And I click on "Show Variants Dropdown"
    And I click "Add Category"
    And I fill "Content Node Form" with:
      | Titles   | Test Node |
      | Url Slug | test-1    |
    And I click "Test Category"
    And I click "Save"
    Then I should see "Content Node has been saved" flash message
    And I set "Default Web Catalog" as default web catalog

  Scenario: Create product
    When I go to Products/ Products
    And I click "Create Product"
    And I fill "ProductForm Step One" with:
      | Type           | Simple  |
      | Product Family | Default |
    And click "Test Category"
    And I click "Continue"
    And I fill "ProductForm" with:
      | Sku      | test_product |
      | Name     | Test Product |
      | URL Slug | test         |
      | Status   | Enabled      |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check product url
    Given I proceed as the Buyer
    And I am on the homepage
    When I click "Test Node"
    And should see "Test Product"
    And click "View Details" for "Test Product" product
    Then the url should match "/test-1/_item/test-1"
    And I should see "Home page / Test Node / Test Product"
    And I should see "Is Featured: No"
    And I should not see "View Details"
