@regression
@ticket-BB-26346
Feature: Import products with invalid enum attribute options
  In order to prevent data corruption during import
  As an Administrator
  I want to see validation errors for non-existent enum options

  Scenario: Create product attributes
    Given I login as administrator
    And I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | color  |
      | Type       | Select |
    And I click "Continue"
    And I set Options with:
      | Label |
      | Red   |
      | Green |
      | Blue  |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

    When I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | size         |
      | Type       | Multi-Select |
    And I click "Continue"
    And I set Options with:
      | Label |
      | S     |
      | M     |
      | L     |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

  Scenario: Update product family with new attribute
    Given I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [color, size] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Import products with invalid enum and multiEnum values
    Given I go to Products / Products
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | SKU   | Name.default.value | color.Name | size.1.Name | size.2.Name | Product Family.Code | Status  | Inventory Status.Id | Type   | Unit of Quantity.Unit.Code | Unit of Quantity.Precision | Unit of Quantity.Conversion Rate | Unit of Quantity.Sell |
      | TEST1 | Product Invalid    | yellow     | xl          | xxl         | default_family      | enabled | in_stock            | simple | item                       | 0                          | 1                                | 1                     |
      | TEST2 | Product Valid      | Blue       | S           | L           | default_family      | enabled | in_stock            | simple | item                       | 0                          | 1                                | 1                     |
    And I import file
    Then Email should contains the following "Errors: 3 processed: 1, read: 2, added: 1, updated: 0, replaced: 0" text
    When I follow "Error log" link from the email
    Then I should see "Error in row #1. color: The value \"yellow\" is not a valid enum option."
    And I should see "Error in row #1. size: The value \"xl\" is not a valid enum option."
    And I should see "Error in row #1. size: The value \"xxl\" is not a valid enum option."

  Scenario: Check product attributes
    Given I login as administrator
    When I go to Products / Products
    Then number of records should be 1
    And I should see following grid:
      | SKU   | Name          |
      | TEST2 | Product Valid |
    When I click view "TEST2" in grid
    Then I should see "Color Blue"
    And I should see "Size S, L"
