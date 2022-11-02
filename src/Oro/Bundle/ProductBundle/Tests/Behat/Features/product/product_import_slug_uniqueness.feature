@ticket-BB-17196
Feature: Product import slug uniqueness
  In order to have unique slugs for product
  As an administrator
  I want to be able to get suffixed Slug URLs for imported file with the same product names

  Scenario: Import products
    Given I login as administrator
    And I go to Products/Products
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | attributeFamily.code | names.default.value | sku   | status  | type   | inventory_status.id | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision |
      | default_family       | Test Product        | PSKU1 | enabled | simple | in_stock            | set                            | 1                              |
      | default_family       | Test Product        | PSKU2 | enabled | simple | in_stock            | set                            | 1                              |
    When I import file
    Then Email should contains the following "Errors: 0 processed: 2, read: 2, added: 2, updated: 0, replaced: 0" text

  Scenario: Check imported products
    Given I go to Products/Products
    And I sort grid by "SKU"
    Then I should see following grid:
      | SKU   | Name         |
      | PSKU1 | Test Product |
      | PSKU2 | Test Product |
    When click view "PSKU1" in grid
    Then I should see "Slugs /test-product"
    When I go to Products/Products
    And click view "PSKU2" in grid
    Then I should see "Slugs /test-product-1"
