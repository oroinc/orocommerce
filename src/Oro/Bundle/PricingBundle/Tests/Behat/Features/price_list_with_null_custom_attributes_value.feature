@regression
@ticket-BB-24880
@fixture-OroProductBundle:products.yml

Feature: Price List with null custom attributes value
  In order to have correct price list product dependent on product attributes
  As an Administrator
  I want to create price lists with condition equal or not equal to null and receive correct products

  Scenario: Create product attributes
    And I login as administrator

  Scenario: Add enum product attribute
    And I go to Products/ Product Attributes
    And I click "Create Attribute"
    When I fill form with:
      | Field Name | color  |
      | Type       | Select |
    And I click "Continue"
    And I set Options with:
      | Label |
      | black |
      | white |
    And I save form
    And I remember element "Product Attribute Name" value as "field.color"
    Then I should see "Attribute was successfully saved" flash message
    And I go to Products/ Product Attributes

  Scenario: Update schema
    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Add product attributes to default family
    Given I go to Products/ Product Families
    When I click "Edit" on row "product_attribute_family_code" in grid
    And I fill "Product Family Form" with:
      | General Attributes | [color] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Update product enum attribute
    Given I go to Products/ Products
    And click edit "PSKU3" in grid
    And I fill form with:
      | color | black |
    When I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Create PL1
    Given I go to Sales/ Price Lists
    When I click "Create Price List"
    And I fill form with:
      | Name       | PL1                           |
      | Currencies | US Dollar ($)                 |
      | Active     | true                          |
      | Rule       | product.$field.color$ == null |
    And I click "Add Price Calculation Rules"
    And I fill "Price Calculation Rules Form" with:
      | Price for quantity    | 1    |
      | Price Unit Static     | each |
      | Price Currency Static | $    |
      | Calculate As          | 100  |
      | Priority              | 1    |
    And I save and close form
    Then should see "Price List has been saved" flash message

  Scenario: Create PL2
    Given I go to Sales/ Price Lists
    When I click "Create Price List"
    And I fill form with:
      | Name       | PL2                            |
      | Currencies | US Dollar ($)                  |
      | Active     | true                           |
      | Rule       | product.$field.color$ != null  |
    And I click "Add Price Calculation Rules"
    And I fill "Price Calculation Rules Form" with:
      | Price for quantity    | 2    |
      | Price Unit Static     | each |
      | Price Currency Static | $    |
      | Calculate As          | 50   |
      | Priority              | 1    |
    And I save and close form
    Then should see "Price List has been saved" flash message

  Scenario: Check generated prices in dependent on absent color attribute
    Given I go to Sales/ Price Lists
    And click View PL1 in grid
    Then number of records in "Price list Product prices Grid" should be 2
    And I should see following "Price list Product prices Grid" grid:
      | Product SKU | Product Name  | Quantity    | Unit | Value  | Currency | Type      |
      | PSKU1       | Product1      |      1      | each | 100.00 | USD      | Generated |
      | PSKU2       | Product2      |      1      | each | 100.00 | USD      | Generated |

  Scenario: Check generated prices in dependent on existed color attribute
    Given I go to Sales/ Price Lists
    And click View PL2 in grid
    Then number of records in "Price list Product prices Grid" should be 1
    And I should see following "Price list Product prices Grid" grid:
      | Product SKU | Product Name | Quantity   | Unit | Value   | Currency | Type      |
      | PSKU3       | Product3      |     2     | each |  50.00  | USD      | Generated |
