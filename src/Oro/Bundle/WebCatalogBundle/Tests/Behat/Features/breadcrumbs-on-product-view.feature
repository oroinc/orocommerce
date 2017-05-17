@fixture-web_catalog_for_breadcrumbs.yml
Feature: Product view page breadcrumbs

  Scenario: Create content nodes in web catalog and mark web catalog as default
    Given I login as administrator
    And I set "Default Web Catalog" as default web catalog for global scope
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
  Scenario: Breadcrumbs should be built based on web catalog
    Given I am on homepage
    And I click "Headlamps"
    When I click "220 Lumen Rechargeable Headlamp"
    Then I should see "Lighting Products / Headlamps / 220 Lumen Rechargeable Headlamp"
