@regression
@fixture-OroProductBundle:product_collection_sort_order.yml

Feature: Product collection sort order grid not have additional columns in popup

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
      | Grid Order | 0 |
    And I save and close form
    Then I should see "Field saved" flash message
    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Check if additional column is not shown in the sort order grid in popup
    When I set "Default Web Catalog" as default web catalog
    And I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click on "Remove Variant Button"
    And I click "Content Variants"
    And I click on "Show Variants Dropdown"
    And I click "Add Product Collection"
    And I click on "Advanced Filter"
    And I should see "Drag And Drop From The Left To Start Working"
    And I drag and drop "Field Condition" on "Drop condition here"
    And I click "Choose a field.."
    And I click on "SKU"
    And I type "PSKU" in "value"
    And I click on "Preview Results"
    Then I should see following grid:
      | SKU   | TEST_BOOLEAN_PRODUCT_FIELD | NAME      |
      | PSKU1 |                            | Product 1 |
      | PSKU2 |                            | Product 2 |
      | PSKU3 |                            | Product 3 |
      | PSKU4 |                            | Product 4 |
      | PSKU5 |                            | Product 5 |
    And I type "Some Custom Segment Name" in "Segment Name"
    When I click "Manage sort order"
    Then should see "Save all changes?" in confirmation dialogue
    When I click "Submit and continue" in confirmation dialogue
    Then I should see "Content Node has been saved" flash message
    And I should see following grid:
      | SORT ORDER | SKU   | NAME      |
      |            | PSKU1 | Product 1 |
      |            | PSKU2 | Product 2 |
      |            | PSKU3 | Product 3 |
      |            | PSKU4 | Product 4 |
      |            | PSKU5 | Product 5 |
