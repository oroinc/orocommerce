@regression
@ticket-BB-9989
@ticket-BB-14755
@fixture-OroProductBundle:ProductAttributesFixture.yml
Feature: Product attribute select
  In order to have custom attributes for Product entity
  As an Administrator
  I need to be able to add product attribute and have attribute data in search, filter and sorter

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create product attribute
    Given I proceed as the Admin
    And I login as administrator
    And I go to Products/ Product Attributes
    When I click "Create Attribute"
    And I fill form with:
      | Field Name | SelectField |
      | Type       | Select      |
    And I click "Continue"
    Then I should see that "Product Attribute Frontend Options" contains "Searchable"
    And I should see that "Product Attribute Frontend Options" contains "Filterable"
    And I should see that "Product Attribute Frontend Options" contains "Sortable"

    When I fill form with:
      | Searchable | Yes |
      | Filterable | Yes |
      | Sortable   | Yes |
    And I set Options with:
      | Label          |
      | TestValueOne   |
      | TestValueTwo   |
      | 10.5           |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Update product family with new attribute
    Given I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [SelectField] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Update products
    Given I go to Products/ Products
    When I click "Edit" on row "SKU123" in grid
    And I fill "Product Form" with:
      | SelectField | TestValueTwo |
    And I save and close form
    Then I should see "Product has been saved" flash message
    When I go to Products/ Products
    And I click "Edit" on row "SKU456" in grid
    And I fill "Product Form" with:
      | SelectField | 10.5 |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check product grid search
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    When I type "TestValueTwo" in "search"
    And I click "Search Button"
    Then I should see "SKU123" product
    And I should not see "SKU456" product

  Scenario: Check product grid filter and sorter
    Given I click "NewCategory"
    And I should see "SKU123" product
    And I should see "SKU456" product
    When I check "TestValueTwo" in SelectField filter in frontend product grid
    Then I should see "SKU123" product
    And I should not see "SKU456" product
    And grid sorter should have "SelectField" options
    When I check "10.5" in SelectField filter in frontend product grid
    And I should see "SKU123" product
    Then I should see "SKU456" product
    And grid sorter should have "SelectField" options

  Scenario: Update product family and remove new attribute from it
    Given I proceed as the Admin
    And I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And I clear "Attributes" field
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Check product grid search for not searchable attribute
    Given I proceed as the Buyer
    When I type "TestValueTwo" in "search"
    And I click "Search Button"
    Then I should not see "SKU123" product
    And I should not see "SKU456" product

  Scenario: Delete product attribute
    Given I proceed as the Admin
    And I go to Products/ Product Attributes
    When I click Remove "SelectField" in grid
    Then I should see "Are you sure you want to delete this attribute?"
    And I click "Yes"
    Then I should see "Attribute successfully deleted" flash message
    And I should see "Update schema"
