@fixture-ShippingRule.yml
Feature: Applying shipping rules
  In order to decrease shipping cost for buyers
  As administrator
  I need to be able change shipping methods rules and orders

  Scenario: "SHIPPING  2A" > SHIPPING RULE #1 BASED ON COUNTRY ONLY. PRIORITY - CRITICAL
    Given I login as AmandaRCole@example.org buyer
    And there is EUR currency in the system configuration
    When Buyer is on Checkout step on Shopping List 1
    Then Shipping Type "Flat Rate: €3.00" is shown for Buyer selection
    And the order total is recalculated to "€13.00"

  Scenario: "SHIPPING 2B" > EDIT AND DISABLE SHIPPING RULE #1 BASED ON COUNTRY ONLY. PRIORITY - MAJOR
    Given Admin User edited "Shipping Rule 1" with next data:
      | Enabled  | false   |
    When Buyer is again on Shipping Method Checkout step on "Shopping List 1"
    Then There is no shipping method available for this order

  Scenario: "SHIPPING 2C" > DIFFERENT CURRENCIES FOR SHIPPING RULE #1 AND ORDER. PRIORITY - MAJOR
    Given Admin User edited "Shipping Rule 1" with next data:
      | Enabled  | true    |
      | Currency | USD     |
      | Country  | Germany |
    When Buyer is again on Shipping Method Checkout step on "Shopping List 1"
    Then There is no shipping method available for this order


  Scenario: "SHIPPING 2E" > LIST OF COUNTRIES FOR SHIPPING RULE #2 CONTAINS COUNTRY FOR ORDER. PRIORITY - MAJOR
    Given Admin User Created "Shipping Rule 2" with next data:
      | Currency      | EUR       |
      | Enabled       | true      |
      | Country       | Ukraine   |
      | Country2      | Germany   |
      | Congif Enable | true      |
      | Price         | 2.5       |
      | Type          | Per Order |
      | HandlingFee   | 1.5       |
    When Buyer is again on Shipping Method Checkout step on "Shopping List 1"
    Then Shipping Type "Flat Rate: €4.00" is shown for Buyer selection
    And  the order total is recalculated to "€14.00"


  Scenario: "Shipping 2F" > List of ZIP codes for Shipping Rule #3 contains ZIP Code for Order. Priority - Major
    Given Admin User created "Shipping Rule 3" with next data:
      | Enabled       | true              |
      | Country       | Ukraine           |
      | Country2      | Germany           |
      | Currency      | EUR               |
      | Sort Order    | 1                 |
      | ZIP           | 10115,10116,10117 |
      | ZIP2          | 10115,10116,10117 |
      | Congif Enable | true              |
      | Price         | 3                 |
      | Type          | Per Order         |
      | HandlingFee   | 1.5               |
    Given Admin User edited "Shipping Rule 2" with next data:
      | Sort Order    | 2 |
    When Buyer is again on Shipping Method Checkout step on "Shopping List 1"
    Then Shipping Type "Flat Rate: €4.50" is shown for Buyer selection
    And  the order total is recalculated to "€14.50"

  Scenario: "Shipping 2G" > LIST OF ZIP CODES FOR SHIPPING RULE #3 DOES NOT CONTAIN ZIP CODE FOR ORDER. PRIORITY - MAJOR
    Given Admin User edited "Shipping Rule 3" with next data:
      | ZIP           | 10114,10116,10117 |
      | ZIP1          | 10114,10116,10117 |
    Given Admin User edited "Shipping Rule 2" with next data:
      | Enabled    | false |
    When Buyer is again on Shipping Method Checkout step on "Shopping List 1"
    Then There is no shipping method available for this order

  Scenario: "Shipping 2H" > CHECK CORRECTNESS OF FLAT RATE TYPE = PER ITEM CALCULATION. PRIORITY - CRITICAL
    Given Admin User created "Shipping Rule 4" with next data:
      | Enabled       | true              |
      | Country       | Ukraine           |
      | Country2      | Germany           |
      | Currency      | EUR               |
      | Sort Order    | 0                 |
      | ZIP           | 10115,10116,10117 |
      | ZIP2          | 10115,10116,10117 |
      | Congif Enable | true              |
      | Type          | Per Item          |
      | Price         | 1.5               |
      | HandlingFee   | 1.5               |
    When Buyer is again on Shipping Method Checkout step on "Shopping List 1"
    Then Shipping Type "Flat Rate: €9.00" is shown for Buyer selection
    And  the order total is recalculated to "€19.00"

  Scenario: "Shipping 2I" > SHIPPING RULE #5 IS APPLICABLE FOR ALL COUNTRIES. PRIORITY - MAJOR
    Given Admin User created "Shipping Rule 5" with next data:
      | Enabled       | true      |
      | Currency      | EUR       |
      | Sort Order    | -1        |
      | Congif Enable | true      |
      | Type          | Per Order |
      | Price         | 5         |
      | HandlingFee   | 1.5       |
    When Buyer is again on Shipping Method Checkout step on "Shopping List 1"
    And Buyer created order with next shipping address:
      | Country       | Ukraine    |
      | City          | Kyiv       |
      | ZIP           | 01000      |
      | Street        | Hreschatik |
    Then Shipping Type "Flat Rate: €6.50" is shown for Buyer selection
    And  the order total is recalculated to "€16.50"
