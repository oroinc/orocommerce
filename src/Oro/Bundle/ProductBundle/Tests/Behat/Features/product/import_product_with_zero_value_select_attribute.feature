@ticket-BAP-19519
@fixture-OroProductBundle:product_frontend.yml

Feature: Import Product with zero value select attribute
  In order to import products
  As an Administrator
  I want to have an ability Import any valid product data from the file into the system

  Scenario: Prepare product attributes
    Given I login as administrator

    # Create Size attribute
    And I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | Size   |
      | Type       | Select |
    And I click "Continue"
    And set Options with:
      | Label |
      | 0     |
      | 10    |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message
    And I confirm schema update

    # Update attribute family
    And I go to Products / Product Families
    And I click Edit Default in grid
    When set Attribute Groups with:
      | Label            | Visible | Attributes |
      | Additional Files | true    | [Size]     |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Check existing product Size attribute
    Given I go to Products/Products
    When click view "SKU1" in grid
    Then I should see "Size N/A"

  Scenario: Import size data
    When I go to Products/ Products
    And I open "Products" import tab
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | size.name | sku  | attributeFamily.code | names.default.value | descriptions.default.value | status  | type   | inventory_status.id | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision |
      | 0         | SKU1 | default_family       | Test Product 1      | Product Description 1      | enabled | simple | in_stock            | set                            | 1                              |
    And I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 0, updated: 0, replaced: 1" text

  Scenario: Check imported product Size attribute
    Given I go to Products/Products
    When click view "SKU1" in grid
    Then I should see "Size 0"
