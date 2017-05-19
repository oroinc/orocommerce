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
      | Type                 | DPD                  |
      | Name                 | DPD                  |
      | Label                | DPD                  |
      | Live Mode            | false                |
      | Cloud User Id        | 2783                 |
      | Cloud User Token     | 39653536665162576759 |
      | Shipping Services    | DPD Classic          |
      | Unit of weight       | kilogram             |
      | Rate Policy          | Flat Rate            |
      | Flat Rate Price      | 10                   |
      | Label Size           | PDF_A6               |
      | Label Start Position | Upper Right          |
      | Status               | Active               |
      | Default owner        | John Doe             |
    And save and close form
    Then I should see "Integration saved" flash message
    And I go to System/ Shipping Rules
    And click "Create Shipping Rule"
    And fill "DPD Shipment Rule Form" with:
      | Enable     | true  |
      | Name       | DPD   |
      | Sort Order | 1     |
      | Currency   | $     |
      | Method     | [DPD] |
    And fill "DPD Classic Form" with:
      | Enable       | true |
      | Handling fee | 10   |
    When save and close form
    Then should see "Shippment rule has been saved" flash message
#    Uncomment when test for "Request Shipping labels" will be written
#    And I go to System/ Configuration
#    And I click "Shipping Origin" on configuration sidebar
#    And fill form with:
#      | Use default      | false                       |
#      | Country          | Portugal                    |
#      | Region/State     | Faro                        |
#      | Zip/Postal Code  | 8000-397                    |
#      | City             | Faro                        |
#      | Street Address 1 | Rua Mouzinho de Albuquerque |
#      | Street Address 2 | 1A                          |
#    And I save form
#    Then should see "Configuration saved" flash message
    And I go to System/ Payment Rules
    And click "Create Payment Rule"
    And fill "Payment Rule Form" with:
      | Enable     | true           |
      | Name       | Payment Terms  |
      | Sort Order | 1              |
      | Currency   | $              |
      | Method     | [Payment Term] |
    When save and close form
    Then should see "Payment rule has been saved" flash message
    And click logout in user menu

  Scenario: Check out with DPD integration
    Given Currency is set to USD
    And I enable the existing warehouses
    And AmandaRCole@example.org customer user has Buyer role
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I press "Create Order"
    And I select "VOTUM GmbH Ohlauer Str. 43, 10999 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "VOTUM GmbH Ohlauer Str. 43, 10999 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    When I should see "DPD Classic: $20.00"
    When on the "Shipping Method" checkout step I press Continue
    When on the "Payment" checkout step I press Continue
    And click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    And click "Sign Out"
