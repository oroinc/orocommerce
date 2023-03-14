@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
@ticket-BB-20692
@ticket-BB-20552

Feature: Product slug prototypes import
  In order to be able to modify product slugs
  As an Administrator
  I need to be able to import slugs prototypes

  Scenario: Feature Background
    Given I enable the existing localizations
    And sessions active:
      | Admin | first_session |

  Scenario: Add default and custom product names
    Given I proceed as the Admin
    And I login as administrator
    When I go to Products / Products
    And I click "Create Product"
    And I click "Continue"
    And click on "Product Names Fallbacks"
    And fill "ProductForm" with:
      | SKU                                       | SKU      |
      | Name                                      | NameEN   |
      | Name English (United States) use fallback | false    |
      | Name English (United States) value        | NameENUS |
    And save and close form
    Then I should see "Product has been saved" flash message
    When I go to Products/Products
    And click edit "SKU" in grid
    And click on "Product Form Slug Fallbacks"
    And URLSlugEnglishValue field should has nameen value
    And URLSlugEnglishUnitedStatesValue field should has nameenus value

  Scenario: Generate slugPrototypes from names when slugPrototypes columns are missing
    Given I go to Products/Products
    And I open "Products" import tab
    And fill import file with data:
      | sku | names.default.value | names.default.fallback | names.English (United States).value | names.English (United States).fallback | attributeFamily.code | status  | inventory_status.id | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision |
      | SKU | NameENimport1       |                        | NameENUSimport1                     |                                        | default_family       | enabled | in_stock            | set                            | 1                              |
    And I open "Products" import tab
    And I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 0, updated: 0, replaced: 1" text
    When I go to Products/Products
    And click edit "SKU" in grid
    And click on "Product Form Slug Fallbacks"
    And URLSlugEnglishValue field should has nameenimport1 value
    And URLSlugEnglishUnitedStatesValue field should has nameenusimport1 value

  Scenario: Import custom slugPrototypes values
    Given I go to Products/Products
    And I open "Products" import tab
    And fill import file with data:
      | sku | names.default.value | names.default.fallback | names.English (United States).value | names.English (United States).fallback | attributeFamily.code | status  | inventory_status.id | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision | slugPrototypes.default.fallback | slugPrototypes.default.value | slugPrototypes.English (United States).fallback | slugPrototypes.English (United States).value |
      | SKU | NameENimport2       |                        | NameENUSimport2                     |                                        | default_family       | enabled | in_stock            | set                            | 1                              |                                 | slugPrototypesEN             |                                                 | slugPrototypesENUS                           |
    And I open "Products" import tab
    And I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 0, updated: 0, replaced: 1" text
    When I go to Products/Products
    And click edit "SKU" in grid
    And click on "Product Form Slug Fallbacks"
    And URLSlugEnglishValue field should has slugPrototypesEN value
    And URLSlugEnglishUnitedStatesValue field should has slugPrototypesENUS value

  Scenario: Import custom slugPrototypes fallbacks
    Given I go to Products/Products
    And I open "Products" import tab
    And fill import file with data:
      | sku | names.default.value | names.default.fallback | names.English (United States).value | names.English (United States).fallback | attributeFamily.code | status  | inventory_status.id | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision | slugPrototypes.default.fallback | slugPrototypes.default.value | slugPrototypes.English (United States).fallback | slugPrototypes.English (United States).value |
      | SKU | NameENimport3       |                        | NameENUSimport3                     |                                        | default_family       | enabled | in_stock            | set                            | 1                              |                                 | slugPrototypesEN1            | system                                          | slugPrototypesENUS                           |
    And I open "Products" import tab
    And I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 0, updated: 0, replaced: 1" text
    When I go to Products/Products
    And click edit "SKU" in grid
    And click on "Product Form Slug Fallbacks"
    And URLSlugEnglishValue field should has slugPrototypesEN1 value
    And URLSlugEnglishUnitedStatesFallback field should has 1 value

  Scenario: Generate slugPrototypes from names when columns are empty
    Given I go to Products/Products
    And I open "Products" import tab
    And fill import file with data:
      | sku | names.default.value | names.default.fallback | names.English (United States).value | names.English (United States).fallback | attributeFamily.code | status  | inventory_status.id | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision | slugPrototypes.default.fallback | slugPrototypes.default.value | slugPrototypes.English (United States).fallback | slugPrototypes.English (United States).value |
      | SKU | NameENimport4       |                        | NameENUSimport4                     |                                        | default_family       | enabled | in_stock            | set                            | 1                              |                                 |                              |                                                 |                                              |
    And I open "Products" import tab
    And I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 0, updated: 0, replaced: 1" text
    When I go to Products/Products
    And click edit "SKU" in grid
    And click on "Product Form Slug Fallbacks"
    And URLSlugEnglishValue field should has nameenimport4 value
    And URLSlugEnglishUnitedStatesValue field should has nameenusimport4 value

  Scenario: Generate and import slugPrototypes
    Given I go to Products/Products
    And I open "Products" import tab
    And fill import file with data:
      | sku | names.default.value | names.default.fallback | names.English (United States).value | names.English (United States).fallback | attributeFamily.code | status  | inventory_status.id | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision | slugPrototypes.default.fallback | slugPrototypes.default.value | slugPrototypes.English (United States).fallback | slugPrototypes.English (United States).value |
      | SKU | NameENimport5       |                        | NameENUSimport5                     |                                        | default_family       | enabled | in_stock            | set                            | 1                              |                                 |                              |                                                 | slugnameenusimport5                          |
    And I open "Products" import tab
    And I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 0, updated: 0, replaced: 1" text
    When I go to Products/Products
    And click edit "SKU" in grid
    And click on "Product Form Slug Fallbacks"
    And URLSlugEnglishValue field should has nameenimport5 value
    And URLSlugEnglishUnitedStatesValue field should has slugnameenusimport5 value

  Scenario: Import with localized name column merely and generate slugPrototypes
    And I go to Products / Products
    And I click "Import file"
    And I upload "import_product_localized_name_merely_column.csv" file to "ShoppingListImportFileField"
    And I click "Import file"
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 0, updated: 0, replaced: 1" text
    When I go to Products/Products
    And click edit "SKU" in grid
    And click on "Product Form Slug Fallbacks"
    And URLSlugEnglishValue field should has nameenimport5 value
    And URLSlugEnglishUnitedStatesValue field should has nameenusimport6 value
