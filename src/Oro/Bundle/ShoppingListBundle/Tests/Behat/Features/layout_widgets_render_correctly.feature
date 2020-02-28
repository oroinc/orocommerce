@ticket-BB-12666
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
@fixture-OroProductBundle:configurable_products.yml
Feature: Layout widgets render correctly
  In order to check layout widget render correctly when building several layout block ids at once
  As a user
  I trigger logic to rebuild layout and unsure there is no duplication of widget content

  Scenario: Create sessions
    Given sessions active:
      | User  | first_session  |
      | Admin | second_session |

  Scenario: Prepare product attributes
    Given I proceed as the Admin
    And I login as administrator
    And I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | Attribute_1 |
      | Type       | Select      |
    And I click "Continue"
    And I fill form with:
      | Label      | Attribute 1 |
    And set Options with:
      | Label    |
      | Value 11 |
      | Value 12 |
      | Value 13 |
      | Value 14 |
    When I save form
    Then I should see "Attribute was successfully saved" flash message
    And I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | Attribute_2 |
      | Type       | Select      |
    And I click "Continue"
    And I fill form with:
      | Label      | Attribute 2 |
    And set Options with:
      | Label    |
      | Value 21 |
      | Value 22 |
      | Value 23 |
    When I save form
    Then I should see "Attribute was successfully saved" flash message
    And I go to Products / Product Attributes
    And click update schema
    And I go to Products / Product Families
    And I click Edit Attribute Family in grid
    And set Attribute Groups with:
      | Label           | Visible | Attributes |
      | Attribute group | true    | [SKU, Name, Is Featured, New Arrival, Brand, Description, Short Description, Images, Inventory Status, Meta title, Meta description, Meta keywords, Product prices, Attribute 1, Attribute 2] |
    When I save form
    Then I should see "Successfully updated" flash message

  Scenario: Prepare configurable products
    And I go to Products / Products
    And filter SKU as is equal to "PROD_B_11"
    And I click Edit PROD_B_11 in grid
    And I fill in product attribute "Attribute_1" with "Value 11"
    And I fill in product attribute "Attribute_2" with "Value 21"
    When I save form
    Then I should see "Product has been saved" flash message
    And I go to Products / Products
    And filter SKU as is equal to "PROD_B_12"
    And I click Edit PROD_B_12 in grid
    And I fill in product attribute "Attribute_1" with "Value 11"
    And I fill in product attribute "Attribute_2" with "Value 22"
    When I save form
    Then I should see "Product has been saved" flash message
    And I go to Products / Products
    And filter SKU as is equal to "PROD_B_21"
    And I click Edit PROD_B_21 in grid
    And I fill in product attribute "Attribute_1" with "Value 12"
    And I fill in product attribute "Attribute_2" with "Value 21"
    When I save form
    Then I should see "Product has been saved" flash message

  Scenario: Save configurable products with simple products selected
    And I go to Products / Products
    And filter SKU as is equal to "CNFB"
    And I click Edit CNFB in grid
    And I should see "There are no product variants"
    And I fill "ProductForm" with:
      | Configurable Attributes | [Attribute 1, Attribute 2] |
    And I check PROD_B_11 and PROD_B_12 in grid
    And I check PROD_B_21 record in grid
    When I save form
    Then I should see "Product has been saved" flash message

  Scenario: Check widget is not duplicated on layout rerender
    Given I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    And type "CNFB" in "search"
    And click "Search Button"
    When click "View Details" for "CNFB" product
    Then I should see an "Matrix Grid Form" element
    And I click "Add to Shopping List"
    And I open shopping list widget
    When I click "View Details"
    Then I should see an "Matrix Grid Form" element
    When I click "Update"
    Then I should not see an "Duplicated Matrix Order Widget" element
    And I should see an "Matrix Order Widget" element
