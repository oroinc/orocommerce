@regression
@ticket-BB-9989
@ticket-BB-14989
@ticket-BB-17055
@ticket-BB-21220
@fixture-OroProductBundle:ProductAttributesFixture.yml
Feature: Product attribute multiselect
  In order to have custom attributes for Product entity
  As an Administrator
  I need to be able to add product attribute and have attribute data in search, filter and product view page

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Login to Admin
    Given I proceed as the Admin
    And I login as administrator

  Scenario: Validate product attribute may be created
    Given I go to Products/ Product Attributes
    When I click "Create Attribute"
    And I fill form with:
      | Field Name | image        |
      | Type       | Multi-Select |
    And I click "Continue"
    Then I should see "The 'image' word is reserved for system purposes."

  Scenario: Create product attribute
    When I fill form with:
      | Field Name | MultiSelectField |
      | Type       | Multi-Select     |
    And I click "Continue"
    Then I should see that "Product Attribute Frontend Options" contains "Searchable"
    And I should see that "Product Attribute Frontend Options" contains "Filterable"
    And I should see that "Product Attribute Frontend Options" does not contain "Sortable"

    When I fill form with:
      | Searchable | Yes |
      | Filterable | Yes |
    And I set Options with:
      | Label               |
      | TestMultiValueOne   |
      | TestMultiValueTwo   |
      | TestMultiValueThree |
      | TestMultiValueFour  |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Delete option from attribute
    Given I go to Products/ Product Attributes
    And click edit MultiSelectField in grid
    And I set Options with:
      | Label               |
      | TestMultiValueOne   |
      | TestMultiValueTwo   |
      | TestMultiValueThree |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

  Scenario: Update product family with new attribute
    Given I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [MultiSelectField] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Update product
    Given I go to Products/ Products
    When I click "Edit" on row "SKU123" in grid
    And I check "TestMultiValueOne"
    And I check "TestMultiValueThree"
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check multiselect attributes are available at product view page (correctly formated)
    Given I go to Products/ Products
    When I click "View" on row "SKU123" in grid
    Then I should see "TestMultiValueOne, TestMultiValueThree"

  Scenario: Check product grid search
    Given I proceed as the Buyer
    And I am on the homepage
    When I type "TestMultiValueThree" in "search"
    And I click "Search Button"
    Then I should see "SKU123" product
    And I should not see "SKU456" product

  Scenario: Check product grid filter
    Given I click "NewCategory"
    And I should see "SKU123" product
    And I should see "SKU456" product
    And I click "Grid Filters Button"
    When I check "TestMultiValueOne" in MultiSelectField filter in frontend product grid
    Then I should see "SKU123" product
    And I should not see "SKU456" product

  Scenario: Check if multiselect attribute if available for Reports & Segments
    Given I proceed as the Admin
    And I go to Reports & Segments / Manage Segments
    And I click "Create Segment"
    And I fill "Segment Form" with:
      | Name         | Segment with multiselect |
      | Entity       | Product                  |
      | Segment Type | Dynamic                  |
    And I add the following columns:
      | MultiSelectField |
    When I save and close form
    Then I should see "Segment saved" flash message

  Scenario: Check multiselect attributes are available at store front
    Given I proceed as the Buyer
    When I type "SKU123" in "search"
    And I click "Search Button"

  Scenario: Check attributes in product page with "Default Page" template
    When I click "View Details" for "SKU123" product
    Then I should see "MultiSelectField: TestMultiValueOne, TestMultiValueThree"

  Scenario: Change product page view to "Short Page"
    Given I proceed as the Admin
    And I go to System / Configuration
    And I follow "Commerce/Design/Theme" on configuration sidebar
    When fill "Page Templates form" with:
      | Use Default  | false      |
      | Product Page | Short page |
    And save form
    Then I should see "Configuration saved" flash message

  Scenario: Check attribute in product page with "Short Page" template
    Given I proceed as the Buyer
    When I reload the page
    Then I should see "MultiSelectField: TestMultiValueOne, TestMultiValueThree"

  Scenario: Change product page template to "Two columns page"
    Given I proceed as the Admin
    When fill "Page Templates form" with:
      | Product Page | Two columns page |
    And save form
    Then I should see "Configuration saved" flash message

  Scenario: Check attribute in product page with "Two columns page" template
    Given I proceed as the Buyer
    When I reload the page
    Then I should see "MultiSelectField: TestMultiValueOne, TestMultiValueThree"

  Scenario: Change product page view to "List Page"
    Given I proceed as the Admin
    When fill "Page Templates form" with:
      | Product Page | List page |
    And save form
    Then I should see "Configuration saved" flash message

  Scenario: Check attribute in product page with "List Page" template
    Given I proceed as the Buyer
    When I reload the page
    Then I should see "MultiSelectField: TestMultiValueOne, TestMultiValueThree"

  Scenario: Delete product attribute options
    Given I proceed as the Admin
    And go to Products/ Product Attributes
    When I click edit "MultiSelectField" in grid
    And set Options with:
      | Label               |
      | TestMultiValueOne   |
      | TestMultiValueTwo   |
    And save and close form
    Then I should see "Attribute was successfully saved" flash message

  Scenario: Delete product attribute
    Given I go to Products/ Product Attributes
    When I click Remove "MultiSelectField" in grid
    Then I should see "Are you sure you want to delete this attribute?"
    And I click "Yes"
    Then I should see "Attribute successfully deleted" flash message
    And I should see "Update schema"
