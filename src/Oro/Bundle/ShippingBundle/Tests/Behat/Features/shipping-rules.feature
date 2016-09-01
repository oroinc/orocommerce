@fixture-ShippingRule.yml
Feature: Applying shipping rules
  In order to decrease shipping cost
  As administrator
  I need to be able change shipping methods rules and orders

  Scenario: "SHIPPING” > SHIPPING RULE #1 BASED ON COUNTRY ONLY. PRIORITY - CRITICAL
    Given I login as AmandaRCole@example.org
    And there is EUR currency in the system configuration
    When Buyer is on Checkout step on Shopping List 1
    Then Shipping Type FlatRate is shown for Buyer selection
    And  the order total is recalculated to <"€ 13.00">

  Scenario: "SHIPPING" > EDIT AND DISABLE SHIPPING RULE #1 BASED ON COUNTRY ONLY. PRIORITY - MAJOR
    Given Admin User edited "Shipping Rule 1" with next data:
      | Enabled  | false |
      | Currency | EUR |
      | Country  | Germany |
    When Buyer is again on Shipping Method Checkout step on "Shopping List 1"
    Then There is no shipping method available for this order

  Scenario: "SHIPPING" > DIFFERENT CURRENCIES FOR SHIPPING RULE #1 AND ORDER. PRIORITY - MAJOR
    Given Admin User edited "Shipping Rule 1" with next data:
      | Country  | Germany |
      | Currency | USD |
      | Enabled  | true |
    When Buyer is again on Shipping Method Checkout step on "Shopping List 1"
    Then There is no shipping method available for this order

  Scenario: "SHIPPING" > LIST OF COUNTRIES FOR SHIPPING RULE #2 CONTAINS COUNTRY FOR ORDER. PRIORITY - MAJOR
    Given Admin User Created "Shipping Rule 2" with next data
      | Currency      | EUR |
      | Enabled       | true |
      | Country       | Ukraine |
      | Country2      | Germany|
      | Congif Enable | true |
      | Price         | 1.5|
      | Type          | Per Order |
      | HandlingFee   |1.5 |
    When Buyer is again on Shipping Method Checkout step on "Shopping List 1"
    Then Shipping Type FlatRate is shown for Buyer selection
    And  the order total is recalculated to <"€ 13.00">
