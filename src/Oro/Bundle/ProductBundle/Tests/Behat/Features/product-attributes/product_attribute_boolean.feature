@regression
@ticket-BB-9989
@fixture-OroProductBundle:ProductAttributesFixture.yml
Feature: Product attribute boolean
  In order to have custom attributes for Product entity
  As an Administrator
  I need to be able to add product attribute and have attribute data in search, filter and sorter

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create product attribute
    Given I proceed as the Admin
    And I login as administrator
    And I go to Products/ Product Attributes
    When I click "Create Attribute"
    And I fill form with:
      | Field Name | BooleanField |
      | Type       | Boolean      |
    And I click "Continue"
    Then I should see that "Product Attribute Frontend Options" does not contain "Searchable"
    And I should see that "Product Attribute Frontend Options" contains "Filterable"
    And I should see that "Product Attribute Frontend Options" contains "Sortable"

    When I fill form with:
      | Filterable | Yes |
      | Sortable   | Yes |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Update product family with new attribute
    Given I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [BooleanField] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Update product
    Given I go to Products/ Products
    When I click "Edit" on row "SKU123" in grid
    And I fill "Product Form" with:
      | BooleanField | Yes |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check product grid sorter
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    When I click "NewCategory"
    Then grid sorter should have "BooleanField" options

  Scenario: Check product grid filter
    Given I proceed as the Buyer
    When I check "Yes" in BooleanField filter in frontend product grid
    Then I should see "SKU123" product
    And I should not see "SKU456" product
    When I check "No" in BooleanField filter in frontend product grid
    Then I should see "SKU123" product
    And I should see "SKU456" product

  Scenario: Delete product attribute
    Given I proceed as the Admin
    And I go to Products/ Product Attributes
    When I click Remove "BooleanField" in grid
    Then I should see "Are you sure you want to delete this attribute?"
    And I click "Yes"
    Then I should see "Attribute successfully deleted" flash message
    And I should see "Update schema"
