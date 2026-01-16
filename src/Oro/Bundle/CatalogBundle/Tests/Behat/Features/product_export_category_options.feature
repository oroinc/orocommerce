@ticket-BB-18280
@fixture-OroCatalogBundle:product_export_category_options.yml

Feature: Product Export Category Options
  In order to export products with or without category paths and default titles
  As an Administrator
  I want to be able to configure whether category paths and default titles are included in product exports

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session |

  Scenario: Export products with category path by default
    Given I proceed as the Admin
    And I login as administrator
    When I go to Products/ Products
    And I click "Export"
    Then I should see "Export started successfully. You will receive email notification upon completion." flash message
    And Email should contains the following "Export performed successfully. 3 products were exported" text
    Then I download export file
    And exported file contains at least the following columns:
      | SKU   | Category.ID | category.path                        |
      | PSKU1 | 3           | All Products / Electronics / Laptops |
      | PSKU2 | 4           | All Products / Electronics / Phones  |
      | PSKU3 | 2           | All Products / Electronics           |
    And the exported file does not contain the following column and content:
      | category.default.title |
      | Laptops                |
      | Phones                 |
      | Electronics            |

  Scenario: Enable category default title export
    Given I go to System/ Configuration
    And I follow "Commerce/Product/Product Import\/Export" on configuration sidebar
    And uncheck "Use default" for "Export Category Default Title" field
    And I check "Export Category Default Title"
    When I save form
    Then I should see "Configuration saved" flash message

  Scenario: Export products with category paths
    Given I go to Products/ Products
    When I click "Export"
    Then I should see "Export started successfully. You will receive email notification upon completion." flash message
    And Email should contains the following "Export performed successfully. 3 products were exported" text
    Then I download export file
    And exported file contains at least the following columns:
      | SKU   | Category.ID | category.default.title | category.path                        |
      | PSKU1 | 3           | Laptops                | All Products / Electronics / Laptops |
      | PSKU2 | 4           | Phones                 | All Products / Electronics / Phones  |
      | PSKU3 | 2           | Electronics            | All Products / Electronics           |

  Scenario: Disable category path export
    Given I go to System/ Configuration
    And I follow "Commerce/Product/Product Import\/Export" on configuration sidebar
    And uncheck "Use default" for "Export Category Path" field
    And I uncheck "Export Category Path"
    When I save form
    Then I should see "Configuration saved" flash message

  Scenario: Export products with category paths only
    Given I go to Products/ Products
    When I click "Export"
    Then I should see "Export started successfully. You will receive email notification upon completion." flash message
    And Email should contains the following "Export performed successfully. 3 products were exported" text
    Then I download export file
    And the exported file does not contain the following column and content:
      | category.path                        |
      | All Products / Electronics / Laptops |
      | All Products / Electronics / Phones  |
      | All Products / Electronics           |
