@feature-BB-21122
@ticket-BB-22959
@fixture-OroProductBundle:ProductKitsExportFixture.yml

Feature: Export Product Kits
  In order to export product kits
  As an Administrator
  I want to have an ability Export all products from the system into the file

  Scenario: Verify administrator is able Export Product Kits from the system
    Given I login as administrator
    When I go to Products/ Products
    And I click "Export"
    Then I should see "Export started successfully. You will receive email notification upon completion." flash message
    And Email should contains the following "Export performed successfully. 6 products were exported. Download" text
    And take the link from email and download the file from this link
    And Exported file with Product Kits divided by "\n" delimiter contains at least the following data:
      | SKU       | Name.default.value | Description.default.value | Status   | Kit Items                                                                                                                                                                                                                                                                                  |
      | PSKU1     | Product 1          | Product 1 Description     | enabled  |                                                                                                                                                                                                                                                                                            |
      | PSKU2     | Product 2          | Product 2 Description     | enabled  |                                                                                                                                                                                                                                                                                            |
      | PSKU3     | Product 3          | Product 3 Description     | enabled  |                                                                                                                                                                                                                                                                                            |
      | PSKU4     | Product 4          | Product 4 Description     | enabled  |                                                                                                                                                                                                                                                                                            |
      | PSKU_KIT1 | Product Kit 1      | Product Kit 1 Description | enabled  | id=1,label="Base Unit",optional=false,products=PSKU1\|PSKU2\|PSKU3,min_qty=1,max_qty=1,unit=set\nid=2,label="Barcode Scanner",optional=false,products=PSKU4,min_qty=1,max_qty=1,unit=item\nid=3,label="Receipt Printer(s)",optional=true,products=PSKU1\|PSKU3,min_qty=1,max_qty=,unit=set |
      | PSKU_KIT2 | Product Kit 2      | Product Kit 2 Description | enabled  | id=4,label="Additional Card Reader(s)",optional=true,products=PSKU4,min_qty=1,max_qty=,unit=item                                                                                                                                                                                           |

  Scenario: Verify administrator is able Export Filtered Product Kits
    Given I go to Products/ Products
    And I check "Kit" in "Type: All" filter strictly
    When I click on "ExportButtonDropdown"
    And I click "Export Filtered Products"
    Then I should see "Export started successfully. You will receive email notification upon completion." flash message
    And Email should contains the following "Export performed successfully. 2 products were exported. Download" text
    And take the link from email and download the file from this link
    And Exported file with Product Kits divided by "\n" delimiter contains at least the following data:
      | SKU       | Name.default.value | Description.default.value | Status   | Kit Items                                                                                                                                                                                                                                                                                  |
      | PSKU_KIT1 | Product Kit 1      | Product Kit 1 Description | enabled  | id=1,label="Base Unit",optional=false,products=PSKU1\|PSKU2\|PSKU3,min_qty=1,max_qty=1,unit=set\nid=2,label="Barcode Scanner",optional=false,products=PSKU4,min_qty=1,max_qty=1,unit=item\nid=3,label="Receipt Printer(s)",optional=true,products=PSKU1\|PSKU3,min_qty=1,max_qty=,unit=set |
      | PSKU_KIT2 | Product Kit 2      | Product Kit 2 Description | enabled  | id=4,label="Additional Card Reader(s)",optional=true,products=PSKU4,min_qty=1,max_qty=,unit=item                                                                                                                                                                                           |

  Scenario: Verify administrator is able Export Product Kits with sensitive symbols
    Given I click Edit "PSKU_KIT1" in grid
    And I fill "ProductKitForm" with:
      | Kit Item 1 Label | ,My, =Escaped= "Kit" 'Item' |
    And I save and close form
    And I go to Products/ Products
    And I filter SKU as is equal to "PSKU_KIT1"
    And I click on "ExportButtonDropdown"
    When I click "Export Filtered Products"
    Then I should see "Export started successfully. You will receive email notification upon completion." flash message
    And Email should contains the following "Export performed successfully. 1 products were exported. Download" text
