@ticket-BB-17348
@fixture-OroVisibilityBundle:import_products_visibility.yml

Feature: Import Products Visibility
  In order to manage product visibility
  As an Administrator
  I want to be able to import products and have correct visibility for them

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Import product
    Given I login as administrator
    And I go to Products/ Products
    And I open "Products" import tab
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | names.default.value | sku   | category.id | category.default.title | descriptions.default.value | attributeFamily.code | status  | type   | inventory_status.id | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision |
      | Test Product 1      | PSKU1 |             | Lighting Products      |                            | default_family       | enabled | simple | in_stock            | set                            | 1                              |
    When I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 1, updated: 0, replaced: 0" text
    And I reload the page
    And I should see following grid:
      | SKU   | Name           |
      | PSKU1 | Test Product 1 |

  Scenario: Check product is not visible
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    And type "PSKU1" in "search"
    When click "Search Button"
    Then I should not see "Test Product 1"

  Scenario: Import product again with another category
    Given I proceed as the Admin
    And fill template with data:
      | names.default.value | sku   | category.id | category.default.title | descriptions.default.value | attributeFamily.code | status  | type   | inventory_status.id | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision |
      | Test Product 1      | PSKU1 |             | Printers               |                            | default_family       | enabled | simple | in_stock            | set                            | 1                              |
    When I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 0, updated: 0, replaced: 1" text

  Scenario: Check product is visible
    Given I proceed as the Buyer
    When I reload the page
    Then I should see "Test Product 1"
