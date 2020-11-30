@ticket-BB-14915
@ticket-BB-15680
@regression
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml

Feature: Matrix forms price for different currencies
  In order to check total price is calculated and displayed correctly on matrix form
  As a buyer
  I want the matrix form to display total price correctly depending on currency and price list

  Scenario: Feature background
    Given sessions active:
      | User  | first_session  |
      | Admin | second_session |
    And I proceed as the Admin
    And I login as administrator
    And I go to System/ Configuration
    And follow "Commerce/Catalog/Pricing" on configuration sidebar
    And fill "Pricing Form" with:
      | Enabled Currencies System | false                     |
      | Enabled Currencies        | [US Dollar ($), Euro (€)] |
    And click "Save settings"

    # Create product attributes
    And I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | Attribute_1 |
      | Type       | Select      |
    And I click "Continue"
    And set Options with:
      | Label   |
      | Value 1 |
      | Value 2 |
    And I save and close form
    And I should see "Attribute was successfully saved" flash message

    And I click "Create Attribute"
    And I fill form with:
      | Field Name | Attribute_2 |
      | Type       | Select      |
    And I click "Continue"
    And set Options with:
      | Label   |
      | Value 1 |
      | Value 2 |
    And I save and close form
    And I should see "Attribute was successfully saved" flash message
    And I confirm schema update

    # Added product attributes to product family
    And I go to Products / Product Families
    And I click Edit Default Family in grid
    And set Attribute Groups with:
      | Label           | Visible | Attributes                 |
      | Attribute group | true    | [Attribute_1, Attribute_2] |
    And I save form
    And I should see "Successfully updated" flash message

    # Create configurable product
    And go to Products/ Products
    And click "Create Product"
    And fill form with:
      | Type | Simple |
    And click "Continue"
    And fill "Create Product Form" with:
      | SKU              | Simple_1 |
      | Name             | Simple_1 |
      | Status           | Enable   |
      | Unit Of Quantity | item     |
    And I fill in product attribute "Attribute_1" with "Value 1"
    And I fill in product attribute "Attribute_2" with "Value 2"
    And click "Add Product Price"
    And fill "Product Price Form" with:
      | Price List | Default Price List |
      | Quantity   | 1                  |
      | Value      | 10                 |
      | Currency   | €                  |
    And click "Add Product Price"
    And fill "Product Price Form" with:
      | Price List 2 | Default Price List |
      | Quantity 2   | 1                  |
      | Value 2      | 12                 |
      | Currency 2   | $                  |
    And save and close form

    And go to Products/ Products
    And click "Create Product"
    And fill form with:
      | Type | Simple |
    And click "Continue"
    And fill "Create Product Form" with:
      | SKU              | Simple_2 |
      | Name             | Simple_2 |
      | Status           | Enable   |
      | Unit Of Quantity | item     |
    And I fill in product attribute "Attribute_1" with "Value 2"
    And I fill in product attribute "Attribute_2" with "Value 1"
    And click "Add Product Price"
    And fill "Product Price Form" with:
      | Price List | Default Price List |
      | Quantity   | 1                  |
      | Value      | 10                 |
      | Currency   | €                  |
    And click "Add Product Price"
    And fill "Product Price Form" with:
      | Price List 2 | Default Price List |
      | Quantity 2   | 1                  |
      | Value 2      | 12                 |
      | Currency 2   | $                  |
    And save and close form

    And go to Products/ Products
    And click "Create Product"
    And I fill form with:
      | Type | Configurable |
    And I click "Continue"
    And fill "Create Product Form" with:
      | SKU              | Simple_3 |
      | Name             | Simple_3 |
      | Status           | Enable   |
      | Unit Of Quantity | item     |
    And I fill "ProductForm" with:
      | Configurable Attributes | [Attribute_1, Attribute_2] |
    And I check Simple_1 and Simple_2 in grid
    And save and close form

  Scenario: Check the container prices for configurable product in different currencies
    Given I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    And type "Simple_3" in "search"
    And click "Search Button"
    And click "View Details" for "Simple_3" product
    When I fill "Matrix Grid Form" with:
      |         | Value 1 | Value 2 |
      | Value 1 | -       | 1       |
      | Value 2 | 1       | -       |
    Then I should see "Total QTY 2 | Total $24.00" in the "Matrix Grid Form" element
    When I click "Currency Switcher"
    And I click "Euro"
    When I fill "Matrix Grid Form" with:
      |         | Value 1 | Value 2 |
      | Value 1 | -       | 1       |
      | Value 2 | 1       | -       |
    Then I should see "Total QTY 2 | Total €20.00" in the "Matrix Grid Form" element

  Scenario: Create custom price list and attach to customer
    Given I proceed as the Admin

    When I go to Sales/ Price Lists
    And I click "Create Price List"
    And I fill form with:
      | Name       | Customer Price List |
      | Currencies | US Dollar ($)       |
      | Active     | true                |
    And I save and close form
    Then I should see "Price List has been saved" flash message

    When I click "Add Product Price"
    And fill "Add Product Price Form" with:
      | Product  | Simple_1 |
      | Quantity | 1        |
      | Unit     | item     |
      | Price    | 7        |
    And click "Save"
    Then I should see "Product Price has been added" flash message

    When I click "Add Product Price"
    And fill "Add Product Price Form" with:
      | Product  | Simple_2 |
      | Quantity | 1        |
      | Unit     | item     |
      | Price    | 5        |
    And click "Save"
    Then I should see "Product Price has been added" flash message

    When I go to Customers/ Customers
    And click edit "AmandaRCole" in grid
    And fill "Customer Form" with:
      | Price List | Customer Price List |
    And save and close form
    Then should see "Customer has been saved" flash message

  Scenario: Check custom price list is successfully applied
    Given I proceed as the User
    And type "Simple_3" in "search"
    And click "Search Button"
    And click "View Details" for "Simple_3" product
    When I click "Currency Switcher"
    And I click "US Dollar"
    When I fill "Matrix Grid Form" with:
      |         | Value 1 | Value 2 |
      | Value 1 | -       | 1       |
      | Value 2 | 1       | -       |
    Then I should see "Total QTY 2 | Total $12.00" in the "Matrix Grid Form" element
