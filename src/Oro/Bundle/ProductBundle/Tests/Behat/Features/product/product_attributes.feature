Feature: Product attributes
  ToDo: BAP-16103 Add missing descriptions to the Behat features

  Scenario: Create product attributes
    Given I login as administrator
    And I go to Products/ Product Attributes
    And press "Create Attribute"
    And fill form with:
      | Field Name | Color  |
      | Type       | Select |
    And press "Continue"
    And set Options with:
      | Label  |
      | Black  |
      | White  |
    And save and close form
    And I press "Create Attribute"
    And fill form with:
      | Field Name | Size   |
      | Type       | Select |
    And press "Continue"
    And set Options with:
      | Label  |
      | L      |
      | M      |
    When I save and close form
    And click update schema
    Then I should see Schema updated flash message

  Scenario: Create extend field from entity management
    Given I go to System/ Entities/ Entity Management
    And I filter Name as is equal to "Product"
    And I click view "Product" in grid
    And I press "Create Field"
    And I fill form with:
      | Field Name   | us_size      |
      | Storage Type | Table column |
      | Type         | Select       |
    And press "Continue"
    When I save and close form
    Then I should see "Field saved" flash message
    And I click update schema
    Then I should see Schema updated flash message

  Scenario: Create product family with new attributes
    And I go to Products/ Product Families
    And I press "Create Product Family"
    And fill "Product Family Form" with:
      | Code       | tshirt_family |
      | Label      | Tshirts       |
      | Enabled    | True          |
      | Attributes | [Color, Size] |
    When I save and close form
    Then I should see "Product Family was successfully saved" flash message

  Scenario: Create configurable product
    Given I go to Products/ Products
    And press "Create Product"
    And I fill form with:
      | Type           | Configurable |
      | Product Family | Tshirts      |
    When I press "Continue"
    Then I should see that "Configurable Attributes" contains "Color"
    And I should see that "Configurable Attributes" contains "Size"
    And I should see that "Configurable Attributes" does not contain "us_size"
