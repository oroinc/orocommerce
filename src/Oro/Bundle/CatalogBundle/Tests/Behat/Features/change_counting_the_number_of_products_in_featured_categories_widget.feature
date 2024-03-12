@ticket-BB-12417
@ticket-BB-18109
@fixture-OroCustomerBundle:CustomerUserFixture.yml
@fixture-OroProductBundle:highlighting_new_products.yml
Feature: Change counting the number of products in featured categories widget

  Scenario: Create new subcategory through the widget and add product
    Given I login as administrator
    When go to Products/ Master Catalog
    And click "NewCategory"
    And click "Create Subcategory"
    And fill "Category Form" with:
      | Title                   | SubNew            |
      | Inventory Threshold Use | false             |
      | Inventory Threshold     | 0                 |
      | Short Description       | Short description |
      | Long Description        | Long description  |
    And I click "Products"
    And click on PSKU2 in grid
    And click "Save"
    And I am on the homepage
    Then should see "3 items" for "NewCategory" category
    And should see "1 item" for "SubNew" category
    And should see "Short description" for "SubNew" category
    When I click "NewCategory" in hamburger menu
    And I click "All NewCategory" in hamburger menu
    Then should see "PSKU2" product
    And should see "PSKU1" product
    Then I go to homepage
    And I click on "Main Menu Button"
    And I should see "NewCategory/ SubNew" in main menu
    When I click "NewCategory" in hamburger menu
    When I click "SubNew" in hamburger menu
    Then should see "Long description" in the "Category Long Description" element
