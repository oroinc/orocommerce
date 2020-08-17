@regression
@fixture-OroWebCatalogBundle:web_catalog_for_breadcrumbs.yml
Feature: Webcatalog meta information
  As a User
  I want be sure
  That meta information on home page, product list page and product view page for
  the web catalog are displayed correctly

  Scenario: Create content nodes in web catalog and change meta information
    Given I login as administrator
    And I set "Default Web Catalog" as default web catalog
    And I go to Marketing/ Web Catalogs
    And I click view Default Web Catalog in grid
    And I click "Edit Content Tree"
    And I fill "Content Node" with:
      | Title            | Default Web Catalog |
      | Meta Title       | Home Meta Title |
      | Meta Description | Home Meta Description |
      | Meta Keywords    | Home Meta Keywords |
    And I click "Add System Page"
    And I click "Save"
    Then I should see "Content Node has been saved" flash message
    And I click "Create Content Node"
    And I fill "Content Node" with:
      | Title            | Headlamps |
      | Slug             | headlamps |
      | Meta Title       | Headlamps Meta Title |
      | Meta Description | Headlamps Meta Description |
      | Meta Keywords    | Headlamps Meta Keywords |
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
    Then Page title equals to "Headlamps"
    And Page meta title equals "Headlamps Meta Title"
    And Page meta description equals "Headlamps Meta Description"
    And Page meta keywords equals "Headlamps Meta Keywords"

  Scenario: Meta information for product view page should be displayed correctly
    Given I am on "headlamps/220-lumen-rechargeable-headlamp"
    Then Page title equals to "220 Lumen Rechargeable Headlamp"

  Scenario: Meta information for home page
    Given I am on homepage
    Then Page title equals to "Default Web Catalog"
    And Page meta title equals "Home Meta Title"
    And Page meta description equals "Home Meta Description"
    And Page meta keywords equals "Home Meta Keywords"
