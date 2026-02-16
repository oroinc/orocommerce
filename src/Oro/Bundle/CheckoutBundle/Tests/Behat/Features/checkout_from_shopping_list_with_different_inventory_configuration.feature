@ticket-BB-4322
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml

Feature: Checkout from Shopping List with different Inventory configuration

  Scenario: Create order from Shopping List 1
    Given I login as AmandaRCole@example.org the "Buyer" at "first_session" session
    And I open page with shopping list List 1
    Then I should not see "Some items in your shopping list need a quick review. Take a moment to update them before continuing."
    And I should not see "This item can't be added to checkout because the inventory status is not supported."
    And I should not see "This item can't be added to RFQ because the inventory status is not supported."
    And I click "Create Order"

  Scenario: Set "Out of Stock" Inventory AllowedStatuses for Order
    Given I login as administrator and use in "second_session" as "Admin"
    And I go to System/Configuration
    And I follow "Commerce/Inventory/Allowed Statuses" on configuration sidebar
    And I uncheck "Use default" for "Can Be Added to Orders" field
    And I fill form with:
      | Can Be Added to Orders | Out of Stock |
    And I click "Save settings"

  Scenario: Enable Enforce separate shopping list validations for checkout and RFQ Feature
    Given I go to System/Configuration
    And I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And I fill "Shopping List Configuration Form" with:
      | Enable Enforce separate shopping list validations for checkout and RFQ Use default | false |
      | Enable Enforce separate shopping list validations for checkout and RFQ             | true  |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check that submit button is disabled and validation message "Can Be Added to Orders" is shown on checkout page
    Given I operate as the Buyer
    And I reload the page
    Then I should see "Checkout Continue" button disabled
    And I should see "No products can be added to this order. Please create an RFQ to request price." flash message

  Scenario: Check the validation on shopping list page for "Can Be Added to Orders"
    Given I open page with shopping list List 1
    Then I should see "Some items in your shopping list need a quick review. Take a moment to update them before continuing."
    And I should see notification "This item can't be added to checkout because the inventory status is not supported." for "SKU123" line item "ShoppingListLineItem"
    When I click "Create Order"
    Then I should see next rows in "Frontend Customer User Shopping List Invalid Line Items Table" table without headers
      | 400-Watt Bulb Work Light SKU123 5 items $2.00 $10.00                                |
      | This item can't be added to checkout because the inventory status is not supported. |
    And I click "Close"

  Scenario: Set "In Stock" Inventory AllowedStatuses for Order
    Given I operate as the Admin
    And I go to System/Configuration
    And I follow "Commerce/Inventory/Allowed Statuses" on configuration sidebar
    And I fill form with:
      | Can Be Added to Orders | In Stock |
    And I click "Save settings"

  Scenario: Create order from Shopping List 1
    Given I operate as the Buyer
    And I reload the page
    Then I should not see "Some items in your shopping list need a quick review. Take a moment to update them before continuing."
    And I should not see "This item can't be added to checkout because the inventory status is not supported."
    And I click "Create Order"

  Scenario: Set "Out of Stock" Inventory AllowedStatuses for RFQ
    Given I operate as the Admin
    And I uncheck "Use default" for "Can Be Added to RFQs" field
    And I fill form with:
      | Can Be Added to RFQs | Out of Stock |
    And I click "Save settings"

  Scenario: Check that submit button is disabled and validation message "Can Be Added to RFQs" is shown on checkout page
    Given I operate as the Buyer
    And I reload the page
    Then I should see "Checkout Continue" button enabled
    And I should not see "No products can be added to this order. Please create an RFQ to request price."

  Scenario: Check the validation on shopping list page for "Can Be Added to RFQs"
    Given I open page with shopping list List 1
    Then I should see "Some items in your shopping list need a quick review. Take a moment to update them before continuing."
    And I should see notification "This item can't be added to RFQ because the inventory status is not supported." for "SKU123" line item "ShoppingListLineItem"
    When I click "Request Quote"
    Then I should see next rows in "Frontend Customer User Shopping List Invalid Line Items Table" table without headers
      | 400-Watt Bulb Work Light SKU123 5 items $2.00 $10.00                           |
      | This item can't be added to RFQ because the inventory status is not supported. |
    And I click "Close"
    When I click "Create Order"
    And I click "Order products"
    Then I should see following grid:
      | SKU    | Product                  |
      | SKU123 | 400-Watt Bulb Work Light |
