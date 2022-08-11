@ticket-BB-17940
@fixture-OroProductBundle:ConfigurableProductFixtures.yml

Feature: Check product attribute visibility in product variants grid
  Make sure that all product attributes are displayed correctly on product variants datagrid

  Scenario: Create product attributes
    Given I login as administrator
    And go to Products/ Product Attributes
    And click "Create Attribute"
    And fill form with:
      | Field Name | Color  |
      | Type       | Select |
    And click "Continue"
    And set Options with:
      | Label |
      | Black |
      | White |
    And save and close form
    And click "Create Attribute"
    And fill form with:
      | Field Name | Size   |
      | Type       | Select |
    And click "Continue"
    And set Options with:
      | Label |
      | L     |
      | M     |
    And save and close form
    And click "Create Attribute"
    And fill form with:
      | Field Name | Transparent |
      | Type       | Boolean     |
    And click "Continue"
    When I save and close form
    And click update schema
    Then I should see Schema updated flash message

  Scenario: Update product family
    Given I go to Products/ Product Families
    And click "Edit" on row "default_family" in grid
    When I fill "Product Family Form" with:
      | Attributes | [Color, Size, Transparent] |
    And save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Prepare first simple product
    Given I go to Products/Products
    When I click Edit 1GB81 in grid
    And fill in product attribute "Color" with "Black"
    And fill in product attribute "Size" with "L"
    And fill in product attribute "Transparent" with "No"
    And save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Prepare second simple product
    Given I go to Products/Products
    When I click Edit 1GB82 in grid
    And fill in product attribute "Color" with "White"
    And fill in product attribute "Size" with "M"
    And fill in product attribute "Transparent" with "Yes"
    And save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check product variant grid
    Given I go to Products/Products
    And click Edit 1GB83 in grid
    When I fill "ProductForm" with:
      | Configurable Attributes | [Color, Size, Transparent] |
    And save form
    Then I should see "Product has been saved" flash message
    When I press "Product Variants"
    Then I should see following grid:
      | SKU   | Inventory status | Color | Size | Transparent |
      | 1GB81 | In Stock         | Black | L    | No          |
      | 1GB82 | In Stock         | White | M    | Yes         |
