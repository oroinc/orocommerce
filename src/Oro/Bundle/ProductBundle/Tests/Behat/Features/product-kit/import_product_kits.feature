@feature-BB-21122
@ticket-BB-22959
@ticket-BB-22594
@fixture-OroProductBundle:ProductKitsImportFixture.yml

Feature: Import Product Kits
  In order to import product kits
  As an Administrator
  I want to have an ability Import any products from the file into the system

  Scenario: Verify administrator is able Import Product Kits from the file
    Given I login as administrator
    And I go to Products/ Products
    And I open "Products" import tab
    When I click "Import file"
    And I upload "import_product_kits.csv" file to "Import Choose File"
    And I click "Import file"
    Then Email should contains the following "Errors: 0 processed: 2, read: 2, added: 2, updated: 0, replaced: 0" text

  Scenario: Check imported product kits
    Given I go to Products/Products
    Then I should see following grid:
      | SKU       | Name    | Status  |
      | PSKU_KIT2 | 0000124 | Enabled |
      | PSKU_KIT1 | 0000123 | Enabled |
    When click view "PSKU_KIT1" in grid
    Then I should see ",My, =Escaped= \"Kit\" 'Item' Optional No Minimum Quantity 1 Maximum Quantity 2 Unit Of Quantity set (fractional, 1 decimal digit) Products PSKU1 - Product 1 PSKU2 - Product 2 PSKU3 - Product 3" in the "Product Kit Item 1" element
    And I should see "Barcode Scanner Optional No Minimum Quantity 1 Maximum Quantity 1 Unit Of Quantity item (fractional, 1 decimal digit) Products PSKU2 - Product 2" in the "Product Kit Item 2" element
    And I should see "Receipt Printer(S) Optional Yes Minimum Quantity 1 Maximum Quantity N/A Unit Of Quantity set (fractional, 1 decimal digit) Products PSKU1 - Product 1 PSKU3 - Product 3" in the "Product Kit Item 3" element

  Scenario: Check updated product kit before import operation
    Given I go to Products/Products
    When click view "PSKU_KIT2" in grid
    Then I should see product with:
      | SKU  | PSKU_KIT2 |
      | Name | 0000124   |
      | Type | Kit       |
    And I should see "Additional Card Reader(S) Optional Yes Minimum Quantity 1 Maximum Quantity N/A Unit Of Quantity item (fractional, 1 decimal digit) Products PSKU2 - Product 2" in the "Product Kit Item 1" element
    When I click "Kit Item 1 Toggler"
    Then records in "Kit Item 1 Products Grid" should be 1

  Scenario: Update product kit import operation
    Given I go to Products/Products
    And I open "Products" import tab
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And I fill product KitItems template divided by "\n" delimiter with data:
      | Product Family.Code | Name.default.value | Description.default.value    | SKU       | Status   | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision | Kit Items                                                                                             |
      | default_family      | 0000125            | Product Description Update 2 | PSKU_KIT2 | disabled | in_stock            | set                        | 1                          | id=4,label="Additional Card Update",optional=false,products=PSKU2\|PSKU1,min_qty=1,max_qty=2,unit=set |
    When I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 0, updated: 0, replaced: 1" text

  Scenario: Check updated product kit after import operation
    Given I go to Products/Products
    Then I should see following grid:
      | SKU       | Name    | Status   |
      | PSKU_KIT2 | 0000125 | Disabled |
    When click view "PSKU_KIT2" in grid
    Then I should see product with:
      | SKU  | PSKU_KIT2 |
      | Name | 0000125   |
      | Type | Kit       |
    And I should see "Additional Card Update Optional No Minimum Quantity 1 Maximum Quantity 2 Unit Of Quantity set (fractional, 1 decimal digit) Products PSKU1 - Product 1 PSKU2 - Product 2" in the "Product Kit Item 1" element
    When I click "Kit Item 1 Toggler"
    Then records in "Kit Item 1 Products Grid" should be 2

  Scenario: Check import product kits that references kit item products that do not yet exist in DB
    Given I go to Products/Products
    And I open "Products" import tab
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And I fill product KitItems template divided by "\n" delimiter with data:
      | Product Family.Code | Name.default.value | Description.default.value | SKU       | Status  | Type   | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision | Kit Items                                                                       |
      | default_family      | 0000126            | Product Description 3     | PSKU_KIT3 | enabled | kit    | in_stock            | item                       | 1                          | label="Card",optional=false,products=PSKU2\|PSKU5,min_qty=1,max_qty=2,unit=item |
      | default_family      | 0000127            | Product Description 4     | PSKU5     | enabled | simple | in_stock            | item                       | 1                          |                                                                                 |
    When I import file
    Then Email should contains the following "Errors: 0 processed: 2, read: 2, added: 2, updated: 0, replaced: 0" text
    When I go to Products/Products
    Then I should see following grid:
      | SKU       | Name    | Status  |
      | PSKU_KIT3 | 0000126 | Enabled |
      | PSKU5     | 0000127 | Enabled |
    When click view "PSKU_KIT3" in grid
    Then I should see "Card Optional No Minimum Quantity 1 Maximum Quantity 2 Unit Of Quantity item (fractional, 1 decimal digit) Products PSKU2 - Product 2 PSKU5 - 0000127" in the "Product Kit Item 1" element

  Scenario: Check import product kits when all products from all required kit items available
    Given I go to Products/Products
    And I open "Products" import tab
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And I fill product KitItems template divided by "\n" delimiter with data:
      | Product Family.Code | Name.default.value | Description.default.value | SKU       | Status   | Type   | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision | Kit Items                                                                                                                                                    |
      | default_family      | 0000128            | Product Description 4     | PSKU_KIT4 | disabled | kit    | in_stock            | item                       | 1                          | label="Scanner",optional=false,products=PSKU2\|PSKU5,min_qty=1,max_qty=2,unit=item\nlabel="Card",optional=false,products=PSKU2,min_qty=1,max_qty=2,unit=item |
    When I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 1, updated: 0, replaced: 0" text
    When I go to Products/Products
    Then I should see following grid:
      | SKU       | Name    | Status   |
      | PSKU_KIT4 | 0000128 | Disabled |

  Scenario: Check duplicate operation when all products from any of the required kit items unavailable
    Given I go to Products/Products
    When click edit "PSKU5" in grid
    And I fill "ProductForm" with:
      | Status | Disabled |
    And I save and close form
    Then I should see "Product has been saved" flash message
    And I go to Products/Products
    And I open "Products" import tab
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And I fill product KitItems template divided by "\n" delimiter with data:
      | Product Family.Code | Name.default.value | Description.default.value | SKU       | Status  | Type   | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision | Kit Items                                                                                                                                                   |
      | default_family      | 0000129            | Product Description 5     | PSKU_KIT5 | enabled | kit    | in_stock            | item                       | 1                          | label="Scanner",optional=true,products=PSKU2\|PSKU5,min_qty=1,max_qty=2,unit=item\nlabel="Card",optional=false,products=PSKU5,min_qty=1,max_qty=2,unit=item |
    When I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 1, updated: 0, replaced: 0" text
    When I go to Products/Products
    Then I should see following grid:
      | SKU       | Name    | Status   |
      | PSKU_KIT5 | 0000129 | Disabled |
