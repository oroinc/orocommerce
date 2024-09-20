@regression
@ticket-BB-22600
@fixture-OroProductBundle:ProductAttributesFixture.yml

Feature: Product search and filter using enum attribute with zero value
  Check that the product will be serarchable and filtrable by select attribute with zero value

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | User  | second_session |

  Scenario: Create custom attribute and add it to default product family
    Given I proceed as the Admin
    And I login as administrator
    When I go to Products/ Product Attributes
    And I click "Create Attribute"
    Then I fill form with:
      | Field Name | SelectField |
      | Type       | Select      |
    And click "Continue"
    And I fill form with:
      | Searchable | Yes |
      | Filterable | Yes |
      | Sortable   | Yes |
    And set Options with:
      | Label |
      | 0     |
      | 1     |
    And I save and close form

  Scenario: Update product family with new attribute
    Given I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [SelectField] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Adding attribute to existing product
    When I go to Products/ Products
    And I click "Edit" on row "SKU123" in grid
    And I fill "Product Form" with:
      | SelectField | 0 |
    And I save and close form
    Then I should see "Product has been saved" flash message
    When I go to Products/ Products
    And I click "Edit" on row "SKU456" in grid
    And I fill "Product Form" with:
      | SelectField | 1 |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check frontend search by select field with zero value
    Given I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    And I go to the homepage
    And I type "0" in "search"
    When click "Search Button"
    Then I should see "Search Results for \"0\""
    And I should see "SKU123" in grid "Product Frontend Grid"

  Scenario: Check frontend filters in product grid by select field with zero value
    When I type "" in "search"
    And I click "Search Button"
    Then I check "0" in SelectField filter in frontend product grid
    And number of records in "Product Frontend Grid" should be 1
    And SKU123 must be first record in "Product Frontend Grid"
    And I click "Clear All Filters"

  Scenario: Check frontend sorters in product grid by by select field
    When I sort frontend grid "Product Frontend Grid" by "SelectField (High to Low)"
    Then SKU456 must be first record in "Product Frontend Grid"
    When I sort frontend grid "Product Frontend Grid" by "SelectField (Low to High)"
    Then SKU123 must be first record in "Product Frontend Grid"
