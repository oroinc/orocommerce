@regression
@ticket-BB-9989
@ticket-BB-13403
@fixture-OroProductBundle:ProductAttributesFixture.yml
Feature: Product attribute text with fuzzy search
  In order to have custom attributes for Product entity
  As an Administrator
  I need to be able to add product attribute and have attribute data in search and filter

  Scenario: Create product attribute
    Given I login as administrator
    And I go to Products/ Product Attributes
    When I click "Create Attribute"
    And I fill form with:
      | Field Name | MultiLineTextField |
      | Type       | Text               |
    And I click "Continue"
    Then I should see that "Product Attribute Frontend Options" contains "Searchable"
    And I should see that "Product Attribute Frontend Options" contains "Filterable"
    And I should see that "Product Attribute Frontend Options" does not contain "Sortable"

    When I fill form with:
      | Searchable | Yes          |
      | Filterable | Yes          |
      | Filter by  | Fuzzy search |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message
    And I should not see "Update schema"

  Scenario: Update product family with new attribute
    Given I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [MultiLineTextField] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Update product
    Given I go to Products/ Products
    When I click "Edit" on row "SKU123" in grid
    And I fill in "MultiLineTextField" with:
      """
      ASDF123
      ASDF456
      """
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check product grid search
    Given I login as AmandaRCole@example.org buyer
    When I type "ASDF123" in "search"
    And I click "Search Button"
    Then I should see "SKU123" product
    And I should not see "SKU456" product

  Scenario: Check product grid filter
    Given I click "NewCategory"
    And I should see "SKU123" product
    And I should see "SKU456" product

    When I filter MultiLineTextField as contains "ASDF123"
    Then I should see "SKU123" product
    And I should not see "SKU456" product

    When I filter MultiLineTextField as contains "ASDF456"
    Then I should see "SKU123" product
    And I should not see "SKU456" product

  Scenario: Delete product attribute
    Given I login as administrator
    Given I go to Products/ Product Attributes
    When I click Remove "MultiLineTextField" in grid
    Then I should see "Are you sure you want to delete this attribute?"
    And I click "Yes"
    Then I should see "Attribute successfully deleted" flash message
    And I should not see "Update schema"
