@ticket-BB-17868

Feature: Import products with attributes from different families
  In order to import products
  As an Administrator
  I need to be able to have simple products with attributes from the family they belong to and the configurable product
  itself and its simple variants should belong to one product family

  Scenario: Create product attribute
    Given I login as administrator
    When I go to Products/ Product Attributes
    And I click "Import file"
    And I upload "color_attribute.csv" file to "Import Choose File"
    And I click "Import file"
    And I reload the page
    And I confirm schema update
    Then I should see Schema updated flash message

  Scenario: Update product family with new attribute
    When I go to Products/ Product Families
    And I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [Color] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Create Product Family
    When I click "Create Product Family"
    And fill "Product Family Form" with:
      | Code    | family |
      | Label   | family |
      | Enabled | True   |
    And I save and close form
    Then I should see "Product Family was successfully saved" flash message

  Scenario: Import configurable product and product variants that belongs to different product families
    When I go to Products/Products
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | sku   | names.default.value  | Color.name | variantLinks.1.product.sku | variantLinks.1.visible | variantLinks.2.product.sku | variantLinks.2.visible | attributeFamily.code | status  | inventory_status.id | type         | variantFields | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision | primaryUnitPrecision.conversionRate | primaryUnitPrecision.sell |
      | 1GB81 | Simple Product 1     | Black      |                            |                        |                            |                        | family               | enabled | in_stock            | simple       |               | kg                             | 3                              | 1                                   | 1                         |
      | 1GB82 | Simple Product 2     | White      |                            |                        |                            |                        | family               | enabled | in_stock            | simple       |               | kg                             | 3                              | 1                                   | 1                         |
      | 1GB83 | Configurable product |            | 1GB81                      | 1                      | 1GB82                      | 1                      | default_family       | enabled | in_stock            | configurable | Color         | kg                             | 3                              | 1                                   | 1                         |
    And I import file
    Then Email should contains the following "Errors: 3 processed: 2, read: 3, added: 2, updated: 0, replaced: 0" text
    When I follow "Error log" link from the email
    Then I should see "Error in row #1. Can't save product variants. Configurable product and product variant(s) \"1GB81, 1GB82\" should belongs to the same product family."
    And I should see "Warning in row #1. Row contains a non-empty value in \"Color\" column. This product does not have \"Color\" attribute and this value was ignored."
    And I should see "Warning in row #2. Row contains a non-empty value in \"Color\" column. This product does not have \"Color\" attribute and this value was ignored."
    When I am on dashboard
    And I go to Products/Products
    Then number of records should be 2
    When I show column Color in grid
    And I sort grid by SKU
    Then I should see following grid:
      | SKU   | Name             | Color |
      | 1GB81 | Simple Product 1 |       |
      | 1GB82 | Simple Product 2 |       |
