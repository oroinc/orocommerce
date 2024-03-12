@feature-BB-21122
@ticket-BB-22959
@fixture-OroProductBundle:ProductKitsImportFixture.yml

Feature: Import Product Kits Check Validation
  In order to import product kits
  As an Administrator
  I want to have the ability to get validation errors from email after import

  Scenario: Check imported product kits with empty and wrong values
    Given I login as administrator
    And I go to Products/ Products
    And I open "Products" import tab
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And I fill product KitItems template divided by "\n" delimiter with data:
      | Product Family.Code | Name.default.value | Description.default.value   | SKU       | Status   | Type | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision | additionalUnitPrecisions:0:unit:code | additionalUnitPrecisions:0:precision | additionalUnitPrecisions:0:sell | Kit Items                                                                                                                                                                                                                                                                                                                                                                 |
      | default_family      | 0000123            | With empty label            | PSKU_KIT1 | enabled  | kit  | in_stock            | set                        | 1                          | item                                 | 1                                    | 1                               | label=" ",optional=false,products=PSKU2,min_qty=1,max_qty=1,unit=item                                                                                                                                                                                                                                                                                                     |
      | default_family      | 0000124            | With empty unit             | PSKU_KIT2 | disabled | kit  | in_stock            |                            | 1                          |                                      |                                      |                                 | label="Barcode Scanner",optional=TRue,products=PSKU2,min_qty=,max_qty=,unit=                                                                                                                                                                                                                                                                                              |
      | default_family      | 0000125            | With label length > 255     | PSKU_KIT3 | disabled | kit  | in_stock            | set                        | 1                          | ite                                  | 1                                    | 1                               | label=“Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec.",optional=1,products=PSKU2,min_qty=,max_qty=,unit=set |
      | default_family      | 0000126            | With invalid optional       | PSKU_KIT4 | disabled | kit  | in_stock            | set                        | 1                          | item                                 | 1                                    | 1                               | label=“Lorem ipsum dolor sit amet”,optional=invalid,products=PSKU2,min_qty=1,max_qty=2,unit=set                                                                                                                                                                                                                                                                           |
      | default_family      | 0000127            | With empty products         | PSKU_KIT5 | enabled  | kit  | in_stock            | set                        | 1                          | item                                 | 1                                    | 1                               | id=,label="Base Unit",optional=yes,products=,min_qty=1,max_qty=1,unit=set                                                                                                                                                                                                                                                                                                 |
      | default_family      | 0000128            | With text value for id      | PSKU_KIT6 | enabled  | kit  | in_stock            | set                        | 1                          | item                                 | 1                                    | 1                               | id="some text",label="Base Unit",optional=yes,products=PSKU2,min_qty=1,max_qty=1,unit=set                                                                                                                                                                                                                                                                                 |
      | default_family      | 0000129            | With float value for id     | PSKU_KIT7 | enabled  | kit  | in_stock            | set                        | 1                          | item                                 | 1                                    | 1                               | id=10.2,label="Base Unit",optional=yes,products=PSKU2,min_qty=1,max_qty=1,unit=set                                                                                                                                                                                                                                                                                        |
      | default_family      | 0000130            | With text value for min_qty | PSKU_KIT8 | enabled  | kit  | in_stock            | set                        | 1                          | item                                 | 1                                    | 1                               | label="Base Unit",optional=yes,products=PSKU2,min_qty=one,max_qty=1,unit=set                                                                                                                                                                                                                                                                                              |
      | default_family      | 0000131            | With comma for max_qty      | PSKU_KIT9 | enabled  | kit  | in_stock            | set                        | 1                          | item                                 | 1                                    | 1                               | label="Base Unit",optional=yes,products=PSKU2,min_qty=1,max_qty="10,2",unit=set                                                                                                                                                                                                                                                                                           |
    When I import file
    Then Email should contains the following "Errors: 12 processed: 0, read: 9, added: 0, updated: 0, replaced: 0" text
    When I follow "Error log" link from the email
    Then I should see "Error in row #1. kitItems[0].labels[default].string: This value should not be blank."
    And I should see "Error in row #2. Unit Precisions Unit Code: Please add one or more product units."
    And I should see "Error in row #2. Unit of Quantity Unit Code: This value should not be blank."
    And I should see "Error in row #2. kitItems[0].productUnit: Unit of quantity cannot be empty."
    And I should see "Error in row #3. Not found entity \"Product Unit\". Item data: {\"code\":\"ite\"}."
    And I should see "Error in row #3. kitItems[0].labels[default].string: This value is too long. It should have 255 characters or less."
    And I should see "Error in row #4. Product Kit Item on line 1 has incorrect value for the field \"optional\": expected boolean (true, false, yes, no, 1, 0), got \"invalid\"."
    And I should see "Error in row #5. kitItems[0].kitItemProducts: Each kit option should have at least one product specified."
    And I should see "Error in row #6. Product Kit Item on line 1 has incorrect value for the field \"id\": expected empty value or integer, got \"\"some text\"\"."
    And I should see "Error in row #7. Product Kit Item on line 1 has incorrect value for the field \"id\": expected empty value or integer, got \"10.2\"."
    And I should see "Error in row #8. Product Kit Item on line 1 has incorrect value for the field \"min_qty\": expected empty value or float (1, 0, 1.0, 0.0), got \"one\"."
    And I should see "Error in row #9. Product Kit Item on line 1 has incorrect value for the field \"max_qty\": expected empty value or float (1, 0, 1.0, 0.0), got \"\"10,2\"\"."

  Scenario: Check only simple products in product kit item products collection
    Given I login as administrator
    And I go to Products/Products
    And I open "Products" import tab
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And I fill product KitItems template divided by "\n" delimiter with data:
      | Product Family.Code | Name.default.value | Description.default.value | SKU       | Status  | Type | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision | Kit Items                                                                        |
      | default_family      | 0000123            | Product Description 1     | PSKU_KIT1 | enabled | kit  | in_stock            | set                        | 1                          | id=,label="Base Unit",optional=false,products=PSKU4,min_qty=1,max_qty=1,unit=set |
    When I import file
    Then Email should contains the following "Errors: 1 processed: 0, read: 1, added: 0, updated: 0, replaced: 0" text
    When I follow "Error log" link from the email
    Then I should see "Error in row #1. kitItems[0].kitItemProducts[0]: Only simple product can be used in kit options."

  Scenario: Check only product kit can have kit items
    Given I login as administrator
    And I go to Products/Products
    And I open "Products" import tab
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And I fill product KitItems template divided by "\n" delimiter with data:
      | Product Family.Code | Name.default.value | Description.default.value | SKU       | Status  | Type   | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision | Kit Items                                                                        |
      | default_family      | 0000123            | Product Description 1     | PSKU_KIT1 | enabled | simple | in_stock            | set                        | 1                          | id=,label="Base Unit",optional=false,products=PSKU1,min_qty=1,max_qty=1,unit=set |
    When I import file
    Then Email should contains the following "Errors: 1 processed: 0, read: 1, added: 0, updated: 0, replaced: 0" text
    When I follow "Error log" link from the email
    Then I should see "Error in row #1. The 'Kit Items' column should only be processed for products classified as 'Kit' type."

  Scenario: Check not allowed fields
    Given I login as administrator
    And I go to Products/Products
    And I open "Products" import tab
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And I fill product KitItems template divided by "\n" delimiter with data:
      | Product Family.Code | Name.default.value | Description.default.value | SKU       | Status  | Type | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision | Kit Items                                                                                                       |
      | default_family      | 0000123            | Product Description 1     | PSKU_KIT1 | enabled | kit  | in_stock            | set                        | 1                          | id=,label="Base Unit",optional=false,products=PSKU1,min_qty=1,max_qty=1,unit=set,quantity=2,additionalUnit=item |
    When I import file
    Then Email should contains the following "Errors: 1 processed: 0, read: 1, added: 0, updated: 0, replaced: 0" text
    When I follow "Error log" link from the email
    Then I should see "Error in row #1. Product Kit Item on line 1 has unknown fields: \"quantity, additionalunit\"."

  Scenario: Check not allowed product unit
    Given I login as administrator
    And I go to Products/Products
    And I open "Products" import tab
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And I fill product KitItems template divided by "\n" delimiter with data:
      | Product Family.Code | Name.default.value | Description.default.value | SKU       | Status  | Type | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision | Kit Items                                                                         |
      | default_family      | 0000123            | Product Description 1     | PSKU_KIT1 | enabled | kit  | in_stock            | set                        | 1                          | id=,label="Base Unit",optional=false,products=PSKU3,min_qty=1,max_qty=1,unit=item |
    When I import file
    Then Email should contains the following "Errors: 1 processed: 0, read: 1, added: 0, updated: 0, replaced: 0" text
    When I follow "Error log" link from the email
    Then I should see "Error in row #1. kitItems[0].productUnit: Unit of quantity should be available for all specified products."

  Scenario: Check that kit item products collection is not lost after a validation error
    Given I login as administrator
    And I go to Products/Products
    And I open "Products" import tab
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And I fill product KitItems template divided by "\n" delimiter with data:
      | Product Family.Code | Name.default.value | Description.default.value | SKU       | Status  | Type | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision | Kit Items                                                                                                                                                            |
      | default_family      | 0000123            | Product Description 1     | PSKU_KIT1 | enabled | kit  | in_stock            | set                        | 1                          | id=,label="Base Unit",optional=false,products=PSKU1,min_qty=1,max_qty=1,unit=set\nlabel="Scanner",optional=false,products=PSKU2,min_qty=1,max_qty=1,unit=item        |
      | default_family      | 0000124            | Product Description 2     | PSKU_KIT2 | enabled | kit  | in_stock            | set                        | 1                          | id=,label="Additional Unit",optional=true,products=PSKU1,min_qty=2,max_qty=2,unit=set\nid=,label="Barcode",optional=true,products=PSKU2,min_qty=2,max_qty=2,unit=set |
    When I import file
    Then Email should contains the following "Errors: 0 processed: 2, read: 2, added: 2, updated: 0, replaced: 0" text
    And I reload the page
    When click view "PSKU_KIT1" in grid
    Then I should see "Base Unit Optional No Minimum Quantity 1 Maximum Quantity 1 Unit Of Quantity set (fractional, 1 decimal digit) Products PSKU1 - Product 1" in the "Product Kit Item 1" element
    And I should see "Scanner Optional No Minimum Quantity 1 Maximum Quantity 1 Unit Of Quantity item (fractional, 1 decimal digit) Products PSKU2 - Product 2" in the "Product Kit Item 2" element
    And I go to Products/Products
    And I open "Products" import tab
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And I fill product KitItems template divided by "\n" delimiter with data:
      | Product Family.Code | Name.default.value | Description.default.value | SKU       | Status  | Type | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision | Kit Items                                                                                                                                                             |
      | default_family      | 0000123            | Product Description 1     | PSKU_KIT1 | enabled | kit  | in_stock            | set                        | 1                          | id=,label="Base Unit",optional=false,products=PSKU1\|PSKU3,min_qty=1,max_qty=1,unit=each\nlabel="Scanner",optional=false,products=PSKU2,min_qty=1,max_qty=1,unit=item |
    When I import file
    Then Email should contains the following "Errors: 1 processed: 0, read: 1, added: 0, updated: 0, replaced: 0" text
    When click view "PSKU_KIT1" in grid
    Then I should see "Base Unit Optional No Minimum Quantity 1 Maximum Quantity 1 Unit Of Quantity set (fractional, 1 decimal digit) Products PSKU1 - Product 1" in the "Product Kit Item 1" element
    And I should not see "Products PSKU1 - Product 1 PSKU3 - Product 3" in the "Product Kit Item 1" element
    And I should see "Scanner Optional No Minimum Quantity 1 Maximum Quantity 1 Unit Of Quantity item (fractional, 1 decimal digit) Products PSKU2 - Product 2" in the "Product Kit Item 2" element

  Scenario: Check that product kit item which does not belong to product will not be changed
    Given I go to Products/Products
    And I open "Products" import tab
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And I fill product KitItems template divided by "\n" delimiter with data:
      | Product Family.Code | Name.default.value | Description.default.value | SKU       | Status  | Type | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision | Kit Items                                                                                                                                                                               |
      | default_family      | 0000124            | Product Description 2     | PSKU_KIT2 | enabled | kit  | in_stock            | set                        | 1                          | id=1,label="Additional Unit Update",optional=false,products=PSKU1,min_qty=3,max_qty=3,unit=set\nid=2,label="Barcode Update",optional=false,products=PSKU2,min_qty=3,max_qty=3,unit=item |
    When I import file
    Then Email should contains the following "Errors: 2 processed: 0, read: 1, added: 0, updated: 0, replaced: 0" text
    When click view "PSKU_KIT1" in grid
    Then I should see "Base Unit Optional No Minimum Quantity 1 Maximum Quantity 1 Unit Of Quantity set (fractional, 1 decimal digit) Products PSKU1 - Product 1" in the "Product Kit Item 1" element
    And I should see "Scanner Optional No Minimum Quantity 1 Maximum Quantity 1 Unit Of Quantity item (fractional, 1 decimal digit) Products PSKU2 - Product 2" in the "Product Kit Item 2" element
    And I go to Products/Products
    When click view "PSKU_KIT2" in grid
    Then I should see "Additional Unit Optional Yes Minimum Quantity 2 Maximum Quantity 2 Unit Of Quantity set (fractional, 1 decimal digit) Products PSKU1 - Product 1" in the "Product Kit Item 1" element
    And I should see "Barcode Optional Yes Minimum Quantity 2 Maximum Quantity 2 Unit Of Quantity set (fractional, 1 decimal digit) Products PSKU2 - Product 2" in the "Product Kit Item 2" element
    When I follow "Error log" link from the email
    Then I should see "Error in row #1. kitItems.2: Kit item \"Base Unit\" cannot be used because it already belongs to the product kit \"PSKU_KIT1\"."
    And I should see "Error in row #1. kitItems.3: Kit item \"Scanner\" cannot be used because it already belongs to the product kit \"PSKU_KIT1\"."
