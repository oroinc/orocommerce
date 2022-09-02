@regression
@ticket-BB-21385
@fixture-OroProductBundle:related_items_customer_users.yml
Feature: Unable to save enum attribute containing '0' option value

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create product attributes
    Given I proceed as the Admin
    And I login as administrator
    And I go to Products/ Product Attributes
    And click "Create Attribute"
    And fill form with:
      | Field Name | Width  |
      | Type       | Select |
    And click "Continue"
    And set Options with:
      | Label |
      | 0     |
      | 1     |
    And I save and close form
    When click update schema
    Then I should see Schema updated flash message

  Scenario: Update product family
    Given I go to Products/ Product Families
    And I click "Edit" on row "default_family" in grid
    When I fill "Product Family Form" with:
      | Attributes | [Width] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Import file with only configurable products
    Given I go to Products/Products
    When I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | sku   | names.default.value  | Width.name | variantLinks.1.product.sku | variantLinks.1.visible | variantLinks.2.product.sku | variantLinks.2.visible | attributeFamily.code | status  | inventory_status.id | type         | variantFields | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision | primaryUnitPrecision.conversionRate | primaryUnitPrecision.sell |
      | 1GB81 | Simple Product 1     | 0          |                            |                        |                            |                        | default_family       | enabled | in_stock            | simple       |               | kg                             | 3                              | 1                                   | 1                         |
      | 1GB82 | Simple Product 2     | 1          |                            |                        |                            |                        | default_family       | enabled | in_stock            | simple       |               | kg                             | 3                              | 1                                   | 1                         |
      | 1GB83 | Configurable product |            | 1GB81                      | 1                      | 1GB82                      | 1                      | default_family       | enabled | in_stock            | configurable | Width         | kg                             | 3                              | 1                                   | 1                         |
    And I import file
    Then Email should contains the following "Errors: 0 processed: 3, read: 3, added: 3, updated: 0, replaced: 0" text

  Scenario: Edit Width product attribute
    Given I go to Products / Product Attributes
    And click edit "Width" in grid
    When I save and close form
    Then I should see "Attribute was successfully saved" flash message

  Scenario: Update system configuration
    Given I go to System/Configuration
    And I follow "Commerce/Product/Configurable Products" on configuration sidebar
    When uncheck "Use default" for "Product Views" field
    And I fill in "Product Views" with "No Matrix Form"
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: Check that configurable product form has Width variants
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And type "1GB83" in "search"
    And click "Search Button"
    And click "View Details" for "1GB83" product
    Then I should see an "Configurable Product Form" element
    And "Configurable Product Form" must contains values:
      | Width | 0 |
