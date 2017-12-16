@regression
@fixture-OroWebCatalogBundle:web_catalog_for_breadcrumbs.yml
Feature: Product view page breadcrumbs webcatalog
  As a User
  I want be sure
  That breadcrumbs for the webcatalog are work correctly

  Scenario: Create content nodes in web catalog and mark web catalog as default
    Given I login as administrator
    And I set "Default Web Catalog" as default web catalog
    And I go to Marketing/ Web Catalogs
    And I click view Default Web Catalog in grid
    And I click "Edit Content Tree"
    And I fill "Content Node" with:
      | Title | Lighting Products |
    And I click "Add System Page"
    And I click "Save"
    And I click "Create Content Node"
    And I fill "Content Node" with:
      | Title | Headlamps |
      | Slug  | headlamps |
    And I click on "Show Variants Dropdown"
    And I click "Add Category"
    And I click "Headlamps"
    And I click "Save"
    And I click "Lighting Products"
    And I click "Create Content Node"
    And I fill "Content Node" with:
      | Title | Product page as Content Node |
      | Slug  | product-page-as-content-node |
    And I click on "Show Variants Dropdown"
    And I click "Add Product Page"
    And I fill "Content Variant" with:
      | Product | 220 Lumen Rechargeable Headlamp |
    And I click "Save"
  Scenario: Breadcrumbs should be built based on web catalog
    Given I am on homepage
    And I click "Headlamps"
    When I click "View Details" for "PSKU1" product
    Then I should see "Lighting Products / Headlamps / 220 Lumen Rechargeable Headlamp"
    When I follow "Lighting Products"
    Then I should be on homepage
    When I click "Product page as Content Node"
    Then Page title equals to "Product page as Content Node"
    And I should not see "220 Lumen Rechargeable Headlamp"
    When I follow "Lighting Products"
    And I click on "Headlamps category"
    And I click "View Details" for "PSKU1" product
    Then I should see "All Products / Headlamps / 220 Lumen Rechargeable Headlamp"
