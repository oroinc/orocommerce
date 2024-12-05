@regression
@ticket-BB-17868
@ticket-BB-24787
@fixture-OroProductBundle:ConfigurableAttributeFamily.yml

Feature: Import export of configurable products with default variant
  In order to import or export configurable products
  As an Administrator
  I should not be able to import configurable product if there is no attributes in product family or default product is not one of the variants

  Scenario: Import file with products
    Given I login as administrator
    When I go to Products/Products
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | SKU   | Name.default.value   | Product Variant Links.1.Product.SKU | Product Variant Links.1.Visible | Product Variant Links.2.Product.Sku | Product Variant Links.2.Visible | Product Family.Code | Status  | Inventory Status.Id | Type         | Configurable Attributes | Unit of Quantity.Unit.Code | Unit of Quantity.Precision | Unit of Quantity.Conversion Rate | Unit of Quantity.Sell |
      | 1GB81 | Simple Product 1     |                                     |                                 |                                     |                                 | default_family      | enabled | in_stock            | simple       |                         | kg                         | 3                          | 1                                | 1                     |
      | 1GB82 | Simple Product 2     |                                     |                                 |                                     |                                 | default_family      | enabled | in_stock            | simple       |                         | kg                         | 3                          | 1                                | 1                     |
      | CNF1  | Configurable product | 1GB81                               | 1                               | 1GB82                               | 1                               | default_family      | enabled | in_stock            | configurable | Color                   | kg                         | 3                          | 1                                | 1                     |
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

  Scenario: Prepare product attributes
    # Create Color attribute
    And I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | Color  |
      | Type       | Select |
    And I click "Continue"
    And set Options with:
      | Label  |
      | Green  |
      | Red    |
      | Yellow |
    And I save form
    Then I should see "Attribute was successfully saved" flash message

    # Create Size attribute
    And I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | Size   |
      | Type       | Select |
    And I click "Continue"
    And set Options with:
      | Label  |
      | L      |
      | M      |
      | S      |
    And I save form
    Then I should see "Attribute was successfully saved" flash message

    # Update attribute family
    And I go to Products / Product Families
    And I click Edit Product Attribute Family in grid
    And set Attribute Groups with:
      | Label         | Visible | Attributes    |
      | T-shirt group | true    | [Color, Size] |
    And I save form
    Then I should see "Successfully updated" flash message

  Scenario: Import file with products with attributes and default variant
    When I go to Products/Products
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | SKU   | Name.default.value     | Product Family.Code           | Color.Name | Size.Name | Product Variant Links.1.Product.SKU | Product Variant Links.1.Visible | Default Variant.SKU | Status  | Inventory Status.Id | Type         | Configurable Attributes | Unit of Quantity.Unit.Code | Unit of Quantity.Precision | Unit of Quantity.Conversion Rate | Unit of Quantity.Sell |
      | 1GB83 | Simple Product 3       | product_attribute_family_code | Red        | M         |                                     |                                 |                     | enabled | in_stock            | simple       |                         | kg                         | 3                          | 1                                | 1                     |
      | 1GB84 | Simple Product 4       | product_attribute_family_code | Green      | L         |                                     |                                 |                     | enabled | in_stock            | simple       |                         | kg                         | 3                          | 1                                | 1                     |
      | CNF2  | Configurable product 2 | product_attribute_family_code |            |           | 1GB83                               | 1                               | 1GB83               | enabled | in_stock            | configurable | Color,Size              | kg                         | 3                          | 1                                | 1                     |
    And I import file
    Then Email should contains the following "Errors: 0 processed: 3, read: 3, added: 3, updated: 0, replaced: 0" text

  Scenario: Try to import file with products with attributes with invalid default variant
    When I go to Products/Products
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | SKU   | Name.default.value     | Product Family.Code           | Color.Name | Size.Name | Product Variant Links.1.Product.SKU | Product Variant Links.1.Visible | Default Variant.SKU | Status  | Inventory Status.Id | Type         | Configurable Attributes | Unit of Quantity.Unit.Code | Unit of Quantity.Precision | Unit of Quantity.Conversion Rate | Unit of Quantity.Sell |
      | CNF3  | Configurable product 3 | product_attribute_family_code |            |           | 1GB83                               | 1                               | 1GB84               | enabled | in_stock            | configurable | Color,Size              | kg                         | 3                          | 1                                | 1                     |
    And I import file
    Then Email should contains the following "Errors: 1 processed: 0, read: 1, added: 0, updated: 0, replaced: 0" text
    When I follow "Error log" link from the email
    Then I should see "Error in row #1. Default Variant SKU: The default variant must be one of the selected product variants."

  Scenario: Check Products grid and Configurable product variants
    When I am on dashboard
    And I go to Products/Products
    Then number of records should be 5
    When I sort grid by SKU
    Then I should see following grid:
      | SKU   | Name                   |
      | 1GB81 | Simple Product 1       |
      | 1GB82 | Simple Product 2       |
      | 1GB83 | Simple Product 3       |
      | 1GB84 | Simple Product 4       |
      | CNF2  | Configurable product 2 |
    When I click View CNF2 in grid
    And I press "Product Variants"
    Then I should see following grid:
      | Default variant | SKU   | Name             |
      | Yes             | 1GB83 | Simple Product 3 |

  Scenario: Export products with configurable and default variant
    When I am on dashboard
    And I go to Products/Products
    And I click "Export"
    Then I should see "Export started successfully. You will receive email notification upon completion." flash message
    And Email should contains the following "Export performed successfully. 5 products were exported. Download" text
    And take the link from email and download the file from this link
    And the downloaded file from email contains at least the following data:
      | SKU   | Name.default.value     | Status   | Product Variant Links.1.Product.SKU | Product Variant Links.1.Visible | Default Variant.SKU |
      | 1GB81 | Simple Product 1       | enabled  |                                     |                                 |                     |
      | 1GB82 | Simple Product 2       | enabled  |                                     |                                 |                     |
      | 1GB83 | Simple Product 3       | enabled  |                                     |                                 |                     |
      | 1GB84 | Simple Product 4       | enabled  |                                     |                                 |                     |
      | CNF2  | Configurable product 2 | enabled  | 1GB83                               | 1                               | 1GB83               |
