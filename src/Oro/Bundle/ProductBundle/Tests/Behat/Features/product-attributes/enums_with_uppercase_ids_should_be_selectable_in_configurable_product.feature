@regression
@ticket-BB-14591
@fixture-OroProductBundle:related_items_customer_users.yml
@fixture-OroProductBundle:configurable_products.yml
@skip
Feature: Enums with uppercase IDs should be selectable in configurable product
  In order to use configurable products
  As a Buyer
  I want to be able to see configurable product options if they are created using uppercase symbols

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Prepare product attributes
    Given I proceed as the Admin
    And I login as administrator
    When I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | Color  |
      | Type       | Select |
    And I click "Continue"
    And I set Options with:
      | Label |
      | Black |
      | White |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

    When I go to Products / Product Attributes
    And I click update schema
    Then I should see "Schema updated" flash message

    Given I go to Products / Product Families
    When I click Edit "Attribute Family" in grid
    And set Attribute Groups with:
      | Label           | Visible | Attributes |
      | Attribute group | true    | [SKU, Name, Is Featured, New Arrival, Brand, Description, Short Description, Images, Inventory Status, Meta title, Meta description, Meta keywords, Product prices, Color] |
    And I save form
    Then I should see "Successfully updated" flash message

    And I change "Color" attribute enum ID from "black" to "Black"
    And I change "Color" attribute enum ID from "white" to "White"

  Scenario: Prepare configurable product
    Given I go to Products / Products
    When filter SKU as is equal to "PROD_A_1"
    And I click Edit "PROD_A_1" in grid
    And I fill in product attribute "Color" with "Black"
    And I save form
    Then I should see "Product has been saved" flash message
    When I go to Products / Products
    And filter SKU as is equal to "PROD_A_2"
    And I click Edit "PROD_A_2" in grid
    And I fill in product attribute "Color" with "White"
    And I save form
    Then I should see "Product has been saved" flash message
    When I go to Products / Products
    And filter SKU as is equal to "CNFA"
    And I click Edit "CNFA" in grid
    And I should see "There are no product variants"
    And I fill "ProductForm" with:
      | Configurable Attributes | [Color] |
    And I check PROD_A_1 and PROD_A_2 in grid
    And I save form
    Then I should see "Product has been saved" flash message
    When I go to System/Configuration
    And I follow "Commerce/Product/Configurable Products" on configuration sidebar
    And uncheck "Use default" for "Product Views" field
    And I fill form with:
      | Product Views | No Matrix Form |
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: Check that configurable product form has Color variants
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And type "CNFA" in "search"
    And click "Search Button"
    And click "View Details" for "CNFA" product
    Then I should see an "ConfigurableProductForm" element
    And "ConfigurableProductForm" must contains values:
      | Color | Black |
    When I fill "ConfigurableProductForm" with:
      | Color | White |
    And I click "Add to Shopping List"
    Then I should see 'Product has been added to "Shopping List"' flash message
