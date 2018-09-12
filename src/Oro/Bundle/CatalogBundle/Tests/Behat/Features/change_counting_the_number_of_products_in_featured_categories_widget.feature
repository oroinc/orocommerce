@ticket-BB-12417
@fixture-OroProductBundle:highlighting_new_products.yml
Feature: Change counting the number of products in featured categories widget
  ToDo: BAP-16103 Add missing descriptions to the Behat features

  Scenario: Create new subcategory through the widget and add product
    Given I login as administrator
    When go to Products/ Master Catalog
    And click "NewCategory"
    And click "Create Subcategory"
    And fill "Category Form" with:
      |Title|SubNew|
      |Inventory Threshold Use|false|
      |Inventory Threshold    |0    |
    And click on PSKU2 in grid
    And click "Save"
    And I am on the homepage
    Then should see "3 items" for "NewCategory" category
    And should see "1 item" for "SubNew" category
    When click "NewCategory"
    Then should see "PSKU2" product
    And should see "PSKU1" product
