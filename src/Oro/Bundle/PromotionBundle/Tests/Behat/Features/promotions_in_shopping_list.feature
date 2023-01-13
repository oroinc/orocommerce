@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroPromotionBundle:promotions.yml
@fixture-OroPromotionBundle:shopping_list_with_configurable_product.yml
Feature: Promotions in Shopping List
  In order to find out applied discounts in shopping list
  As a site user
  I need to have ability to see applied discounts at shopping list on front-end

  Scenario: Check line item and subtotal discount in Shopping List with simple products
    Given I login as administrator and use in "first_session" as "Admin"
    And I login as AmandaRCole@example.org the "Buyer" at "second_session" session
    When I open page with shopping list List 1
    Then I see next line item discounts for shopping list "List 1":
      | SKU              | Discount |
      | SKU1             |          |
      | SKU2             | -$5.00   |
    And I see next subtotals for "Shopping List":
      | Subtotal | Amount  |
      | Discount | -$12.50 |

  Scenario: Prepare configurable product
    Given I proceed as the Admin
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
    And I should see "There are no product variants"
    And I fill "ProductForm" with:
      | Configurable Attributes | [Size] |
    And I check SKU2 record in grid
    And I save form
    Then I should see "Product has been saved" flash message

  Scenario: Check line item and subtotal discount in Shopping List with configurable product
    Given I operate as the Buyer
    When I open page with shopping list List 2 with configurable product
    Then I see next line item discounts for shopping list "List 2 with configurable product":
      | SKU  | Discount |
      | SKU2 | -$5.00   |
    And I see next subtotals for "Shopping List":
      | Subtotal | Amount |
      | Discount | -$7.50 |
