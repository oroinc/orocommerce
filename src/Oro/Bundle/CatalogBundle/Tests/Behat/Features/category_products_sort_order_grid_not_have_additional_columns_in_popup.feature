@regression
@fixture-OroCatalogBundle:category_products_sort_order_drag_n_drop.yml

Feature: Category products sort order grid not have additional columns in popup

  Scenario: Feature Background
    Given I login as administrator

  Scenario: Add additional field to Product entity
    When I go to System/Entities/Entity Management
    And I filter Name as is equal to "Product"
    And I click View OroProductBundle in grid
    And I click "Create field"
    And I fill form with:
      | Field Name   | test_boolean_product_field |
      | Storage Type | Table column               |
      | Type         | Boolean                    |
    And I click "Continue"
    And I fill form with:
      | Grid Order | 1 |
    And I save and close form
    Then I should see "Field saved" flash message
    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Check if additional column is not shown in the sort order grid in popup
    Given I go to Products/ Master Catalog
    And I click "NewCategory"
    And I should see following grid:
      | IN CATEGORY | TEST_BOOLEAN_PRODUCT_FIELD | SORT ORDER | SKU   | NAME      |
      | 1           |                            |            | PSKU1 | Product 1 |
      | 1           |                            |            | PSKU2 | Product 2 |
      | 1           |                            |            | PSKU4 | Product 4 |
      | 1           |                            |            | PSKU5 | Product 5 |
      | 0           |                            |            | PSKU3 | Product 3 |
    And I fill "Category Form" with:
      | PSKU1 | 1 |
      | PSKU2 | 2 |
      | PSKU4 | 3 |
      | PSKU5 | 4 |
    When I click "Manage sort order"
    Then should see "Save all changes?" in confirmation dialogue
    When I click "Submit and continue" in confirmation dialogue
    Then I should see "Category has been saved" flash message
    And I should see "UiDialog" with elements:
      | Title | Sort products in NewCategory category |
    And I should see following grid:
      | IN CATEGORY | SORT ORDER | SKU   | NAME      |
      | 1           | 1          | PSKU1 | Product 1 |
      | 1           | 2          | PSKU2 | Product 2 |
      | 1           | 3          | PSKU4 | Product 4 |
      | 1           | 4          | PSKU5 | Product 5 |
      | 0           |            | PSKU3 | Product 3 |
