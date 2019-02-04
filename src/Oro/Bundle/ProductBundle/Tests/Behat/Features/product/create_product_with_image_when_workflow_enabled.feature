@ticket-BB-14928
@fixture-OroProductBundle:ProductWorkflowFixture.yml

Feature: Create product with image when workflow enabled
  In order to ensure a product can be successfully created with image when workflow is enabled
  As an administrator
  I need to create a product and upload an image with enabled workflow for Product entity

  Scenario: Feature Background
    Given complete workflow fixture loading

  Scenario: Create a Product With Image
    Given I login as administrator
    And go to Products/Products
    And click "Create Product"
    And fill form with:
      | Type | Simple |
    And click "Continue"
    And fill "ProductForm" with:
      | SKU    | PSKU1    |
      | Name   | Product1 |
      | Status | Enabled  |
    And I set Images with:
      | File     | Main | Listing | Additional |
      | cat1.jpg | 1    | 1       | 1          |
    When I save and close form
    Then I should see "Product has been saved" flash message
    And I should see "Product Workflow"
    And I should see "cat1.jpg"

  Scenario: Import Products
    Given I go to Products/ Products
    When I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | attributeFamily.code | sku   | status  | type   | inventory_status.id | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision | names.default.value |
      | default_family       | PSKU2 | enabled | simple | in_stock            | set                            | 3                              | Product2            |
      | default_family       | PSKU3 | enabled | simple | in_stock            | item                           | 1                              | Product3            |
    And import file
    Then Email should contains the following "Errors: 0 processed: 2, read: 2, added: 2, updated: 0, replaced: 0" text
    And reload the page
    And should see following grid:
      | SKU   | NAME     | Status  |
      | PSKU3 | Product3 | Enabled |
      | PSKU2 | Product2 | Enabled |
      | PSKU1 | Product1 | Enabled |
    And number of records should be 3

  Scenario: Import Product Images
    Given I open "Product Images" import tab
    When I download "Product Images" Data Template file
    And I upload product images files
    And fill template with data:
      | SKU   | Name     | Main | Listing | Additional |
      | PSKU2 | dog1.jpg | 1    | 1       | 1          |
      | PSKU3 | dog1.jpg | 0    | 0       | 1          |
    And import file
    Then Email should contains the following "Errors: 0 processed: 2, read: 2, added: 2, updated: 0, replaced: 0" text
    And I click view "PSKU2" in grid
    And I should see "Product Workflow"
    And I should see "dog1.jpg"
    And I go to Products/ Products
    And I click view "PSKU3" in grid
    And I should see "Product Workflow"
    And I should see "dog1.jpg"
