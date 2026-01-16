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
      | Product Family.Code | Name.default.value | SKU   | Status  | Type   | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision | Meta description.default.fallback | Meta description.default.value | Meta description.English (United States).fallback | Meta description.English (United States).value | Meta keywords.default.fallback | Meta keywords.default.value | Meta keywords.English (United States).fallback | Meta keywords.English (United States).value | Meta title.default.fallback | Meta title.default.value | Meta title.English (United States).fallback | Meta title.English (United States).value |
      | default_family      | FailedProduct      | PSKU1 | enabled | simple | in_stock            | set                        | 1                          | invalid                           | sample-description1            | invalid                                           | english-description1                           | invalid                        | sample-keywords1            | invalid                                        | english-keywords1                           | invalid                     | sample-titles1           | invalid                                     | english-titles1                          |
      | default_family      | ImportedProduct    | PSKU2 | enabled | simple | in_stock            | set                        | 1                          | system                            | sample-description2            | system                                            | english-description2                           |                                | sample-keywords2            |                                                | english-keywords2                           |                             | sample-titles2           |                                             | english-titles2                          |
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
      | SKU   | Product Family.Code | Status  | Type   | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision | Unit of Quantity.Conversion Rate | Unit of Quantity.Sell | Name.default.fallback | Name.default.value | Name.English (United States).fallback | Name.English (United States).value | Short Description.default.fallback | Short Description.default.value | Short Description.English (United States).fallback | Short Description.English (United States).value | Description.default.fallback | Description.default.value | Description.English (United States).fallback | Description.English (United States).value | Configurable Attributes | Availability Date | Is Featured | New Arrival | Backorders.value | Category.ID | Decrement Inventory.value | Highlight Low Inventory.value | Inventory Threshold.value | Low Inventory Threshold.value | Managed Inventory.value | Maximum Quantity To Order.value | Meta description.default.fallback | Meta description.default.value | Meta description.English (United States).fallback | Meta description.English (United States).value | Meta keywords.default.fallback | Meta keywords.default.value | Meta keywords.English (United States).fallback | Meta keywords.English (United States).value | Meta title.default.fallback | Meta title.default.value | Meta title.English (United States).fallback | Meta title.English (United States).value | Minimum Quantity To Order.value | Upcoming.value | URL Slug.default.fallback | URL Slug.default.value | URL Slug.English (United States).fallback | URL Slug.English (United States).value | category.path |
      | PSKU2 | default_family      | enabled | simple | in_stock            | set                        | 1                          | 1                                | 1                     |                       | ImportedProduct    |                                       |                                    |                                    |                                 |                                                    |                                                 |                              |                           |                                              |                                           |                         |                   | 0           | 0           | category         |             | category                  | category                      | category                  | category                      | category                | category                        | system                            | sample-description2            | system                                            | english-description2                           |                                | sample-keywords2            |                                                | english-keywords2                           |                             | sample-titles2           |                                             | english-titles2                          | category                        | category       |                           | importedproduct        |                                           |                                        |               |

