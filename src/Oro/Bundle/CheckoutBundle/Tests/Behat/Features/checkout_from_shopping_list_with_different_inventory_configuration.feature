@ticket-BB-4322
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
Feature: Checkout from Shopping List with different Inventory configuration
  In order to create order from Shopping List on front store
  As a buyer
  I want to start checkout from Shopping List view page and view validation messages according to Inventory configuration

  Scenario: Changing Inventory AllowedStatuses for Order
    Given I login as AmandaRCole@example.org the "Buyer" at "first_session" session
    And I login as administrator and use in "second_session" as "Admin"
    Given I go to System/Configuration
    And I follow "Commerce/Inventory/Allowed Statuses" on configuration sidebar
    And uncheck "Use default" for "Can Be Added to Orders" field
    And I fill form with:
      | Can Be Added to Orders | Out of Stock |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Create order from Shopping List 1 with Visibility validation error with RFP enabled
    Given I operate as the Buyer
    When I open page with shopping list List 1
    And I click "Create Order"
    Then I should see "No products can be added to this order. Please create an RFQ to request price." flash message

  Scenario: Changing Inventory AllowedStatuses for RFQ
    Given I operate as the Admin
    And I go to System/Configuration
    And I follow "Commerce/Inventory/Allowed Statuses" on configuration sidebar
    And uncheck "Use default" for "Can Be Added to RFQs" field
    And I fill form with:
      | Can Be Added to RFQs | Out of Stock |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Create order from Shopping List 1 with Visibility validation error with RFQ enabled
    Given I operate as the Buyer
    When I open page with shopping list List 1
    And I click "Create Order"
    Then I should see "No products can be added to this order." flash message
    And I should not see "Please create an RFQ to request price."
