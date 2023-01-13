@regression
@fixture-OroShoppingListBundle:ShoppingListFixtureForConfigurableProducts.yml

Feature: Delete line item from shopping list for configurable products
  In order to have ability to manage shopping list
  As a Buyer
  I want to delete line item from Shopping List and this should not affect
  another line items in case if configured product representation at checkout page was not configured as matrix view

  Scenario: Create sessions
    Given sessions active:
      | User  | first_session  |
      | Admin | second_session |
    And I proceed as the Admin
    And I login as administrator
    And I go to System / Localization / Translations
    And I filter Key as equal to "oro.frontend.shoppinglist.lineitem.unit.label"
    And I edit "oro.frontend.shoppinglist.lineitem.unit.label" Translated Value as "Unit"

  Scenario: Prepare product attribute
    Given I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | BooleanAttribute |
      | Type       | Boolean          |
    And I click "Continue"
    And I fill form with:
      | Label | BooleanAttribute |
    And I save form
    Then I should see "Attribute was successfully saved" flash message
    When I go to Products / Product Attributes
    And I confirm schema update
    Then I go to Products / Product Families
    When I click Edit Default in grid
    And set Attribute Groups with:
      | Label           | Visible | Attributes                                                                                                                                                                            |
      | Attribute group | true    | [BooleanAttribute] |
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
    And I should see "There are no product variants"
    And I fill "ProductForm" with:
      | Configurable Attributes | [BooleanAttribute] |
    And I check PROD_A_1 and PROD_A_2 in grid
    And I save form
    Then I should see "Product has been saved" flash message

  Scenario: Remove configurable product when it shown as matrix at shopping list
    Given I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    When Buyer is on "Configurable products list 2" shopping list
    And I click "Shopping List Actions"
    And I click "Edit"
    And I should see following grid:
      | SKU      | Item                                       | Qty Update All |
      | PROD_A_1 | ConfigurableProductA BooleanAttribute: Yes | 5 item         |
      | PROD_A_2 | ConfigurableProductA BooleanAttribute: No  | 2 item         |
    And I click Delete PROD_A_1 in grid
    And I click "Yes, Delete" in modal window
    Then I should see 'The "ConfigurableProductA" product was successfully deleted' flash message
    And I should see following grid:
      | SKU      | Item                                      | Qty Update All |
      | PROD_A_2 | ConfigurableProductA BooleanAttribute: No | 2 item         |
    And I should not see "BooleanAttribute: Yes"
