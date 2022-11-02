@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
@fixture-OroLocaleBundle:DutchLocalization.yml
@fixture-OroLocaleBundle:FrenchLocalization.yml
@ticket-BB-20692
Feature: Product localizable names import
  In order to be able to modify product names
  As an Administrator
  I need to be able to import names and fallbacks

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
      | Name Dutch use fallback                   | false    |
      | Name Dutch value                          | NameDE   |
      | Name French use fallback                  | false    |
      | Name French value                         | NameFR   |
    And save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Set fallbacks using import
    Given I go to Products/Products
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | sku | names.default.value | names.default.fallback | names.English (United States).value | names.English (United States).fallback | names.Dutch.value | names.Dutch.fallback | names.French.value | names.French.fallback | attributeFamily.code | status  | inventory_status.id | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision |
      | SKU | NameENimport        |                        | NameENUSimport                      | system                                 | NameDEimport      |                      | NameFRimport       | parent_localization   | default_family       | enabled | in_stock            | set                            | 1                              |
    And I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 0, updated: 0, replaced: 1" text
    When I go to Products/Products
    And click edit "SKU" in grid
    And click on "Product Names Fallbacks"
    Then I should see "Default Value" in the "NameEnglishUnitedStatesFallback" element
    And NameDutchValue field should has NameDEimport value
    And I should see "English (United States) [Parent Localization]" in the "NameFrenchFallback" element

  Scenario: Reset fallbacks using import
    Given I go to Products/Products
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | sku | names.default.value | names.default.fallback | names.English (United States).value | names.English (United States).fallback | names.Dutch.value | names.Dutch.fallback | names.French.value | names.French.fallback | attributeFamily.code | status  | inventory_status.id | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision |
      | SKU | NameENimport2       |                        | NameENUSimport2                     |                                        | NameDEimport2     |                      | NameFRimport2      |                       | default_family       | enabled | in_stock            | set                            | 1                              |
    And I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 0, updated: 0, replaced: 1" text
    When I go to Products/Products
    And click edit "SKU" in grid
    And click on "Product Names Fallbacks"
    Then NameEnglishUnitedStatesValue field should has NameENUSimport2 value
    And NameDutchValue field should has NameDEimport2 value
    And NameFrenchValue field should has NameFRimport2 value

  Scenario: Set fallbacks using import back
    Given I go to Products/Products
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | sku | names.default.value | names.default.fallback | names.English (United States).value | names.English (United States).fallback | names.Dutch.value | names.Dutch.fallback | names.French.value | names.French.fallback | attributeFamily.code | status  | inventory_status.id | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision |
      | SKU | NameENimport        |                        | NameENUSimport                      | system                                 | NameDEimport      |                      | NameFRimport       | parent_localization   | default_family       | enabled | in_stock            | set                            | 1                              |
    And I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 0, updated: 0, replaced: 1" text
    When I go to Products/Products
    And click edit "SKU" in grid
    And click on "Product Names Fallbacks"
    Then I should see "Default Value" in the "NameEnglishUnitedStatesFallback" element
    And NameDutchValue field should has NameDEimport value
    And I should see "English (United States) [Parent Localization]" in the "NameFrenchFallback" element

  Scenario: Reset fallbacks using import back
    Given I go to Products/Products
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | sku | names.default.value | names.default.fallback | names.English (United States).value | names.English (United States).fallback | names.Dutch.value | names.Dutch.fallback | names.French.value | names.French.fallback | attributeFamily.code | status  | inventory_status.id | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision |
      | SKU | NameENimport2       |                        | NameENUSimport2                     |                                        | NameDEimport2     |                      | NameFRimport2      |                       | default_family       | enabled | in_stock            | set                            | 1                              |
    And I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 0, updated: 0, replaced: 1" text
    When I go to Products/Products
    And click edit "SKU" in grid
    And click on "Product Names Fallbacks"
    Then NameEnglishUnitedStatesValue field should has NameENUSimport2 value
    And NameDutchValue field should has NameDEimport2 value
    And NameFrenchValue field should has NameFRimport2 value
