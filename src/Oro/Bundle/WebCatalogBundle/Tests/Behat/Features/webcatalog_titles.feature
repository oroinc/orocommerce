@regression
@ticket-BB-16300
@fixture-OroWebCatalogBundle:web_catalog_for_breadcrumbs.yml
@fixture-OroCMSBundle:CMSPageFixture.yml
Feature: Webcatalog titles
  As an Administrator
  I want be sure
  That title rewriting for the webcatalog works correctly

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create content nodes in web catalog and mark web catalog as default
    Given I proceed as the Admin
    And I login as administrator
    And I set "Default Web Catalog" as default web catalog
    And I go to Marketing/ Web Catalogs
    And I click view Default Web Catalog in grid
    And I click "Edit Content Tree"

    When I click "Add System Page"
    And I fill "Content Node" with:
      | Title | Root Node |
    And I click "Save"
    Then should see "Content Node has been saved" flash message

    When I click "Create Content Node"
    And I click on "Show Variants Dropdown"
    And I click "Add System Page"
    And I fill "Content Node" with:
      | Title             | SYSTEM PAGE TITLE                       |
      | System Page Route | Oro Frontend Root (Welcome - Home page) |
    And I click "Save"
    Then should see "Content Node has been saved" flash message

    When I click "Root Node"
    And I click "Create Content Node"
    And I click on "Show Variants Dropdown"
    And I click "Add Product Page"
    And I fill "Content Node" with:
      | Title   | PRODUCT PAGE TITLE              |
      | Product | 220 Lumen Rechargeable Headlamp |
    And I click "Save"
    Then should see "Content Node has been saved" flash message

    When I click "Root Node"
    And I click "Create Content Node"
    And I click on "Show Variants Dropdown"
    And I click "Add Landing Page"
    And I fill "Content Node Form" with:
      | Titles       | LANDING PAGE TITLE |
      | Landing Page | Test CMS Page      |
    And I click "Save"
    Then should see "Content Node has been saved" flash message

    When I click "Root Node"
    When I click "Create Content Node"
    And I click on "Show Variants Dropdown"
    And I click "Add Category"
    And I click "Headlamps"
    And I fill "Content Node" with:
      | Title | CATEGORY PAGE TITLE |
    And I click "Save"
    Then should see "Content Node has been saved" flash message

    When I click "Root Node"
    And I click "Create Content Node"
    And I fill "Content Node" with:
      | Title | COLLECTION PAGE TITLE |
    And I click on "Show Variants Dropdown"
    And I click "Add Product Collection"
    When I click "Content Variants"
    And I click on "Advanced Filter"
    And I drag and drop "Field Condition" on "Drop condition here"
    And I click "Choose a field.."
    And I click on "SKU"
    And type "PSKU1" in "value"
    And I click on "Preview Results"
    Then I should see following grid:
      | SKU   | NAME                            |
      | PSKU1 | 220 Lumen Rechargeable Headlamp |
    When I save form
    Then I should see "Content Node has been saved" flash message

  Scenario Outline: Validate page title
    Given I proceed as the Buyer
    And I am on the homepage
    When I click "<Variant title>"
    Then Page title equals to "<Variant title>"

    When I proceed as the Admin
    And I click "<Variant title>"
    And I uncheck "Rewrite Variant Title"
    And I click "Save"
    Then should see "Content Node has been saved" flash message

    When I proceed as the Buyer
    And I reload the page
    Then Page title equals to "<Original title>"

    Examples:
      | Variant title         | Original title                  |
      | SYSTEM PAGE TITLE     | Welcome - Home page             |
      | PRODUCT PAGE TITLE    | 220 Lumen Rechargeable Headlamp |
      | LANDING PAGE TITLE    | Test CMS Page                   |
      | CATEGORY PAGE TITLE   | Headlamps                       |
      | COLLECTION PAGE TITLE | All Products                    |
