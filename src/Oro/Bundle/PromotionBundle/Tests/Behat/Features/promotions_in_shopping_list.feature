@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-promotions.yml
@fixture-shopping_list_with_configurable_product.yml
Feature: Promotions in Shopping List
  In order to find out applied discounts in shopping list
  As a site user
  I need to have ability to see applied discounts at shopping list on front-end

  Scenario: Logged in as buyer and manager on different window sessions
    Given sessions active:
      | Admin  | first_session  |
      | Buyer  | second_session |

  Scenario: Check line item and subtotal discount in Shopping List with simple products
    Given I operate as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    Then I see next line item discounts for shopping list "List 1":
      | SKU              | Discount |
      | SKU2             | $5.00    |
      | SKU1             |          |
    And I see "$12.50" subtotal discount for shopping list "List 1"

  Scenario: Prepare configurable product
    Given I proceed as the Admin
    And I login as administrator
    And I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | Size   |
      | Type       | Select |
    And I click "Continue"
    And set Options with:
      | Label |
      | Small |
      | Large |
    And I save form
    Then I should see "Attribute was successfully saved" flash message

    # Update schema
    And I go to Products / Product Attributes
    And I confirm schema update

    # Update attribute family
    And I go to Products / Product Families
    And I click Edit product_attribute_family_code in grid
    And set Attribute Groups with:
      | Label         | Visible | Attributes |
      | system  group | true    | [SKU, Name, Is Featured, New Arrival, Brand, Description, Short Description, Images, Inventory Status, Meta title, Meta description, Meta keywords, Product prices] |
      | Size group    | true    | [Size]     |
    And I save form
    Then I should see "Successfully updated" flash message

    # Prepare configurable products
    And I go to Products / Products
    And I click Edit SKU2 in grid
    And I fill "ProductForm" with:
      | Size | Large |
    And I save form
    Then I should see "Product has been saved" flash message

    # Save configurable product with simple products selected
    And I go to Products / Products
    And I click Edit SKU_CONFIGURABLE in grid
    And I should see "No records found"
    And I fill "ProductForm" with:
      | Configurable Attributes | [Size] |
    And I save form
    Then I should see "Product has been saved" flash message

  Scenario: Check line item and subtotal discount in Shopping List with configurable product
    Given I operate as the Buyer
    When I open page with shopping list List 2 with configurable product
    Then I see next line item discounts for shopping list "List 2 with configurable product":
      | SKU              | Discount |
      | SKU_CONFIGURABLE | $5.00    |
    And I see "$7.50" subtotal discount for shopping list "List 2 with configurable product"
