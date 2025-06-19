@random-failed
@regression
@ticket-BAP-20385
@ticket-BAP-20920
@fixture-OroProductBundle:single_product.yml

Feature: Check the audit history after changing the product with attributes
  Make sure that the history of changing attributes is saved and displayed without errors

  Scenario: Feature Background
    Given I login as administrator

  Scenario Outline: Create "Multi-Select" product attribute
    Given I go to Products/ Product Attributes
    And click "Create Attribute"
    When I fill form with:
      | Field Name | <FieldName>  |
      | Type       | Multi-Select |
    And click "Continue"
    And set Options with:
      | Label   |
      | Option1 |
      | Option2 |
    And fill form with:
      | Auditable | Yes |
    And save and close form
    Examples:
      | FieldName                |
      | multi_select_attribute_1 |
      | multi_select_attribute_2 |

  Scenario: Update product family with new attributes
    Given I go to Products/ Product Families
    And click "Edit" on row "default family" in grid
    When I fill "Product Family Form" with:
      | Attributes | [multi_select_attribute_1,multi_select_attribute_2] |
    And save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Check product attribute history after attributes added
    Given I go to Products/Products
    And click "Edit" on row "PSKU1" in grid
    And fill "ProductForm" with:
      | MultiSelectField1 | [Option1, Option2] |
      | MultiSelectField2 | [Option1, Option2] |
    When I save and close form
    Then I should see "Product has been saved" flash message
    When I click "Change History"
    Then I should see in row #1 column "Old values" contains items in "Audit History Grid" grid:
      | [Name:]                     |
      | [multi_select_attribute_1:] |
      | [multi_select_attribute_2:] |
    And I should see following "Audit History Grid" grid:
      | Old Values                                                | New Values                                                                                                                             |
      | Name: multi_select_attribute_1: multi_select_attribute_2: | Name:  Product Name "English (United States)" added multi_select_attribute_1: option1,option2 multi_select_attribute_2: option1,option2 |
    And close ui dialog

  Scenario: Check product attribute history after all attribute option removed
    Given I click "Edit"
    And fill "ProductForm" with:
      | MultiSelectField1 | [] |
      | MultiSelectField2 | [] |
    When I save and close form
    Then I should see "Product has been saved" flash message
    When I click "Change History"
    And I should see following "Audit History Grid" grid:
      | Old Values                                                                          | New Values                                                                                                                              |
      | multi_select_attribute_1: option1,option2 multi_select_attribute_2: option1,option2 | multi_select_attribute_1:  multi_select_attribute_2:                                                                                     |
      | Name: multi_select_attribute_1: multi_select_attribute_2:                           | Name:  Product Name "English (United States)" added multi_select_attribute_1: option1,option2 multi_select_attribute_2: option1,option2 |
    And close ui dialog
