@ticket-BB-18266
@ticket-BB-19408
@ticket-BB-22103
Feature: Import Products
  In order to import products
  As an Administrator
  I want to have an ability Import any products from the file into the system

  Scenario: Verify administrator is able Import Products from the file
    Given I login as administrator
    When I go to Products/ Products
    And I open "Products" import tab
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | attributeFamily.code | names.default.value | descriptions.default.value | sku   | status  | type   | inventory_status.id | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision |
      | default_family       | 0000123             | Product Description 1      | PSKU1 | enabled | simple | in_stock            | set                            | 1                              |
    And I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 1, updated: 0, replaced: 0" text

  Scenario: Check imported products
    Given I go to Products/Products
    When I should see following grid:
      | SKU   | Name           |
      | PSKU1 | 0000123        |
    And click view "PSKU1" in grid
    Then I should see "Product Description 1"

  Scenario: Check leading zero/numeric string as product name
    Given I go to Products/ Products
    And I open "Products" import tab
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | attributeFamily.code | names.default.value | descriptions.default.value | sku   | status  | type   | inventory_status.id | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision |
      | default_family       | 00123.00            | Product Description 1      | PSKU1 | enabled | simple | in_stock            | set                            | 1                              |
    When I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 0, updated: 0, replaced: 1" text
    Given I go to Products/Products
    When I should see following grid:
      | SKU   | Name           |
      | PSKU1 | 00123.00       |

  Scenario: Check type validation
    Given I go to Products/ Products
    And I open "Products" import tab
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | attributeFamily.code | names.default.value | descriptions.default.value | sku   | status  | type   | inventory_status.id | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision |
      | default_family       | 00123.00            | Product Description 1      | PSKU1 | enabled | simple | in_stock            | set                            | invalid_value                  |
    When I import file
    Then Email should contains the following "Errors: 1 processed: 0, read: 1, added: 0, updated: 0, replaced: 0" text
    When I follow "Error log" link from the email
    Then I should see "Error in row #1. primaryUnitPrecision.precision: This value should contain only valid integer."
