@regression
@ticket-BB-17868

Feature: Configurable products import
  In order to import products
  As an Administrator
  I should not be able to import configurable product if there is no attributes in product family

  Scenario: Import file with products
    Given I login as administrator
    When I go to Products/Products
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | SKU   | Name.default.value   | Product Variant Links.1.Product.SKU | Product Variant Links.1.Visible | Product Variant Links.2.Product.Sku | Product Variant Links.2.Visible | Product Family.Code | Status  | Inventory Status.Id | Type         | Configurable Attributes | Unit of Quantity.Unit.Code | Unit of Quantity.Precision | Unit of Quantity.Conversion Rate | Unit of Quantity.Sell |
      | 1GB81 | Simple Product 1     |                                     |                                 |                                     |                                 | default_family      | enabled | in_stock            | simple       |                         | kg                         | 3                          | 1                                | 1                     |
      | 1GB82 | Simple Product 2     |                                     |                                 |                                     |                                 | default_family      | enabled | in_stock            | simple       |                         | kg                         | 3                          | 1                                | 1                     |
      | 1GB83 | Configurable product | 1GB81                               | 1                               | 1GB82                               | 1                               | default_family      | enabled | in_stock            | configurable | Color                   | kg                         | 3                          | 1                                | 1                     |
    And I import file
    Then Email should contains the following "Errors: 1 processed: 2, read: 3, added: 2, updated: 0, replaced: 0" text
    When I follow "Error log" link from the email
    Then I should see "Error in row #1. Configurable product requires at least one filterable attribute of the Select or Boolean type to enable product variants. The provided product family does not fit for configurable products."

  Scenario: Check Products grid
    When I am on dashboard
    And I go to Products/Products
    Then number of records should be 2
    When I sort grid by SKU
    Then I should see following grid:
      | SKU   | Name             |
      | 1GB81 | Simple Product 1 |
      | 1GB82 | Simple Product 2 |
