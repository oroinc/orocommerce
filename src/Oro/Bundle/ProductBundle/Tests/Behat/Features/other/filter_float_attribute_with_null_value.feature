@ticket-BB-17074
@fixture-OroProductBundle:products_grid.yml
@regression
@skip
Feature: Filter float attribute with null value
  In order to filter float attribute with null value
  As customer
  I need to be able to correct filtering float attribute with null value

  Scenario: Prepare the sessions
    Given sessions active:
      | Admin | first_session  |
      | User  | second_session |

  Scenario: Apply "empty" and "less than" filter to float product attribute with null value
    Given I proceed as the Admin
    And I login as administrator
    And I go to Products/Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field name | test6 |
      | Type       | Float |
    And I click "Continue"
    And fill form with:
      | Filterable | Yes |
    And I save and close form
    And I go to Products/ Product Families
    And I click "Edit" on row "default_family" in grid
    When I fill "Product Family Form" with:
      | Attributes | [test6] |
    And I save and close form
    And I click "Edit" on row "product_attribute_family_code" in grid
    When I fill "Product Family Form" with:
      | Attributes | [test6] |
    And I save and close form
    Given I proceed as the User
    And I go to homepage
    And I click "Category 4"
    And filter test6 as less than "10"
    Then there is no records in grid
    And filter test6 as is empty
    Then I should see "PSKU"
    And filter test6 as is not empty
    Then there is no records in grid

  Scenario: Check if the filter works correctly after setting and removing attribute value
    Given I proceed as the Admin
    And I go to Products/ Products
    And I click edit "PSKU20" in grid
    And fill "ProductForm" with:
      | test6            | 5.5 |
      | PrimaryPrecision |  1  |
    When I save and close form
    Then I should see "Product has been saved" flash message
    When I proceed as the User
    And I reload the page
    Then number of records in "Product Frontend Grid" should be 1
    And I should see "PSKU20"
    When I proceed as the Admin
    And I go to Products/ Products
    And I click edit "PSKU20" in grid
    And I fill in "test6" with ""
    When I save and close form
    Then I should see "Product has been saved" flash message
    When I proceed as the User
    And I reload the page
    Then there is no records in grid
