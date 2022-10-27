@ticket-BB-10299
@ticket-BB-18266
@fixture-OroProductBundle:ProductsExportFixture.yml
Feature: Export Products
  In order to export products
  As an Administrator
  I want to have an ability Export all products from the system into the file

  Scenario: Verify administrator is able Export Products from the system
    Given I login as administrator
    When I go to Products/ Products
    And I click "Export"
    Then I should see "Export started successfully. You will receive email notification upon completion." flash message
    And Email should contains the following "Export performed successfully. 5 products were exported. Download" text
    And Exported file with Products contains at least the following data:
      | sku   | names.default.value | descriptions.default.value | status   | metaTitles.default.value | metaDescriptions.default.value | metaKeywords.default.value |
      | PSKU1 | Product 1           | Product 1 Description      | enabled  | Meta Title 1             | Meta Description 1             | Meta Keywords 1            |
      | PSKU2 | Product 2           | Product 2 Description      | enabled  |                          |                                |                            |
      | PSKU3 | Product 3           | Product 3 Description      | enabled  |                          |                                |                            |
      | PSKU4 | Product 4           | Product 4 Description      | enabled  |                          |                                |                            |
      | PSKU5 | Product5(disabled)  | Product 5 Description      | disabled |                          |                                |                            |

  Scenario: Verify administrator is able Export Filtered Products
    Given I filter SKU as is equal to "PSKU1"
    And I click on "ExportButtonDropdown"
    And I click "Export Filtered Products"
    Then I should see "Export started successfully. You will receive email notification upon completion." flash message
    And Email should contains the following "Export performed successfully. 1 products were exported. Download" text
    And take the link from email and download the file from this link
    And the downloaded file from email contains at least the following data:
      | sku   | names.default.value | status  | metaTitles.default.value | metaDescriptions.default.value | metaKeywords.default.value |
      | PSKU1 | Product 1           | enabled | Meta Title 1             | Meta Description 1             | Meta Keywords 1            |
