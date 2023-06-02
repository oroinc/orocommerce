@regression
@ticket-BB-22241
@fixture-OroLocaleBundle:LocalizationFixture.yml

Feature: Import products with Non Default Localization
  In order to import products with non default localization
  As an Administrator
  I need to be able to have simple products that attributes have translated options in specified localization

  Scenario: Create product attributes
    Given I login as administrator
    And I go to Products/ Product Attributes
    And click "Create Attribute"
    And fill form with:
      | Field Name | Color  |
      | Type       | Select |
    And click "Continue"
    And set Options with:
      | Label |
      | Black |
      | White |
    And I save and close form
    When click update schema
    Then I should see Schema updated flash message

  Scenario: Update product family
    Given I go to Products/ Product Families
    And I click "Edit" on row "default_family" in grid
    When I fill "Product Family Form" with:
      | Attributes | [Color] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Import file with only configurable products
    Given I go to Products/Products
    When I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | SKU   | Name.default.value   | Color.Name | Product Variant Links.1.Product.SKU | Product Variant Links.1.Visible | Product Variant Links.2.Product.SKU | Product Variant Links.2.Visible | Product Family.Code | Status  | Inventory Status.Id | Type         | Configurable Attributes | Unit of Quantity.Unit.Code | Unit of Quantity.Precision | Unit of Quantity.Conversion Rate | Unit of Quantity.Sell |
      | 1GB81 | Simple Product 1     | Black      |                                     |                                 |                                     |                                 | default_family      | enabled | in_stock            | simple       |                         | kg                         | 3                          | 1                                | 1                     |
      | 1GB82 | Simple Product 2     | White      |                                     |                                 |                                     |                                 | default_family      | enabled | in_stock            | simple       |                         | kg                         | 3                          | 1                                | 1                     |
      | 1GB83 | Configurable product |            | 1GB81                               | 1                               | 1GB82                               | 1                               | default_family      | enabled | in_stock            | configurable | Color                   | kg                         | 3                          | 1                                | 1                     |
    And I import file
    Then Email should contains the following "Errors: 0 processed: 3, read: 3, added: 3, updated: 0, replaced: 0" text
    And I go to Products/Products
    Then number of records should be 3
    When I show column Color in grid
    And I sort grid by SKU
    Then I should see following grid:
      | SKU   | Name                 | Color |
      | 1GB81 | Simple Product 1     | Black |
      | 1GB82 | Simple Product 2     | White |
      | 1GB83 | Configurable product |       |

  Scenario: Switch default language to German
    When I go to System / Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill form with:
      | Enabled Localizations | [English (United States), German Localization] |
      | Default Localization  | German Localization                            |
    And I submit form
    Then I should see "Configuration saved" flash message
    And I go to Products/ Product Attributes
    And I click edit "Color" in grid
    And I fill "Entity Config Form" with:
      | Option First  | Schwarz |
      | Option Second | Weiss   |
    And I save and close form

  Scenario: Again to import file with localized option values
    Given I go to Products/Products
    When I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | SKU   | Name.default.value | Color.Name | Product Family.Code | Status  | Inventory Status.Id | Type   | Configurable Attributes | Unit of Quantity.Unit.Code | Unit of Quantity.Precision | Unit of Quantity.Conversion Rate | Unit of Quantity.Sell |
      | 1GB81 | Simple Product 1   | Schwarz    | default_family      | enabled | in_stock            | simple |                         | kg                         | 3                          | 1                                | 1                     |
      | 1GB82 | Simple Product 2   | Weiss      | default_family      | enabled | in_stock            | simple |                         | kg                         | 3                          | 1                                | 1                     |
    And I import file
    Then Email should contains the following "Errors: 0 processed: 2, read: 2, added: 0, updated: 0, replaced: 2" text
    When I reload the page
    Then number of records should be 3
    When I show column Color in grid
    And I sort grid by SKU
    Then I should see following grid:
      | SKU   | Name                 | Color   |
      | 1GB81 | Simple Product 1     | Schwarz |
      | 1GB82 | Simple Product 2     | Weiss   |
      | 1GB83 | Configurable product |         |
