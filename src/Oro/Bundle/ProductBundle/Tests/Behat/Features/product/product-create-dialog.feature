@regression
@ticket-BB-7756
@automatically-ticket-tagged
@fixture-OroWebCatalogBundle:web_catalog.yml
Feature: Create product with dialog widget
  In order to manage products from other pages
  As administrator
  I need to be able to create product with dialog widget

  Scenario: Create new product from web catalog product variant creation page
    Given I login as administrator
    When I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click on "Show Variants Dropdown"
    And I click "Add Product Page"
    And I click "Content Variants"
    Then "Content Variant" must contains values:
      | Product | |
    When I click on "Create Product Plus Button"
    Then I should see "Create Product"
    And I should see "Continue"
    When I click "Continue" in modal window
    Then I should see "Create Product"
    And I should see "SKU"
    And I should see "Save"
    Then I close ui dialog
