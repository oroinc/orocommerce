@regression
@fixture-OroWebCatalogBundle:web_catalog_for_breadcrumbs.yml
Feature: Products webcatalog meta information
  As a User
  I want be sure
  That meta information on product list page and product view page for the webcatalog are displayed correctly

  Scenario: Create content nodes in web catalog and change meta information
    Given I login as administrator
    And I set "Default Web Catalog" as default web catalog
    And I go to Marketing/ Web Catalogs
    And I click view Default Web Catalog in grid
    And I click "Edit Content Tree"
    And I fill "Content Node" with:
      | Title | Default Web Catalog |
    And I click "Add System Page"
    And I click "Save"
    Then I should see "Content Node has been saved" flash message
    And I click "Create Content Node"
    And I fill "Content Node" with:
      | Title      | Headlamps |
      | Slug       | headlamps |
      | Meta Title | Best products for you |
    And I click on "Show Variants Dropdown"
    And I click "Add Category"
    And I click "Headlamps"
    And I click "Save"
    Then I should see "Content Node has been saved" flash message
    And I click "Create Content Node"
    And I fill "Content Node" with:
      | Title      | 220 Lumen Rechargeable Headlamp |
      | Slug       | 220-lumen-rechargeable-headlamp |
      | Meta Title | Best product |
    And I click on "Show Variants Dropdown"
    And I click "Add Product Page"
    And I fill "Content Variant" with:
      | Product | 220 Lumen Rechargeable Headlamp |
    And I click "Save"
    Then I should see "Content Node has been saved" flash message

  Scenario: Meta information for product list page should be displayed correctly
    Given I am on homepage
    And I follow "Headlamps"
    Then Page title equals to "Best products for you"

  Scenario: Meta information for product view page should be displayed correctly
    Given I am on "headlamps/220-lumen-rechargeable-headlamp"
    Then Page title equals to "Best product"
