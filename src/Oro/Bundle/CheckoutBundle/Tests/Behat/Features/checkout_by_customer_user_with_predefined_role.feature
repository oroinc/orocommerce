@ticket-BB-19943
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroCheckoutBundle:NewCheckoutLineItemsLayoutFixture.yml

Feature: Checkout by customer user with predefined role
  In order to to create order from Shopping List on front store
  As a Buyer
  I want to be able to create order with any customer user role which has sufficient permissions

  Scenario: Edit existing Customer User Role
    Given I login as administrator
    And I go to Customers/Customer User Roles
    When click Edit Administrator in grid
    And I save and close form
    Then I should see "Customer User Role has been saved" flash message

  Scenario: Create Checkout as Customer User with new Customer User Role
    When I signed in as AmandaRCole@example.org on the store frontend
    Then I do the order through completion, and should be on order view page
