@regression
@ticket-BB-15567
@fixture-OroProductBundle:ProductAttributesFixture.yml

Feature: Product attribute import select existing options
  In order to have custom attributes for Product entity
  As an Administrator
  I need to be able to import product select attribute options

  Scenario: Create product attribute
    And I login as administrator
    And I go to Products/ Product Attributes
    When I click "Create Attribute"
    And I fill form with:
      | Field Name | SelectField |
      | Type       | Select      |
    And I click "Continue"

    And I set Options with:
      | Label          |
      | SelectOption   |
      | SelectOption 1 |
      | SelectOption 2 |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

  Scenario: Create select attribute with duplicate options
    Given I click "Create Attribute"
    And I fill form with:
      | Field Name | Field2 |
      | Type       | Select |
    And I click "Continue"

    And I set Options with:
      | Label |
      | Abc   |
      | ABC   |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

  Scenario: Update schema
    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Product select attribute with duplicate options created correctly
    When I click "Edit" on row "Field2" in grid
    Then I should see values in field "Options":
      | Abc |
      | ABC |

  Scenario: Update product family with new attribute
    Given I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [SelectField,Field2] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Update products
    Given I go to Products/ Products
    When I click "Edit" on row "SKU123" in grid
    And I fill "Product Form" with:
      | SelectField | SelectOption |
      | Field2      | Abc          |
    And I save and close form
    Then I should see "Product has been saved" flash message
    When I go to Products/ Products
    And I click "Edit" on row "SKU456" in grid
    And I fill "Product Form" with:
      | SelectField | SelectOption 1 |
      | Field2      | ABC            |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Import product select attribute with options in different case
    Given I go to Products/ Product Attributes
    And I download Product Attributes' Data Template file
    And I fill template with data:
      | fieldName | type | enum.enum_options.0.label | enum.enum_options.0.is_default | enum.enum_options.1.label | enum.enum_options.1.is_default | enum.enum_options.2.label | enum.enum_options.2.is_default |
      | Field2    | enum | aBC                       | yes                            | ABC                       | no                             | NewOption                 | no                             |
    When I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 0, updated: 1, replaced: 0" text
    When I click "Edit" on row "Field2" in grid
    Then I should see values in field "Options":
      | aBC       |
      | ABC       |
      | NewOption |
    And I should not see values in field "Options":
      | Abc |

  Scenario: Import product select attribute with existing and new option
    Given I go to Products/ Product Attributes
    And I download Product Attributes' Data Template file
    And I fill template with data:
      | fieldName   | type | enum.enum_options.0.label | enum.enum_options.0.is_default | enum.enum_options.1.label | enum.enum_options.1.is_default |
      | SelectField | enum | SelectOption 3            | yes                            | SelectOption 1            | no                             |
    When I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 0, updated: 1, replaced: 0" text
    When I click "Edit" on row "SelectField" in grid
    Then I should see values in field "Options":
      | SelectOption 3 |
      | SelectOption 1 |
    And I should not see values in field "Options":
      | SelectOption |

  Scenario: Check option not present in imported file was removed
    Given I go to Products/ Products
    When I click "View" on row "SKU123" in grid
    Then I should not see "SelectOption"
    And I should not see "Abc"
    And I should not see "aBC"
    And I should not see "ABC"
    And I should not see "NewOption"

  Scenario: Check option present in imported file was not removed
    Given I go to Products/ Products
    When I click "View" on row "SKU456" in grid
    Then I should see "SelectOption 1"
    And I should see "ABC"
