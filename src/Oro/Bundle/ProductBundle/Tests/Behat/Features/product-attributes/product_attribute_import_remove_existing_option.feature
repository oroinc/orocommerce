@regression
@ticket-BB-18900
@fixture-OroProductBundle:ConfigurableProductFixtures.yml

Feature: Product attribute import remove existing option
  In order to have custom attributes for Product entity
  As an Administrator
  I need to be able to add and remove unused product select attribute options via import

  Scenario: Import product attributes
    Given I login as administrator
    When I go to Products/ Product Attributes
    And I download Product Attributes' Data Template file
    And I fill template with data:
      | fieldName   | type | entity.label | enum.enum_options.0.label | enum.enum_options.0.is_default | enum.enum_options.1.label | enum.enum_options.1.is_default |
      | SelectField | enum | SelectField  | SelectOption 1            | yes                            | SelectOption 2            | no                             |
    And I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 1, updated: 0, replaced: 0" text

  Scenario: Update schema
    Given I go to Products/ Product Attributes
    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Update product family with new attribute
    Given I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [SelectField] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Update simple products with select field
    When I go to Products/ Products
    When I click "Edit" on row "1GB81" in grid
    And I fill "Product Form" with:
      | SelectField | SelectOption 1 |
    And I save and close form
    Then I should see "Product has been saved" flash message

    When I go to Products/ Products
    When I click "Edit" on row "1GB82" in grid
    And I fill "Product Form" with:
      | SelectField | SelectOption 2 |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Prepare configurable product
    When I go to Products/Products
    And I filter SKU as is equal to "1GB83"
    And I click Edit 1GB83 in grid
    When I fill "ProductForm" with:
      | Configurable Attributes | [SelectField] |
    And I check 1GB81 and 1GB82 in grid
    And I save form
    Then I should see "Product has been saved" flash message

  Scenario: Import product select attribute with new options
    Given I go to Products/ Product Attributes
    And I download Product Attributes' Data Template file
    And I fill template with data:
      | fieldName   | type | enum.enum_options.0.label | enum.enum_options.0.is_default | enum.enum_options.1.label | enum.enum_options.1.is_default | enum.enum_options.2.label | enum.enum_options.2.is_default |
      | SelectField | enum | SelectOption 1            | yes                            | SelectOption 2            | no                             | SelectOption 3            | no                             |
    When I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 0, updated: 1, replaced: 0" text

  Scenario: Import product select attribute with removed new options
    Given I go to Products/ Product Attributes
    And I download Product Attributes' Data Template file
    And I fill template with data:
      | fieldName   | type | enum.enum_options.0.label | enum.enum_options.0.is_default | enum.enum_options.1.label | enum.enum_options.1.is_default |
      | SelectField | enum | SelectOption 1            | yes                            | SelectOption 2            | no                             |
    When I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 0, updated: 1, replaced: 0" text

  Scenario: Import product select attribute with removed used options
    Given I go to Products/ Product Attributes
    And I download Product Attributes' Data Template file
    And I fill template with data:
      | fieldName   | type | enum.enum_options.0.label | enum.enum_options.0.is_default |
      | SelectField | enum | SelectOption 1            | yes                            |
    When I import file
    Then Email should contains the following "Errors: 1 processed: 0, read: 1, added: 0, updated: 0, replaced: 0" text
    When I follow "Error log" link from the email
    Then I should see "Error in row #1. enum.enum_options: The \"SelectOption 2\" options cannot be deleted because they are used in the following configurable products: 1GB83"
