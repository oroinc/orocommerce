@ticket-BB-19308
Feature: Import Products With WYSIWYG
  In order to import products
  As an Administrator
  I want to have an ability Import any products with wysiwyg fields

  Scenario: Verify administrator is able Import Products from the file
    Given I login as administrator
    When I go to Products/ Products
    And I open "Products" import tab
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | attributeFamily.code | sku   | status  | type   | primaryUnitPrecision.unit.code | inventory_status.id | primaryUnitPrecision.precision | primaryUnitPrecision.conversionRate | primaryUnitPrecision.sell | additionalUnitPrecisions:0:unit:code | additionalUnitPrecisions:0:precision | additionalUnitPrecisions:0:conversionRate | additionalUnitPrecisions:0:sell | names.default.fallback | names.default.value | names.English (United States).fallback | names.English (United States).value | shortDescriptions.default.fallback | shortDescriptions.default.value | shortDescriptions.English (United States).fallback | shortDescriptions.English (United States).value | descriptions.default.fallback | descriptions.default.value                                                                                                                                                             | descriptions.English (United States).fallback | descriptions.English (United States).value | variantFields | featured | newArrival | backOrder.value | category.id |
      | default_family       | PSKU1 | enabled | simple | item                           | in_stock            | 0                              | 1                                   | 1                         | set                                  | 0                                    | 10                                        | 1                               |                        | Test Product 1      | system                                 |                                     |                                    | Test Product Short Description  | system                                             |                                                 |                               | <div><p class="product-view-desc">Test product description 1</p><img id="wysiwyg_img" alt="about_320.jpg" src="{{ wysiwyg_image('1','5f352b84-fd53-4632-8527-132557b708f5') }}"></div> | system                                        |                                            |               | 0        | 0          | systemConfig    | 1           |
    And I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 1, updated: 0, replaced: 0" text

  Scenario: Check imported products
    Given I go to Products/Products
    When I should see following grid:
      | SKU   | Name           |
      | PSKU1 | Test Product 1 |
    And click view "PSKU1" in grid
    Then I should see "PSKU1 - Test Product 1"
    And I should see "Test product description 1"
    And I should see an "Digital Asset Image" element

  Scenario: Reimport product not broke product data
    When I go to Products/ Products
    And I open "Products" import tab
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | attributeFamily.code | sku   | status  | type   | primaryUnitPrecision.unit.code | inventory_status.id | primaryUnitPrecision.precision | primaryUnitPrecision.conversionRate | primaryUnitPrecision.sell | additionalUnitPrecisions:0:unit:code | additionalUnitPrecisions:0:precision | additionalUnitPrecisions:0:conversionRate | additionalUnitPrecisions:0:sell | names.default.fallback | names.default.value | names.English (United States).fallback | names.English (United States).value | shortDescriptions.default.fallback | shortDescriptions.default.value | shortDescriptions.English (United States).fallback | shortDescriptions.English (United States).value | descriptions.default.fallback | descriptions.default.value                                                                                                                                                                     | descriptions.English (United States).fallback | descriptions.English (United States).value | variantFields | featured | newArrival | backOrder.value | category.id |
      | default_family       | PSKU1 | enabled | simple | item                           | in_stock            | 0                              | 1                                   | 1                         | set                                  | 0                                    | 10                                        | 1                               |                        | Test Product 2      | system                                 |                                     |                                    | Test Product Short Description  | system                                             |                                                 |                               | <div><p class="product-view-desc">Test product description 1 updated</p><img id="wysiwyg_img" alt="about_320.jpg" src="{{ wysiwyg_image('1','5f352b84-fd53-4632-8527-132557b708f5') }}"></div> | system                                        |                                            |               | 0        | 0          | systemConfig    | 1           |
    And I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 0, updated: 0, replaced: 1" text

  Scenario: Check imported products
    Given I go to Products/Products
    When I should see following grid:
      | SKU   | Name           |
      | PSKU1 | Test Product 2 |
    And click view "PSKU1" in grid
    Then I should see "PSKU1 - Test Product 2"
    And I should see "Test product description 1 updated"
    And I should see an "Digital Asset Image" element
