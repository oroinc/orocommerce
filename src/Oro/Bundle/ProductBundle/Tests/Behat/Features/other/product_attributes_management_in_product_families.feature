@ticket-BB-15287
@automatically-ticket-tagged
Feature: Product Attributes Management in Product Families
  In order to create different type of products with different set of attributes
  As administrator
  I want to add and delete attributes to families

  Scenario: Create Size product attributes
    Given I login as administrator

    When I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | Size   |
      | Type       | Select |
    And I click "Continue"
    And set Options with:
      | Label |
      | M     |
      | S     |
    And I save form
    Then I should see "Attribute was successfully saved" flash message

    When I go to Products / Product Attributes
    And I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Create product families with Size attribute
    When I go to Products / Product Families
    And I click "Create Product Family"
    And I fill "Product Family Form" with:
      | Code       | size_family1 |
      | Label      | size_family1 |
      | Attributes | [Size]       |
    And I save and close form
    Then I should see "Product Family was successfully saved" flash message

    When I go to Products / Product Families
    And I click "Create Product Family"
    And I fill "Product Family Form" with:
      | Code       | size_family2 |
      | Label      | size_family2 |
      | Attributes | [Size]       |
    And I save and close form
    Then I should see "Product Family was successfully saved" flash message

  Scenario: Create configurable product with Size attributes in both families
    When go to Products/ Products
    And click "Create Product"
    And fill "ProductForm Step One" with:
      | Type           | Configurable |
      | Product Family | size_family1 |
    And I click "Continue"
    And I fill "ProductForm" with:
      | Sku                     | size_prod1   |
      | Name                    | SizeProduct1 |
      | Configurable Attributes | [Size]       |
      | Size                    | S            |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Attribute can not be removed if it's used in a product from this family
    When I go to Products / Product Families
    And I click Edit size_family1 in grid
    And I clear "Attributes" field
    And I save and close form
    Then I should see "Attributes Size used as configurable attributes in products: size_prod1" error message

  Scenario: Attribute can be removed if it's not used in any product from this family
    When I go to Products / Product Families
    And I click Edit size_family2 in grid
    And I clear "Attributes" field
    And I save and close form
    Then I should see "Successfully updated" flash message
