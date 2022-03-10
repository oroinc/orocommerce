@regression
@ticket-BB-9989
@ticket-BB-16686
@ticket-BB-16591
@ticket-BB-7152
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
    And go to System/Configuration
    And follow "Commerce/Product/Configurable Products" on configuration sidebar
    And I fill "Display Simple Variations Form" with:
      | Display Simple Variations Default | false      |
      | Display Simple Variations         | everywhere |
    And I save setting
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

  Scenario: Create configurable product
    Given I go to Products/ Products
    And click "Create Product"
    And fill form with:
      | Type           | Configurable |
    And click "NewCategory"
    And click "Continue"
    And fill "Create Product Form" with:
      | Name                                 | ConfigurableProd |
      | SKU                                  | Conf_Sku         |
      | Status                               | Enable           |
      | Unit Of Quantity                     | set              |
      | Configurable Attributes BooleanField | true             |
    And save and close form
    And click "Edit Product"
    And click on SKU123 in grid
    And save and close form
    Then should see "Product has been saved" flash message

  Scenario: Check product grid sorter
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    When I click "NewCategory"
    Then grid sorter should have "BooleanField" options

  @skip
#  Unskip when BB-16686 will be fixed
  Scenario: Check product grid filter
    Given I click "Grid Filters Button"
    When I check "Yes" in BooleanField filter in frontend product grid
    Then I should see "SKU123" product
    And I should not see "SKU456" product
    When I check "No" in BooleanField filter in frontend product grid
    Then I should see "SKU123" product
    And I should see "SKU456" product

  @skip
#  Unskip when BB-16686 will be fixed
  Scenario: Check configurable product view
    Given I click "View Details" for "Conf_Sku" product
    Then should see an "BooleanField" element
    And should not see "BooleanField: No"

  Scenario: Remove configurable product
    Given I proceed as the Admin
    And I go to Products/ Products
    And click delete "Conf_Sku" in grid
    And click "Yes, Delete"

  Scenario: Remove new attribute from product family
    Given I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And I clear "Attributes" field
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Check that attribute is not present
    Given I go to Products/ Products
    When I click "View" on row "SKU123" in grid
    Then I should not see "BooleanField"

  Scenario: Update product family with new attribute again to check if attribute data is deleted
    Given I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [BooleanField] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Check that attribute is present but its data is gone
    Given I go to Products/ Products
    When I click "View" on row "SKU123" in grid
    Then I should see product with:
      | BooleanField | N/A |

  Scenario: Delete product attribute
    Given I go to Products/ Product Attributes
    When I click Remove "BooleanField" in grid
    Then I should see "Are you sure you want to delete this attribute?"
    And I click "Yes"
    Then I should see "Attribute successfully deleted" flash message
    And I should see "Update schema"
