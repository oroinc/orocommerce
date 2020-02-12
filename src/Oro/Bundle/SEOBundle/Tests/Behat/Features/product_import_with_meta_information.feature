@ticket-BB-17396

Feature: Product import with meta information
  In order to have ability to import products with meta information
  As an administrator
  I want to be able to properly import product with meta title, meta description, meta keywords

  Scenario: Import products
    Given I login as administrator
    And I go to Products/Products
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | attributeFamily.code | names.default.value | sku   | status  | type   | inventory_status.id | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision | metaDescriptions.default.fallback | metaDescriptions.default.value | metaDescriptions.English (United States).fallback | metaDescriptions.English (United States).value | metaKeywords.default.fallback | metaKeywords.default.value | metaKeywords.English (United States).fallback | metaKeywords.English (United States).value | metaTitles.default.fallback | metaTitles.default.value | metaTitles.English (United States).fallback | metaTitles.English (United States).value |
      | default_family       | FailedProduct       | PSKU1 | enabled | simple | in_stock            | set                            | 1                              | invalid                           | sample-description1            | invalid                                           | english-description1                           | invalid                       | sample-keywords1           | invalid                                       | english-keywords1                          | invalid                     | sample-titles1           | invalid                                     | english-titles1                          |
      | default_family       | ImportedProduct     | PSKU2 | enabled | simple | in_stock            | set                            | 1                              | system                            | sample-description2            | system                                            | english-description2                           |                               | sample-keywords2           |                                               | english-keywords2                          |                             | sample-titles2           |                                             | english-titles2                          |
    When I import file
    Then Email should contains the following "Errors: 6 processed: 1, read: 2, added: 1, updated: 0, replaced: 0" text
    When I follow link from the email
    Then I should see "Error in row #1. metaTitles[default].fallback: The value you selected is not a valid choice."
    And I should see "Error in row #1. metaTitles[English (United States)].fallback: The value you selected is not a valid choice."
    And I should see "Error in row #1. metaDescriptions[default].fallback: The value you selected is not a valid choice."
    And I should see "Error in row #1. metaDescriptions[English (United States)].fallback: The value you selected is not a valid choice."
    And I should see "Error in row #1. metaKeywords[default].fallback: The value you selected is not a valid choice."
    And I should see "Error in row #1. metaKeywords[English (United States)].fallback: The value you selected is not a valid choice."

  Scenario: Check imported products
    Given I login as administrator
    When I go to Products/Products
    And I sort grid by "SKU"
    Then I should see following grid:
      | SKU   | Name            |
      | PSKU2 | ImportedProduct |
    And I should not see "FailedProduct"
    When click view "PSKU2" in grid
    Then I should see "sample-titles2"
    And I should see "sample-description2"
    And I should see "sample-keywords2"
    And Exported file for "Products" with processor "oro_product_product" contains at least the following columns:
      | sku   | attributeFamily.code | status  | type   | inventory_status.id | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision | primaryUnitPrecision.conversionRate | primaryUnitPrecision.sell | names.default.fallback | names.default.value | names.English (United States).fallback | names.English (United States).value | shortDescriptions.default.fallback | shortDescriptions.default.value | shortDescriptions.English (United States).fallback | shortDescriptions.English (United States).value | descriptions.default.fallback | descriptions.default.value | descriptions.English (United States).fallback | descriptions.English (United States).value | variantFields | availability_date | featured | newArrival | backOrder.value | category.id | decrementQuantity.value | highlightLowInventory.value | inventoryThreshold.value | lowInventoryThreshold.value | manageInventory.value | maximumQuantityToOrder.value | metaDescriptions.default.fallback | metaDescriptions.default.value | metaDescriptions.English (United States).fallback | metaDescriptions.English (United States).value | metaKeywords.default.fallback | metaKeywords.default.value | metaKeywords.English (United States).fallback | metaKeywords.English (United States).value | metaTitles.default.fallback | metaTitles.default.value | metaTitles.English (United States).fallback | metaTitles.English (United States).value | minimumQuantityToOrder.value | isUpcoming.value | slugPrototypes.default.fallback | slugPrototypes.default.value | slugPrototypes.English (United States).fallback | slugPrototypes.English (United States).value | category.default.title |
      | PSKU2 | default_family       | enabled | simple | in_stock            | set                            | 1                              | 1                                   | 1                         |                        | ImportedProduct     |                                        |                                     |                                    |                                 |                                                    |                                                 |                               |                            |                                               |                                            |               |                   | 0        | 0          |                 |             |                         |                             |                          |                             |                       |                              | system                            | sample-description2            | system                                            | english-description2                           |                               | sample-keywords2           |                                               | english-keywords2                          |                             | sample-titles2           |                                             | english-titles2                          |                              |                  |                                 | importedproduct              |                                                 |                                              |                        |

