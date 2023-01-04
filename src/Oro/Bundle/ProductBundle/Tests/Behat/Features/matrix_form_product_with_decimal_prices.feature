@ticket-BAP-20454
@ticket-BB-15279
@regression
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml

Feature: Matrix form product with decimal prices
  In order to be able to work with decimal prices in matrix form
  As a customer
  I go to product page, configure the product and see that prices are accurate and correctly rounded

  Scenario: Feature background
    Given sessions active:
      | User  | first_session  |
      | Admin | second_session |

  Scenario: Configure attributes
    Given I proceed as the Admin
    And login as administrator
    And go to Products / Product Attributes
    When I click "Create Attribute"
    And fill form with:
      | Field Name | Attribute_1 |
      | Type       | Select      |
    And click "Continue"
    And save and close form
    Then I should see "Attribute was successfully saved" flash message

    When I click "Create Attribute"
    And fill form with:
      | Field Name | Attribute_2 |
      | Type       | Select      |
    And I click "Continue"
    And save and close form
    Then I should see "Attribute was successfully saved" flash message
    When I confirm schema update
    Then I should see "Schema updated" flash message

  Scenario: Upload attributes options
    And I click "Import file"
    And I upload "enum_attribute_options.csv" file to "Import Choose File"
    And I click "Import file"

  Scenario: Added product attributes to product family
    Given I go to Products / Product Families
    When I click Edit Default Family in grid
    And set Attribute Groups with:
      | Label           | Visible | Attributes                 |
      | Attribute group | true    | [Attribute_1, Attribute_2] |
    And save form
    Then I should see "Successfully updated" flash message

  Scenario: Create simple product
    Given I go to Products/ Products
    When I click "Create Product"
    And fill form with:
      | Type | Simple |
    And click "Continue"
    And fill "Create Product Form" with:
      | SKU              | Simple_1 |
      | Name             | Simple_1 |
      | Status           | Enable   |
      | Unit Of Quantity | item     |
    And I fill in product attribute "Attribute_1" with "Value 301"
    And I fill in product attribute "Attribute_2" with "Value 1"
    And click "Add Product Price"
    And fill "Product Price Form" with:
      | Price List | Default Price List |
      | Quantity   | 1                  |
      | Value      | 4.9995             |
      | Currency   | $                  |
    When I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Create configurable product
    Given I go to Products/ Products
    When I click "Create Product"
    And fill form with:
      | Type | Configurable |
    And click "Continue"
    And fill "Create Product Form" with:
      | SKU              | Configurable |
      | Name             | Configurable |
      | Status           | Enable       |
      | Unit Of Quantity | item         |
    And fill "ProductForm" with:
      | Configurable Attributes | [Attribute_1, Attribute_2] |
    And check first 1 records in grid
    And save and close form
    Then should see "Product has been saved" flash message

  Scenario: Check the total prices for configurable product
    Given I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    And type "Configurable" in "search"
    And click "Search Button"
    And click "View Details" for "Configurable" product
    When I fill "Matrix Grid Form" with:
      |       | Value 1 | Value 2 |
      | Value | 5       | -       |
    Then I should see "Total QTY 5 | Total $24.9975" in the "Matrix Grid Form" element
    And should not see "Total QTY 5 | Total $24.997499999999999" in the "Matrix Grid Form" element
