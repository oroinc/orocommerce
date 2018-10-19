@ticket-BAP-17313
@fixture-OroProductBundle:ProductAttributesFixture.yml

Feature: Product attribute not in family
  In order to ensure that product attribute column is present in columns manager only when attribute is in family
  As an Administrator
  I need to add product attribute, then check it is not present in datagrid columns manager
  I need to add product attribute to family, then check it is present in datagrid columns manager

  Scenario: Create product attribute
    Given I login as administrator
    And I go to Products/ Product Attributes
    When I click "Create Attribute"
    And I fill form with:
      | Field Name | SelectField |
      | Type       | Select      |
    And I click "Continue"
    And set Options with:
      | Label               |
      | CustomSelectOption1 |
      | CustomSelectOption2 |
    And I save and close form
    And I should see "Attribute was successfully saved" flash message
    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Ensure product attribute is not present in datagrid column manager
    Given I go to Products/ Products
    When click "Grid Settings"
    Then I should not see "Grid Settings SelectField"
    And click "Grid Settings"

  Scenario: Update product family with new attribute
    Given I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [SelectField] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Update attribute in product
    Given I go to Products/ Products
    When I click "Edit" on row "SKU123" in grid
    And I fill "Product Form" with:
      | SelectField | CustomSelectOption1 |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Ensure product attribute is present in datagrid column manager and can be loaded
    Given I go to Products/ Products
    And I show column SelectField in grid
    Then I should see that "CustomSelectOption1" is in 2 row
