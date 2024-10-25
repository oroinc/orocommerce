@regression
@ticket-BB-9989
@fixture-OroProductBundle:ProductAttributesFixture.yml
Feature: Product attribute bigint
  In order to have custom attributes for Product entity
  As an Administrator
  I need to be able to add product attribute and have attribute data in search, filter and sorter

  Scenario: Create product attribute
    Given I login as administrator
    And I go to Products/ Product Attributes
    When I click "Create Attribute"
    And I fill form with:
      | Field Name | BigIntField |
      | Type       | BigInt      |
    And I click "Continue"
    Then I should see that "Product Attribute Storefront Options" does not contain "Searchable"
    And I should see that "Product Attribute Storefront Options" contains "Filterable"
    And I should see that "Product Attribute Storefront Options" contains "Sortable"

    When I fill form with:
      | Filterable | Yes |
      | Sortable   | Yes |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message
    And I should not see "Update schema"

    When I check "BigInt" in "Data Type" filter
    Then I should see following grid:
      | Name        | Storage type     |
      | BigIntField | Serialized field |

  Scenario: Update product family with new attribute
    Given I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [BigIntField] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Update product
    Given I go to Products/ Products
    When I click "Edit" on row "SKU123" in grid
    And I fill "Product Form" with:
      | BigIntField | 9007199254740992 |
    And I save form
    Then I should see validation errors:
      | BigIntField | This value should be between -9,007,199,254,740,991 and 9,007,199,254,740,991. |
    When I fill "Product Form" with:
      | BigIntField | -9007199254740992 |
    And I save form
    Then I should see validation errors:
      | BigIntField | This value should be between -9,007,199,254,740,991 and 9,007,199,254,740,991. |
    When I fill "Product Form" with:
      | BigIntField | 9007199254740991 |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check product grid search
    Given I login as AmandaRCole@example.org buyer
    When I type "9007199254740991" in "search"
    And I click "Search Button"
    Then I should not see "SKU123" product
    And I should not see "SKU456" product

  Scenario: Check product grid filter and sorter
    Given I click "NewCategory" in hamburger menu
    And I should see "SKU123" product
    And I should see "SKU456" product
    When I filter BigIntField as equals "9007199254740991"
    Then I should see "SKU123" product
    And I should not see "SKU456" product
    And grid sorter should have "BigIntField" options

  Scenario: Delete product attribute
    Given I login as administrator
    Given I go to Products/ Product Attributes
    When I click Remove "BigIntField" in grid
    Then I should see "Are you sure you want to delete this attribute?"
    And I click "Yes"
    Then I should see "Attribute successfully deleted" flash message
    And I should not see "Update schema"
