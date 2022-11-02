@ticket-BB-16680
@ticket-BB-19380
@ticket-BB-21746
@fixture-OroProductBundle:ConfigurableProductFixtures.yml
@fixture-OroProductBundle:DuplicatesForConfigurableProductFixtures.yml

Feature: Product with variants import validation
  In order to check file for errors before import
  As an administrator
  I want to be able to get a list of validation errors for imported file

  Scenario: Create product attributes
    Given I login as administrator
    And I go to Products/ Product Attributes
    And click "Create Attribute"
    And fill form with:
      | Field Name | Color  |
      | Type       | Select |
    And click "Continue"
    And set Options with:
      | Label |
      | Black |
      | White |
    And save and close form
    And I click "Create Attribute"
    And fill form with:
      | Field Name | Size   |
      | Type       | Select |
    And click "Continue"
    And set Options with:
      | Label |
      | L     |
      | M     |
    When I save and close form
    And click update schema
    Then I should see Schema updated flash message

  Scenario: Update product family
    Given I go to Products/ Product Families
    And I click "Edit" on row "default_family" in grid
    When I fill "Product Family Form" with:
      | Attributes | [Color, Size] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Import file with only configurable products
    Given I go to Products/Products
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | sku    | names.default.value  | attributeFamily.code | status  | inventory_status.id | type         | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision | primaryUnitPrecision.conversionRate | primaryUnitPrecision.sell |
      | XXXAAA | Configurable Product | default_family       | enabled | in_stock            | configurable | kg                             | 3                              | 1                                   | 1                         |
    When I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 1, updated: 0, replaced: 0" text
    When I go to System/ Jobs
    Then I should see oro:import:oro_product_product.add_or_replace:entity_import_from_csv:1 in grid with following data:
      | Status | Success |

  Scenario: Prepare first simple product
    When I go to Products/Products
    And I click Edit 1GB81 in grid
    And I fill in product attribute "Color" with "Black"
    And I fill in product attribute "Size" with "L"
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Prepare second simple product
    When I go to Products/Products
    And I click Edit 1GB82 in grid
    And I fill in product attribute "Color" with "White"
    And I fill in product attribute "Size" with "L"
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Prepare third simple product duplicate of first
    When I go to Products/Products
    And I click Edit DUPLICATE in grid
    And I fill in product attribute "Color" with "Black"
    And I fill in product attribute "Size" with "L"
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Prepare configurable product
    When I go to Products/Products
    And I click Edit 1GB83 in grid
    When I fill "ProductForm" with:
      | Configurable Attributes | [Color, Size] |
    And I check records in grid:
      | 1GB81 |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Import file with valid variant SKU
    Given I go to Products/Products
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | sku   | names.default.value | variantLinks.1.product.sku | variantLinks.1.visible | attributeFamily.code | status  | inventory_status.id | type         | variantFields | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision | primaryUnitPrecision.conversionRate | primaryUnitPrecision.sell |
      | 1GB83 | Slip-On Clog        | 1GB82                      | 1                      | default_family       | enabled | in_stock            | configurable | Color,Size    | kg                             | 3                              | 1                                   | 1                         |
    When I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 0, updated: 0, replaced: 1" text
    When I click View 1GB83 in grid
    Then I should see "1GB82"
    And I should not see "1GB81"

  Scenario: Import file with invalid variant SKU in configurable product
    Given I go to Products/Products
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | sku   | names.default.value   | variantLinks.1.product.sku | variantLinks.1.visible | variantLinks.2.product.sku | variantLinks.2.visible | attributeFamily.code | status  | inventory_status.id | type         | variantFields | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision | primaryUnitPrecision.conversionRate | primaryUnitPrecision.sell |
      | 1GB84 | Black Slip-On Clog XL |                            |                        |                            |                        | default_family       | enabled | in_stock            | simple       |               | kg                             | 3                              | 1                                   | 1                         |
      | 1GB83 | Slip-On Clog          | 1GB81/*<111>*/АРТ          | 1                      | 1GB82                      | 1                      | default_family       | enabled | in_stock            | configurable | Color,Size    | kg                             | 3                              | 1                                   | 1                         |

  @skipWait
  Scenario: Check import error page from the email after importing file
    Given I import file
    Then Email should contains the following "Errors: 2 processed: 1, read: 2, added: 1, updated: 0, replaced: 0" text
    And I follow "Error log" link from the email
    Then I should see "Error in row #1. Not found entity \"Product\". Item data: {\"sku\":\"1GB81/*<111>*/АРТ\"}."
    And I should see "Error in row #1. variantLinks[1].product: This value should not be blank."

  Scenario: Import file with invalid simple product used in configurable
    Given I login as administrator
    And I go to Products/Products
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | sku   | names.default.value      | attributeFamily.code | status  | inventory_status.id | type   | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision | primaryUnitPrecision.conversionRate | primaryUnitPrecision.sell |
      | 1GB84 | Black Slip-On Clog XL UP | default_family       | enabled | in_stock            | simple | kg                             | 3                              | 1                                   | 1                         |
      | 1GB82 | White Slip-On Clog M UP  | default_family       | enabled | in_stock            | simple | unknown                        | 3                              | 1                                   | 1                         |

  @skipWait
  Scenario: Check import error page from the email after importing file
    Given I import file
    Then Email should contains the following "Errors: 2 processed: 1, read: 2, added: 0, updated: 0, replaced: 1" text
    And I follow "Error log" link from the email
    Then I should see "Error in row #2. Not found entity \"Product Unit\". Item data: {\"code\":\"unknown\"}."
    And I should see "Error in row #2. Unit of Quantity Unit Code: This value should not be blank."

  Scenario: Import file with invalid simple product used in configurable and both are in the same batch
    Given I login as administrator
    And I go to Products/Products
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | sku   | names.default.value       | variantLinks.1.product.sku | variantLinks.1.visible | variantLinks.2.product.sku | variantLinks.2.visible | attributeFamily.code | status  | inventory_status.id | type         | variantFields | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision | primaryUnitPrecision.conversionRate | primaryUnitPrecision.sell |
      | 1GB84 | Black Slip-On Clog XL UP2 |                            |                        |                            |                        | default_family       | enabled | in_stock            | simple       |               | kg                             | 3                              | 1                                   | 1                         |
      | 1GB82 | White Slip-On Clog M UP   |                            |                        |                            |                        | default_family       | enabled | in_stock            | simple       |               | unknown                        | 3                              | 1                                   | 1                         |
      | 1GB83 | Slip-On Clog              | 1GB81                      | 1                      | 1GB82                      | 1                      | default_family       | enabled | in_stock            | configurable | Color,Size    | kg                             | 3                              | 1                                   | 1                         |

  @skipWait
  Scenario: Check import error page from the email after importing file
    Given I import file
    Then Email should contains the following "Errors: 2 processed: 2, read: 3, added: 0, updated: 0, replaced: 2" text
    And I follow "Error log" link from the email
    Then I should see "Error in row #2. Not found entity \"Product Unit\". Item data: {\"code\":\"unknown\"}."
    And I should see "Error in row #2. Unit of Quantity Unit Code: This value should not be blank."

  Scenario: Import file with invalid simple product used in invalid configurable and both are the same batch
    Given I login as administrator
    And I go to Products/Products
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | sku   | names.default.value       | variantLinks.1.product.sku | variantLinks.1.visible | variantLinks.2.product.sku | variantLinks.2.visible | attributeFamily.code | status  | inventory_status.id | type         | variantFields | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision | primaryUnitPrecision.conversionRate | primaryUnitPrecision.sell |
      | 1GB84 | Black Slip-On Clog XL UP2 |                            |                        |                            |                        | default_family       | enabled | in_stock            | simple       |               | kg                             | 3                              | 1                                   | 1                         |
      | 1GB81 | Black Slip-On Clog L UP   |                            |                        |                            |                        | default_family       | enabled | in_stock            | simple       |               | unknown                        | 3                              | 1                                   | 1                         |
      | 1GB83 | Slip-On Clog              | 1GB81                      | 1                      | 1GB82                      | 1                      | default_family       | enabled | in_stock            | configurable | Color,Size    | unk                            | 3                              | 1                                   | 1                         |

  @skipWait
  Scenario: Check import error page from the email after importing file
    Given I import file
    Then Email should contains the following "Errors: 6 processed: 1, read: 3, added: 0, updated: 0, replaced: 1" text
    And I follow "Error log" link from the email
    Then I should see "Error in row #2. Not found entity \"Product Unit\". Item data: {\"code\":\"unknown\"}."
    And I should see "Error in row #2. Field \"Color\" can not be empty. It is used in the following configurable products: 1GB83"
    And I should see "Error in row #2. Field \"Size\" can not be empty. It is used in the following configurable products: 1GB83"
    And I should see "Error in row #2. Unit of Quantity Unit Code: This value should not be blank."
    And I should see "Error in row #1. Not found entity \"Product Unit\". Item data: {\"code\":\"unk\"}."
    And I should see "Error in row #1. Unit of Quantity Unit Code: This value should not be blank."

  Scenario: Import two configurable products. One valid and one with non-unique variants
    Given I login as administrator
    And I go to Products/Products
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | sku    | names.default.value    | variantLinks.1.product.sku | variantLinks.2.product.sku | attributeFamily.code | status  | inventory_status.id | type         | variantFields | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision | primaryUnitPrecision.conversionRate | primaryUnitPrecision.sell |
      | CFG001 | Configurable Product 1 | 1GB82                      |                            | default_family       | enabled | in_stock            | configurable | Color,Size    | item                           | 3                              | 1                                   | 1                         |
      | CFG002 | Configurable Product 2 | 1GB81                      | DUPLICATE                  | default_family       | enabled | in_stock            | configurable | Color,Size    | item                           | 3                              | 1                                   | 1                         |

  @skipWait
  Scenario: Check import error page from the email after importing file
    Given I import file
    Then Email should contains the following "Errors: 1 processed: 1, read: 2, added: 1, updated: 0, replaced: 0" text
    And I follow "Error log" link from the email
    Then I should see "Error in row #2. Can't save product variants. Configurable attribute combinations should be unique."

  Scenario: Import configurable product with non-existing product variant
    Given I login as administrator
    And I go to Products/Products
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | sku   | attributeFamily.code | status  | inventory_status.id | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision | primaryUnitPrecision.conversionRate | primaryUnitPrecision.sell | names.default.fallback | names.default.value | variantFields | variantLinks.1.product.sku | featured | newArrival | slugPrototypes.default.value |
      | 1GB83 | default_family       | enabled | in_stock            | kg                             | 3                              | 1                                   | 1                         |                        | Slip-On Clog        | Color, Size   | Non-ex                     | 0        | 0          | slip-on-clog                 |

  @skipWait
  Scenario: Check import error page from the email after importing file
    Given I import file
    Then Email should contains the following "Errors: 2 processed: 0, read: 1, added: 0, updated: 0, replaced: 0" text
    And I follow "Error log" link from the email
    Then I should see "Error in row #1. Not found entity \"Product\". Item data: {\"sku\":\"Non-ex\"}."
    Then I should see "Error in row #1. variantLinks[0].product: This value should not be blank."

  Scenario: Import configurable product with non-existing product variant and existing product variant
    Given I login as administrator
    And I go to Products/Products
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | sku   | attributeFamily.code | status  | inventory_status.id | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision | primaryUnitPrecision.conversionRate | primaryUnitPrecision.sell | names.default.fallback | names.default.value | variantFields | variantLinks.1.product.sku | variantLinks.2.product.sku | featured | newArrival | slugPrototypes.default.value |
      | 1GB83 | default_family       | enabled | in_stock            | kg                             | 3                              | 1                                   | 1                         |                        | Slip-On Clog        | Color, Size   | Non-ex                     | 1GB82                      | 0        | 0          | slip-on-clog                 |

  @skipWait
  Scenario: Check import error page from the email after importing file
    Given I import file
    Then Email should contains the following "Errors: 2 processed: 0, read: 1, added: 0, updated: 0, replaced: 0" text
    And I follow "Error log" link from the email
    Then I should see "Error in row #1. Not found entity \"Product\". Item data: {\"sku\":\"Non-ex\"}."
    Then I should see "Error in row #1. variantLinks[0].product: This value should not be blank."

  Scenario: Import configurable products with non-existing product variant and existing product variant
    Given I login as administrator
    And I go to Products/Products
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | sku    | attributeFamily.code | status  | inventory_status.id | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision | primaryUnitPrecision.conversionRate | primaryUnitPrecision.sell | names.default.fallback | names.default.value | variantFields | variantLinks.1.product.sku | variantLinks.2.product.sku | featured | newArrival | slugPrototypes.default.value |
      | 1GB83  | default_family       | enabled | in_stock            | kg                             | 3                              | 1                                   | 1                         |                        | Slip-On Clog        | Color, Size   | Non-ex                     | 1GB82                      | 0        | 0          | slip-on-clog                 |
      | CFG001 | default_family       | enabled | in_stock            | kg                             | 3                              | 1                                   | 1                         |                        | Slip-On Clog        | Color, Size   |                            | 1GB82                      | 0        | 0          | slip-on-clog                 |

  @skipWait
  Scenario: Check import error page from the email after importing file
    Given I import file
    Then Email should contains the following "Errors: 2 processed: 1, read: 2, added: 0, updated: 0, replaced: 1" text
    And I follow "Error log" link from the email
    Then I should see "Error in row #1. Not found entity \"Product\". Item data: {\"sku\":\"Non-ex\"}."
    Then I should see "Error in row #1. variantLinks[0].product: This value should not be blank."
