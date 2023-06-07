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
      | Product Family.Code | SKU   | Status  | Type   | Unit of Quantity.Unit.Code | Inventory Status.Id | Unit of Quantity.Precision | Unit of Quantity.Conversion Rate | Unit of Quantity.Sell | Additional Units:0:Unit:Code | Additional Units:0:Precision | Additional Units:0:Conversion Rate | Additional Units:0:Sell | Name.default.fallback | Name.default.value | Name.English (United States).fallback | Name.English (United States).value | Short Description.default.fallback | Short Description.default.value | Short Description.English (United States).fallback | Short Description.English (United States).value | Description.default.fallback | Description.default.value                                                                                                                                                              | Description.English (United States).fallback | Description.English (United States).value | Configurable Attributes | Is Featured | New Arrival | Backorders.value | Category.ID |
      | default_family      | PSKU1 | enabled | simple | item                       | in_stock            | 0                          | 1                                | 1                     | set                          | 0                            | 10                                 | 1                       |                       | Test Product 1     | system                                |                                    |                                    | Test Product Short Description  | system                                             |                                                 |                              | <div><p class="product-view-desc">Test product description 1</p><img id="wysiwyg_img" alt="about_320.jpg" src="{{ wysiwyg_image('1','5f352b84-fd53-4632-8527-132557b708f5') }}"></div> | system                                       |                                           |                         | 0           | 0           | systemConfig     | 1           |
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
      | Product Family.Code | SKU   | Status  | Type   | Unit of Quantity.Unit.Code | Inventory Status.Id | Unit of Quantity.Precision | Unit of Quantity.Conversion Rate | Unit of Quantity.Sell | Additional Units:0:Unit:Code | Additional Units:0:Precision | Additional Units:0:Conversion Rate | Additional Units:0:Sell | Name.default.fallback | Name.default.value | Name.English (United States).fallback | Name.English (United States).value | Short Description.default.fallback | Short Description.default.value | Short Description.English (United States).fallback | Short Description.English (United States).value | Description.default.fallback | Description.default.value                                                                                                                                                                      | Description.English (United States).fallback | Description.English (United States).value | Configurable Attributes | Is Featured | New Arrival | Backorders.value | Category.ID |
      | default_family      | PSKU1 | enabled | simple | item                       | in_stock            | 0                          | 1                                | 1                     | set                          | 0                            | 10                                 | 1                       |                       | Test Product 2     | system                                |                                    |                                    | Test Product Short Description  | system                                             |                                                 |                              | <div><p class="product-view-desc">Test product description 1 updated</p><img id="wysiwyg_img" alt="about_320.jpg" src="{{ wysiwyg_image('1','5f352b84-fd53-4632-8527-132557b708f5') }}"></div> | system                                       |                                           |                         | 0           | 0           | systemConfig     | 1           |
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
