@ticket-BB-16378
@ticket-BAP-18847
Feature: Product import validation
  In order to check file for errors before import
  As an administrator
  I want to be able to get a list of validation errors for imported file

  Scenario: Prepare additional localization
    Given I login as administrator
    And I go to System/Localization/Localizations
    And I click "Create Localization"
    And fill "Localization Form" with:
      | Name       | Ukrainian           |
      | Title      | Ukrainian           |
      | Language   | English             |
      | Formatting | Ukrainian (Ukraine) |
    And I save form
    Then I should see "Localization has been saved" flash message

  Scenario: Validate import file with valid data
    Given I go to Products/Products
    When I download "Products" Data Template file with processor "oro_product_product_export_template"
    Then I see the following columns in the downloaded csv template:
      | Name.English (United States).fallback              |
      | Name.English (United States).value                 |
      | Name.Ukrainian.fallback                            |
      | Name.Ukrainian.value                               |
      | Short Description.default.fallback                 |
      | Short Description.default.value                    |
      | Short Description.English (United States).fallback |
      | Short Description.English (United States).value    |
      | Short Description.Ukrainian.fallback               |
      | Short Description.Ukrainian.value                  |
    And fill template with data:
      | Name.default.value                                                                                                | Product Family.Code | SKU   | Status  | Type   | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision | URL Slug.default.value |
      | <b>Test</b><br><img src="http://test.jpg"><strong>Test</strong><a href="http://test.pdf" target="_blank">Test</a> | default_family      | PSKU1 | enabled | simple | in_stock            | set                        | 1                          | invalid,slug^&         |
      |                                                                                                                   | default_family      | PSKU2 | enabled | simple | in_stock            | set                        | 1                          | valid-slug             |
      | Product 3                                                                                                         | default_family      | PSKU3 | enabled | simple | in_stock            | set                        | 1                          | _item                  |
      | Product 4                                                                                                         | default_family      | PSKU4 | enabled | simple | in_stock            | set                        | 1                          |                        |

  Scenario: Check import error page from the email after validating import file
    Given I validate file
    Then Email should contains the following "Errors: 3 processed: 1, read: 4" text
    When I follow "Error log" link from the email
    Then I should see "Error in row #1. slugPrototypes[default]: This value should contain only latin letters, numbers and symbols \"-._~\""
    Then I should see "Error in row #2. Name Localization Name: Product default name is blank"
    Then I should see "Error in row #3. slugPrototypes[default]: This value should not contain reserved keyword \"_item\""
    And I login as administrator

  Scenario: Import file with few invalid records
    Given I go to Products/Products
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | Product Family.Code | Name.default.value | Name.Ukrainian.value | SKU   | Status  | Type   | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision | URL Slug.default.value |
      | default_family      | Product 1          | Product 1 Ukr        | PSKU1 | enabled | simple | in_stock            | set                        | 1                          | invalid,slug^&         |
      | default_family      |                    | Product 2 Ukr        | PSKU2 | enabled | simple | in_stock            | set                        | 1                          | valid-slug             |
      | default_family      | Product 3          | Product 2 Ukr        | PSKU3 | enabled | simple | in_stock            | set                        | 1                          | valid-slug             |
      | default_family      | Product 4          | Product 3 Ukr        | PSKU4 | enabled | simple | in_stock            | set                        | 1                          |                        |

  Scenario: Check import error page from the email after importing file
    Given I import file
    Then Email should contains the following "Errors: 2 processed: 2, read: 4" text
    When I follow "Error log" link from the email
    Then I should see "Error in row #1. slugPrototypes[default]: This value should contain only latin letters, numbers and symbols \"-._~\""
    Then I should see "Error in row #2. Name Localization Name: Product default name is blank"
    And I login as administrator

  Scenario: Check imported products
    Given I go to Products/Products
    Then I should see following grid:
      | SKU   | Name      |
      | PSKU4 | Product 4 |
      | PSKU3 | Product 3 |
    When click edit "PSKU3" in grid
    Then I should see URL Slug field filled with "valid-slug"
    And I go to Products/Products
    When click edit "PSKU4" in grid
    Then I should see URL Slug field filled with "product-4"
