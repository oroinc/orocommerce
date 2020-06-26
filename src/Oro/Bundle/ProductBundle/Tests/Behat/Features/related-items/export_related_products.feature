@regression
@ticket-BB-18183
@fixture-OroProductBundle:related_products.yml
Feature: Export Related Products
  In order to export related products
  As an Administrator
  I want to have an ability Export all related products from the system into the file

  Scenario: Verify administrator is able to Export Products from the system
    Given I login as administrator
    And I go to Products/ Products
    And I click on "ExportButtonDropdown"
    And I should see "Export Related Products"
    When I click "Export Related Products"
    Then I should see "Export started successfully. You will receive email notification upon completion." flash message
    And Email should contains the following "Export performed successfully. 1 related products were exported. Download" text
    And take the link from email and download the file from this link
    And the downloaded file from email contains at least the following data:
      | SKU   | Related SKUs {{ "type": "array" }} |
      | PSKU2 | PSKU5,PSKU4,PSKU3,PSKU1            |

  Scenario: Export bidirectional relations
    Given I go to System/Configuration
    And follow "Commerce/Catalog/Related Items" on configuration sidebar
    And fill "RelatedProductsConfig" with:
      | Assign in Both Directions Use Default | false |
      | Assign in Both Directions             | true  |
    And click "Save settings"
    And I should see "Configuration saved" flash message
    And I go to Products/ Products
    And I click on "ExportButtonDropdown"
    When I click "Export Related Products"
    Then I should see "Export started successfully. You will receive email notification upon completion." flash message
    And Email should contains the following "Export performed successfully. 5 related products were exported. Download" text
    And take the link from email and download the file from this link
    And the downloaded file from email contains at least the following data:
      | SKU   | Related SKUs {{ "type": "array" }} |
      | PSKU1 | PSKU2                              |
      | PSKU2 | PSKU5,PSKU4,PSKU3,PSKU1            |
      | PSKU3 | PSKU2                              |
      | PSKU4 | PSKU2                              |
      | PSKU5 | PSKU2                              |

  Scenario: Export when feature is disabled
    Given I go to System/Configuration
    And follow "Commerce/Catalog/Related Items" on configuration sidebar
    And fill "RelatedProductsConfig" with:
      | Enable Related Products Use Default | false |
      | Enable Related Products             | false |
    And click "Save settings"
    And I should see "Configuration saved" flash message
    And I go to Products/ Products
    When I click on "ExportButtonDropdown"
    Then I should not see "Export Related Products"
