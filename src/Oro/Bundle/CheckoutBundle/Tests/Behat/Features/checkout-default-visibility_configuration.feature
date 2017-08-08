@ticket-BB-4322
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
Feature: Checkout from Shopping List with different Inventory configuration
  In order to to create order from Shopping List on front store
  As a buyer
  I want to start checkout from Shopping List view page and view validation messages according to Inventory configuration

  Scenario: Changing Inventory AllowedStatuses for Order
    Given I login as administrator
    And I go to System/Configuration
    And I click "Commerce" on configuration sidebar
    And I click "Inventory" on configuration sidebar
    And I click "Allowed Statuses" on configuration sidebar
    And uncheck "Use default" for "Can Be Added to Orders" field
    And I fill form with:
      | Can Be Added to Orders | Out of Stock |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Create order from Shopping List 1 with Visibility validation error with RFP enabled
    Given AmandaRCole@example.org customer user has Buyer role
    And I signed in as AmandaRCole@example.org on the store frontend

    When I open page with shopping list List 1
    And I press "Create Order"
    Then I should see "No products can be added to this order. Please create an RFQ to request price." flash message

  Scenario: Changing Inventory AllowedStatuses for RFQ
    Given I login as administrator
    And I go to System/Configuration
    And I click "Commerce" on configuration sidebar
    And I click "Inventory" on configuration sidebar
    And I click "Allowed Statuses" on configuration sidebar
    And uncheck "Use default" for "Can Be Added to RFQs" field
    And I fill form with:
      | Can Be Added to RFQs | Out of Stock |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Create order from Shopping List 1 with Visibility validation error with RFQ enabled
    Given AmandaRCole@example.org customer user has Buyer role
    And I signed in as AmandaRCole@example.org on the store frontend

    When I open page with shopping list List 1
    And I press "Create Order"
    Then I should see "No products can be added to this order." flash message
    And I should not see "Please create an RFQ to request price."
