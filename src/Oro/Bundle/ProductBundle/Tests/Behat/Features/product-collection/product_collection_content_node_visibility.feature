@fixture-OroProductBundle:product_collection_webcatalog.yml
Feature: Product collection content node visibility
  In order to provider user with Product Collection functionality
  As a Buyer
  I have to see newly created Content Node with Product Collection variant in main menu on homepage

  Scenario: Add Product Collection variant to newly create Content Node
    And I login as administrator
    And I set "Default Web Catalog" as default web catalog
    When I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click on "Show Variants Dropdown"
    And I click "Add System Page"
    And I fill "Content Node Form" with:
      | Titles   | Root Node |
    When I save form
    Then I should see "Content Node has been saved" flash message
    And I click "Create Content Node"
    And I fill "Content Node Form" with:
      | Titles   | Product Collection Node |
      | Url Slug | product-collection-node |
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
      | SKU   | NAME      |
      | PSKU1 | Product 1 |
    When I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Newly created Product Collection Content Node is visible in menu on homepage
    Given I am on the homepage
    Then I should see "Product Collection Node"
