@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-Checkout.yml
@fixture-OroWarehouseBundle:Checkout.yml
Feature: DPD shipping integration
#  DPD shipping implementation in Oro commerce features
#  Order view (admin)
#  Additional "Ship order" action (send order to DPD service).
#  Action display preconditions (user can update order, and shipping method is DPD).
#  Parcel pick-up date validation using API call.
#  Send order to handler.
#  Create a shipping "transaction" to store relevant response data (parcel information).
#  Update order view with label download link (order attachment).
#  Additional "DPD shipping" action (redirects user to DPD menu - visible for shipped orders).
#  DPD Oro Integration (admin)
#  API credentials form.
#  Shipping rules (admin)
#  DPD shipping configuration form.
#  DPD menu entry (admin)
#  List all orders filtered by "DPD" shipping method.
#  Allow bulk "setOrder" for selected orders.
#  Display action to create a "shipping list" for DPD driver.
#  Display order tracking information
#  Order history (customer)
#  Display tracking information.

  Scenario: Create DPD integration (Flat Rate)
    Given I login as administrator
    And go to System/ Integrations/ Manage Integrations
    And click "Create Integration"
    When I fill "Integration Form" with:
      |Type                |DPD                 |
      |Name                |DPD                 |
      |Label               |DPD                 |
      |Live Mode           |False               |
      |Cloud User Id       |2783                |
      |Cloud User Token    |39653536665162576759|
      |Shipping Services   |DPD Classic         |
      |Unit of weight      |kilogram            |
      |Rate Policy         |Flat Rate           |
      |Flat Rate Price     |10                  |
      |Label Size          |PDF_A6              |
      |Label Start Position|Upper Right         |
      |Status              |Active              |
      |Default owner       |John Doe            |
    And save and close form
    Then I should see "Integration saved" flash message
    And I go to System/ Shipping Rules
    And click "Create Shipping Rule"
    And fill "Shippment Rule Form" with:
      |Enable    |true |
      |Name      |DPD  |
      |Sort Order|1    |
      |Currency  |$    |
      |Method    |[DPD]|
    When save and close form
    Then should see "Shippment rule has been saved" flash message
    And I go to System/ Configuration
    And click "Commerce"
    And click "Shipping"
    And click "Shipping Origin"
    And fill form with:
    |Use default|false|
    |Country    |Denmark|
    And click logout in user menu
    And I wait for action