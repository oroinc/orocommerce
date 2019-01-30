@regression
@ticket-BB-9989
@fixture-OroProductBundle:ProductAttributesFixture.yml
Feature: Product attribute date
  In order to have custom attributes for Product entity
  As an Administrator
  I need to be able to add product attribute and have attribute data in search, filter and sorter

  Scenario: Create product attribute
    Given I login as administrator
    And I go to Products/ Product Attributes
    When I click "Create Attribute"
    And I fill form with:
      | Field Name | DateField |
      | Type       | Date      |
    And I click "Continue"
    Then I should see that "Product Attribute Frontend Options" does not contain "Searchable"
    And I should see that "Product Attribute Frontend Options" does not contain "Filterable"
    And I should see that "Product Attribute Frontend Options" contains "Sortable"

    When I fill form with:
      | Sortable | Yes |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message
    And I should not see "Update schema"

    When I check "Date" in "Data Type" filter
    Then I should see following grid:
      | Name      | Storage type     |
      | DateField | Serialized field |

  Scenario: Update product family with new attribute
    Given I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [DateField] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Update product
    Given I go to Products/ Products
    When I click "Edit" on row "SKU123" in grid
    And I fill "Product Form" with:
      | DateField | Sep 8, 2017 |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check product grid sorter
    Given I login as AmandaRCole@example.org buyer
    When I click "NewCategory"
    Then grid sorter should have "DateField" options

  Scenario: Delete product attribute
    Given I login as administrator
    Given I go to Products/ Product Attributes
    When I click Remove "DateField" in grid
    Then I should see "Are you sure you want to delete this attribute?"
    And I click "Yes"
    Then I should see "Attribute successfully deleted" flash message
    And I should not see "Update schema"
