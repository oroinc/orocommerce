@regression
@fixture-OroShoppingListBundle:ShoppingListFixtureForConfigurableProducts.yml

Feature: Delete line item from shopping list for configurable products
  In order to have ability to manage shopping list
  As a Buyer
  I want to delete line item from Shopping List and this should not affect
  another line items in case if configured product representation at checkout page was not configured as matrix view.

  Scenario: Create sessions
    Given sessions active:
      | User  | first_session  |
      | Admin | second_session |

  Scenario: Prepare product attribute
    Given I proceed as the Admin
    Given I login as administrator
    When I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | BooleanAttribute |
      | Type       | Boolean          |
    And I click "Continue"
    And I fill form with:
      | Label      | BooleanAttribute |
    And I save form
    Then I should see "Attribute was successfully saved" flash message
    When I go to Products / Product Attributes
    And I confirm schema update
    Then I go to Products / Product Families
    When I click Edit Attribute Family in grid
    And set Attribute Groups with:
      | Label           | Visible | Attributes |
      | Attribute group | true    | [SKU, Name, Is Featured, New Arrival, Brand, Description, Short Description, Images, Inventory Status, Meta title, Meta description, Meta keywords, Product prices, BooleanAttribute] |
    And I save form
    Then I should see "Successfully updated" flash message

  Scenario: Prepare configurable product
    Given I go to Products / Products
    When filter SKU as is equal to "PROD_A_1"
    And I click Edit PROD_A_1 in grid
    And I fill in product attribute "BooleanAttribute" with "Yes"
    And I save form
    Then I should see "Product has been saved" flash message
    When I go to Products / Products
    And filter SKU as is equal to "PROD_A_2"
    And I click Edit PROD_A_2 in grid
    And I fill in product attribute "BooleanAttribute" with "No"
    And I save form
    Then I should see "Product has been saved" flash message
    When I go to Products / Products
    And filter SKU as is equal to "CNFA"
    And I click Edit CNFA in grid
    And I should see "No records found"
    And I fill "ProductForm" with:
      | Configurable Attributes | [BooleanAttribute] |
    And I check PROD_A_1 and PROD_A_2 in grid
    And I save form
    Then I should see "Product has been saved" flash message

  Scenario: Remove configurable product when it shown as matrix at shopping list
    Given I proceed as the User
    Given I signed in as AmandaRCole@example.org on the store frontend
    When Buyer is on Configurable products list 2
    Then I should see an "One Dimensional Matrix Grid Form" element
    And I should see "ConfigurableProductA"
    And I should see "Item #: CNFA"
    And I delete line item 1 in "Shopping List Line Items Table"
    And I click "Yes, Delete"
    Then I should see "The Shopping List is empty. Please add at least one product."

  Scenario: Reconfigure matrix line item form representation for configurable products
    Given I proceed as the Admin
    When I go to System/ Configuration
    And I follow "Commerce/Product/Configurable Products" on configuration sidebar
    And uncheck "Use default" for "Shopping Lists" field
    And I fill in "Shopping Lists" with "Group Single Products"
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: Remove configurable product when it shown as matrix at shopping list
    Given I proceed as the User
    When Buyer is on Configurable products list 1
    Then I should not see an "One Dimensional Matrix Grid Form" element
    And I should see following line items in "Shopping List Line Items Table":
      | SKU  | Quantity | Unit |
      | CNFA | 5        | item |
      | CNFA | 2        | item |
    And I should see "BooleanAttribute: Yes"
    And I should see "BooleanAttribute: No"
    When I delete line item 1 in "Shopping List Line Items Table"
    And I click "Yes, Delete"
    Then I should see "Shopping list item has been deleted" flash message
    And I should see following line items in "Shopping List Line Items Table":
      | SKU  | Quantity | Unit |
      | CNFA | 2        | item |
    And I should not see "BooleanAttribute: Yes"
    And I should see "BooleanAttribute: No"
