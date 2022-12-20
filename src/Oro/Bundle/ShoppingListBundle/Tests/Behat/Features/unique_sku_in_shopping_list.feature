@regression
@fixture-OroShoppingListBundle:unique_sku_in_shopping_list.yml
Feature: Unique SKU in shopping list

  Scenario: Create different window session
    Given sessions active:
      | Admin     |first_session |
      | Guest     |second_session|

  Scenario: Pre actions: creating product attributes and setting values in product
    # Prepare product attributes
    Given I proceed as the Admin
    And I login as administrator

    ## Create Color attribute
    And I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | color_attribute  |
      | Type       | Select |
    And I click "Continue"
    And I fill form with:
      | Label      | Color Attribute  |
      | Filterable | Yes  |
    And set Options with:
      | Label  |
      | Green  |
      | Red    |
      | Yellow |
    And I save form
    Then I should see "Attribute was successfully saved" flash message

    ## Create Size attribute
    And I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | size_attribute  |
      | Type       | Boolean |
    And I click "Continue"
    And I fill form with:
      | Label | Size Attribute |
    And I save form
    Then I should see "Attribute was successfully saved" flash message

    ## Update schema
    And I go to Products / Product Attributes
    And I confirm schema update

    ## Update attribute family
    And I go to Products / Product Families
    And I click Edit Product Attribute Family in grid
    And set Attribute Groups with:
      | Label         | Visible | Attributes                        |
      | T-shirt group | true    | [Color Attribute, Size Attribute] |
    And I save form
    Then I should see "Successfully updated" flash message

    # Prepare simple products
    And I go to Products / Products
    And I click Edit gtsh_l in grid
    And I fill in product attribute "color_attribute" with "Green"
    And I fill in product attribute "size_attribute" with "Yes"
    And I save form
    Then I should see "Product has been saved" flash message

    # Save configurable product with simple products selected
    And I go to Products / Products
    And I click Edit shirt_101 in grid
    And I should see "There are no product variants"
    And I fill "ProductForm" with:
      | Configurable Attributes | [Color Attribute, Size Attribute] |
    And I check gtsh_l record in grid
    And I save form
    Then I should see "Product has been saved" flash message

        # Save configurable product with simple products selected
    And I go to Products / Products
    And I click Edit shirt_102 in grid
    And I should see "There are no product variants"
    And I fill "ProductForm" with:
      | Configurable Attributes | [Color Attribute, Size Attribute] |
    And I check gtsh_l record in grid
    And I save form
    Then I should see "Product has been saved" flash message

  Scenario: Fill Matrix Order Form of configurable product
    Given I proceed as the Guest
    And I am on homepage
    And I signed in as AmandaRCole@example.org on the store frontend
    Given I open product with sku "shirt_101" on the store frontend
    Then I should see an "Matrix Grid Form" element
    And I fill "Shirt_101 Matrix Grid Order Form" with:
      | Green Yes Quantity | 10 |
    And I click "Add to Shopping List" in matrix order window
    Then I should see 'Shopping list "Shopping list" was updated successfully' flash message
    And "Shirt_101 Matrix Grid Order Form" must contains values:
      | Green Yes Quantity | 10 |

  Scenario: Check Matrix Order Form of another configurable product with same simple product as variant
    Given I open product with sku "shirt_102" on the store frontend
    Then I should see an "Matrix Grid Form" element
    And "Shirt_102 Matrix Grid Order Form" must contains values:
      | Green Yes Quantity | 10 |
    And I should see "Add to Shopping list"
