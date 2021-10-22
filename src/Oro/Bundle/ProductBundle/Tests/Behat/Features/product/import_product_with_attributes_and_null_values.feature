@ticket-BAP-19519
@fixture-OroProductBundle:product_frontend.yml

Feature: Import Product with attributes and null values
  I want to have an ability import any attributes with empty values

  Scenario: Prepare product attributes
    Given I login as administrator

  Scenario: Create Select attribute
    Given I go to Products / Product Attributes
    And click "Create Attribute"
    And fill form with:
      | Field Name | SizeAttribute |
      | Type       | Select        |
    And click "Continue"
    And set Options with:
      | Label |
      | 0     |
      | 10    |
    When I save and close form
    Then I should see "Attribute was successfully saved" flash message

  Scenario: Create Integer attribute
    Given I go to Products / Product Attributes
    And click "Create Attribute"
    And fill form with:
      | Field Name | IntegerAttribute |
      | Type       | Integer          |
    And click "Continue"
    When I save and close form
    Then I should see "Attribute was successfully saved" flash message

  Scenario: Update schema
    Given I go to Products / Product Attributes
    And confirm schema update

  Scenario: Update product families
    Given I go to Products / Product Families
    And click Edit Default in grid
    And set Attribute Groups with:
      | Label            | Visible | Attributes                        |
      | Additional Files | true    | [SizeAttribute, IntegerAttribute] |
    When I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Check existing product Size attribute
    Given I go to Products/Products
    When click view "SKU1" in grid
    Then I should see "SizeAttribute N/A"
    And should see "IntegerAttribute N/A"

  Scenario: Import data with "0" value
    When I go to Products/ Products
    And I open "Products" import tab
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | SizeAttribute.name | IntegerAttribute | sku  | attributeFamily.code | names.default.value | descriptions.default.value | status  | type   | inventory_status.id | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision |
      | 0                  | 0                | SKU1 | default_family       | Test Product 1      | Product Description 1      | enabled | simple | in_stock            | set                            | 1                              |
    And I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 0, updated: 0, replaced: 1" text

  Scenario: Check imported product attribute
    Given I go to Products/Products
    When click view "SKU1" in grid
    Then I should see "SizeAttribute 0"
    Then I should see "IntegerAttribute 0"

  Scenario: Import data with "null" value
    When I go to Products/ Products
    And I open "Products" import tab
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | SizeAttribute.name | IntegerAttribute | sku  | attributeFamily.code | names.default.value | descriptions.default.value | status  | type   | inventory_status.id | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision |
      |                    |                  | SKU1 | default_family       | Test Product 1      | Product Description 1      | enabled | simple | in_stock            | set                            | 1                              |
    And I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 0, updated: 0, replaced: 1" text

  Scenario: Check imported product attribute
    Given I go to Products/Products
    When click view "SKU1" in grid
    Then I should see "SizeAttribute N/A"
    Then I should see "IntegerAttribute N/A"
