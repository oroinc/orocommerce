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

  Scenario: Update product family
    When I go to Products/ Product Families
    And I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [Color] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Import file with new added attribute
    Given I go to Products/Products
    When I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | SKU   | Name.default.value | Color.Name | Product Family.Code | Status  | Inventory Status.Id | Type   | Configurable Attributes | Unit of Quantity.Unit.Code | Unit of Quantity.Precision | Unit of Quantity.Conversion Rate | Unit of Quantity.Sell |
      | 1GB81 | Simple Product 1   | Black      | default_family      | enabled | in_stock            | simple |                         | kg                         | 3                          | 1                                | 1                     |
      | 1GB82 | Simple Product 2   | White      | default_family      | enabled | in_stock            | simple |                         | kg                         | 3                          | 1                                | 1                     |
    And I import file
    Then Email should contains the following "Errors: 0 processed: 2, read: 2, added: 2, updated: 0, replaced: 0" text
    And I go to Products/Products
    Then number of records should be 2
    When I show column Color in grid
    And I sort grid by SKU
    Then I should see following grid:
      | SKU   | Name             | Color |
      | 1GB81 | Simple Product 1 | Black |
      | 1GB82 | Simple Product 2 | White |

  Scenario: Switch default language to German
    When I go to System / Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill form with:
      | Enabled Localizations | [English (United States), German Localization] |
      | Default Localization  | German Localization                            |
    And I submit form
    Then I should see "Configuration saved" flash message
    When I go to Products/ Product Attributes
    And I click edit "Color" in grid
    And fill form with:
      | Label       | Farbe        |
      | Description | Produktfarbe |
    And click "Add"
    And I fill "Entity Config Form" with:
      | Option First  | Schwarz |
      | Option Second | Weiss   |
      | Option Third  | Blau    |
    And I save and close form

  Scenario: Again to import file with localized option values
    When I go to Products/Products
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | SKU   | Name.default.value | Farbe.Name | Product Family.Code | Status  | Inventory Status.Id | Type   | Configurable Attributes | Unit of Quantity.Unit.Code | Unit of Quantity.Precision | Unit of Quantity.Conversion Rate | Unit of Quantity.Sell |
      | 1GB81 | Simple Product 1   | Schwarz    | default_family      | enabled | in_stock            | simple |                         | kg                         | 3                          | 1                                | 1                     |
      | 1GB82 | Simple Product 2   | Weiss      | default_family      | enabled | in_stock            | simple |                         | kg                         | 3                          | 1                                | 1                     |
      | 1GB83 | Simple Product 3   | Blau       | default_family      | enabled | in_stock            | simple |                         | kg                         | 3                          | 1                                | 1                     |
    And I import file
    Then Email should contains the following "Errors: 0 processed: 3, read: 3, added: 1, updated: 0, replaced: 2" text
    When I reload the page
    Then number of records should be 3
    When I show column Farbe in grid
    And I sort grid by SKU
    Then I should see following grid:
      | SKU   | Name             | Farbe   |
      | 1GB81 | Simple Product 1 | Schwarz |
      | 1GB82 | Simple Product 2 | Weiss   |
      | 1GB83 | Simple Product 3 | Blau    |
