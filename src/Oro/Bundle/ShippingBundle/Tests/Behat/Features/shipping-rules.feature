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

#  Scenario: "SHIPPING" > DIFFERENT CURRENCIES FOR SHIPPING RULE #1 AND ORDER. PRIORITY - MAJOR
#    Given Admin User edited "Shipping Rule 1" with next data:
#      | Country  | Germany |
#      | Currency | USD |
#      | Enabled  | true |
#    When Buyer is again on Shipping Method Checkout step on "Shopping List 1"
#    Then There is no shipping method available for this order

  Scenario: "SHIPPING" > LIST OF COUNTRIES FOR SHIPPING RULE #2 CONTAINS COUNTRY FOR ORDER. PRIORITY - MAJOR
    Given Admin User created "Shipping Rule 2" with next data:
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


  Scenario: "Shipping" > List of ZIP codes for Shipping Rule #3 contains ZIP Code for Order. Priority - Major
    Given Admin User created "Shipping Rule 3" with next data:
      | Enabled       | true |
      | Country2      | Germany|
      | Country       | Ukraine |
      | Currency      | EUR |
      | Sort Order    | 1 |
      | ZIP           | 10115,10116,10117|
      | ZIP2          | 10115,10116,10117|
      | Congif Enable | true |
      | Price         | 3 |
      | HandlingFee   |1.5 |
    Given Admin User edited "Shipping Rule 2" with next data:
      | Sort Order    | 2 |
    When Buyer is again on Shipping Method Checkout step on "Shopping List 1"
    Then Shipping Type FlatRate is shown for Buyer selection
    And  the order total is recalculated to <"€ 14.50">

  Scenario: "Shipping" > LIST OF ZIP CODES FOR SHIPPING RULE #3 DOES NOT CONTAIN ZIP CODE FOR ORDER. PRIORITY - MAJOR
    Given Admin User edited "Shipping Rule 3" with next data:
      | Country2      | Germany|
      | Country       | Ukraine |
      | ZIP           | 10114,10116,10117|
      | ZIP2          | 10114,10116,10117|
      | Currency      | EUR |
      | Enabled       | true |
    Given Admin User edited "Shipping Rule 2" with next data:
      | Enabled    | false |
    When Buyer is again on Shipping Method Checkout step on "Shopping List 1"
    Then There is no shipping method available for this order
