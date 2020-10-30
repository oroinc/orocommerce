@regression
@ticket-BB-19815
@fixture-OroWebCatalogBundle:customer.yml
@fixture-OroWebCatalogBundle:web_catalog_for_breadcrumbs.yml
Feature: Content Node slug validation

  Scenario: Create content nodes tree
    Given I login as administrator
    And I go to Marketing/ Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click on "Show Variants Dropdown"
    And I click "Add System Page"
    And I fill "Content Node Form" with:
      | Titles | Web Catalog Root |
    When I save form
    Then I should see "Content Node has been saved" flash message

    When I click "Create Content Node"
    And I fill "Content Node" with:
      | Title | Products node |
      | Slug  | products-node |
    And I click on "Show Variants Dropdown"
    And I click "Add System Page"
    When I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Create content node with the same URL Slug on the same level should trigger validation error
    Given I click "Web Catalog Root"
    And I click "Create Content Node"
    And I fill "Content Node" with:
      | Title | Products duplicate |
      | Slug  | products-node      |
    And I click on "Show Variants Dropdown"
    And I click "Add System Page"
    When I save form
    Then I should see "URL Slug must be unique within the same parent Content Node"

  Scenario: Create content node with the same URL Slug in another case on the same level should trigger validation error
    And I fill "Content Node" with:
      | Slug | products-NODE |
    When I save form
    Then I should see "URL Slug must be unique within the same parent Content Node"

  Scenario: Create content node with the same URL Slug on another level should be possible
    Given I click "Products node"
    And I click "Create Content Node"
    And I fill "Content Node" with:
      | Title | Products duplicate |
      | Slug  | products-NODE      |
    And I click on "Show Variants Dropdown"
    And I click "Add System Page"
    When I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Move content node with the same URL Slug
    When I drag and drop "Products duplicate" before "Products node"
    And I click "Apply" in modal window
    Then "Content Node" must contains values:
      | Slug | products-node-1 |
