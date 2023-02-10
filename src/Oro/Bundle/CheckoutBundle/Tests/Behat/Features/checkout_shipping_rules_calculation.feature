@ticket-BB-22104
@fixture-OroCheckoutBundle:CheckoutPaymentTerm.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroCheckoutBundle:CheckoutCustomerFixture.yml
@fixture-OroCheckoutBundle:CheckoutShippingRulesCalculation.yml

Feature: Checkout shipping rules calculation

  Scenario: Prepare the sessions
    Given sessions active:
      | Admin  | first_session  |
      | Buyer  | second_session |

  Scenario: Create a product attribute of the select type called “shipping_category”
    Given I proceed as the Admin
    And I login as administrator
    And I go to Products/Product Attributes
    And I click "Create Attribute"
    When I fill form with:
      | Field Name | shipping_category |
      | Type       | Select            |
    And I click "Continue"
    And I set Options with:
      | Label |
      | a     |
      | b     |
      | c     |
      | d     |
    And I save form
    Then I should see "Attribute was successfully saved" flash message
    Given I go to Products/Product Attributes
    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Add “shipping_category” product attribute to the default product family
    Given I go to Products/Product Families
    When I click Edit default_family in grid
    And set Attribute Groups with:
      | Label             | Visible | Attributes          |
      | Shipping category | true    | [shipping_category] |
    And I save form
    Then I should see "Successfully updated" flash message

  Scenario: Set shipping category to the products
    Given I go to Products/ Products
    When I click "Edit" on row "SKU2" in grid
    And I fill "Product Form" with:
      | Shipping_category | d |
    And I save and close form
    Then I should see "Product has been saved" flash message
    And I go to Products/ Products
    When I click "Edit" on row "SKU3" in grid
    And I fill "Product Form" with:
      | Shipping_category | b |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check out shopping list
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I click "Create Order"
    And on the "Billing" checkout step I press Continue
    And on the "Shipping" checkout step I press Continue
    And I should see "Flat Rate: $200"
    And on the "Shipping Method" checkout step I press Continue
    And I click "Continue"
    Then I should see Checkout Totals with data:
      | Subtotal | $6.00   |
      | Shipping | $200.00 |
    When I wait "Submit Order" button
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
