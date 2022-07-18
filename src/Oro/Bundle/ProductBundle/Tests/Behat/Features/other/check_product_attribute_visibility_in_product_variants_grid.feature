@ticket-BB-17940
@fixture-OroProductBundle:ConfigurableProductFixtures.yml

Feature: Check product attribute visibility in product variants grid
  Make sure that all product attributes are displayed correctly on product variants datagrid

  Scenario: Create product attributes
    Given I login as administrator
    And I go to Products/ Product Attributes
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
    And I click "Create Attribute"
    And fill form with:
      | Field Name | Size   |
      | Type       | Select |
    And click "Continue"
    And set Options with:
      | Label |
      | L     |
      | M     |
    When I save and close form
    And click update schema
    Then I should see Schema updated flash message

  Scenario: Update product family
    Given I go to Products/ Product Families
    And I click "Edit" on row "default_family" in grid
    When I fill "Product Family Form" with:
      | Attributes | [Color, Size] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Prepare first simple product
    When I go to Products/Products
    And I click Edit 1GB81 in grid
    And I fill in product attribute "Color" with "Black"
    And I fill in product attribute "Size" with "L"
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Prepare second simple product
    When I go to Products/Products
    And I click Edit 1GB82 in grid
    And I fill in product attribute "Color" with "White"
    And I fill in product attribute "Size" with "M"
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check product variant grid
    When I go to Products/Products
    And I click Edit 1GB83 in grid
    When I fill "ProductForm" with:
      | Configurable Attributes | [Color, Size] |
    Then I should see following grid:
      | SKU   | Color | Size |
      | 1GB81 | Black | L    |
      | 1GB82 | White | M    |
