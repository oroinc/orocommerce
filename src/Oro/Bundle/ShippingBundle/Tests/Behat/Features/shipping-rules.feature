@skip
# TODO: unskip when BB-8036 will be resolved
@fixture-ShippingMethodsConfigsRule.yml
Feature: Applying shipping rules
  In order to decrease shipping cost for buyers
  As administrator
  I need to be able change shipping methods rules and orders

  Scenario: "SHIPPING 2A" > SHIPPING RULE #1 BASED ON COUNTRY ONLY. PRIORITY - CRITICAL
    Given I login as AmandaRCole@example.org buyer
    And There is EUR currency in the system configuration
    When Buyer is on Checkout step on Shopping List 1
    Then Shipping Type "Flat Rate: €3.00" is shown for Buyer selection
    And the order total is recalculated to "€13.00"

  Scenario: "SHIPPING 2B" > EDIT AND DISABLE SHIPPING RULE #1 BASED ON COUNTRY ONLY. PRIORITY - MAJOR
    Given Admin User edited "Shipping Rule First" with next data:
      | Enabled  | false   |
    When Buyer is on view shopping list "Shopping List 1" page and clicks create order button
    Then Flash message appears that there is no shipping methods available

  Scenario: "SHIPPING 2C" > DIFFERENT CURRENCIES FOR SHIPPING RULE #1 AND ORDER. PRIORITY - MAJOR
    Given Currency is set to USD
    And Admin User edited "Shipping Rule First" with next data:
      | Enabled  | true    |
      | Currency | USD     |
      | Country  | Germany |
    # specific for community edition
    And Currency is set to EUR
    When Buyer is on view shopping list "Shopping List 1" page and clicks create order button
    Then Flash message appears that there is no shipping methods available

  Scenario: "SHIPPING 2D" > DIFFERENT COUNTRIES FOR SHIPPING RULE #1 AND ORDER. PRIORITY - MAJOR
    Given Currency is set to EUR
    And Admin User edited "Shipping Rule First" with next data:
      | Enabled  | true    |
      | Currency | EUR     |
      | Country  | Ukraine |
    When Buyer is again on Shipping Method Checkout step on "Shopping List 1"
    Then There is no shipping method available for this order

  Scenario: "SHIPPING 2E" > LIST OF COUNTRIES FOR SHIPPING RULE #2 CONTAINS COUNTRY FOR ORDER. PRIORITY - MAJOR
    Given Admin User created "Shipping Rule Second" with next data:
      | Enabled       | true      |
      | Currency      | EUR       |
      | Country1      | Ukraine   |
      | Country2      | Germany   |
      | Sort Order    | 1         |
      | Price         | 2.5       |
      | Type          | Per Order |
      | HandlingFee   | 1.5       |
    When Buyer is again on Shipping Method Checkout step on "Shopping List 1"
    Then Shipping Type "Flat Rate: €4.00" is shown for Buyer selection
    And  the order total is recalculated to "€14.00"

  Scenario: "Shipping 2F" > LIST OF ZIP CODES FOR SHIPPING RULE #3 CONTAINS ZIP CODE FOR ORDER. PRIORITY - MAJOR
    Given Admin User created "Shipping Rule Third" with next data:
      | Enabled       | true              |
      | Country1      | Ukraine           |
      | Country2      | Germany           |
      | Currency      | EUR               |
      | Sort Order    | 1                 |
      | ZIP1          | 10115,10116,10117 |
      | ZIP2          | 10115,10116,10117 |
      | Price         | 3                 |
      | Type          | Per Order         |
      | HandlingFee   | 1.5               |
    Given Admin User edited "Shipping Rule Second" with next data:
      | Sort Order    | 2 |
    When Buyer is again on Shipping Method Checkout step on "Shopping List 1"
    Then Shipping Type "Flat Rate: €4.50" is shown for Buyer selection
    And  the order total is recalculated to "€14.50"

  Scenario: "Shipping 2G" > LIST OF ZIP CODES FOR SHIPPING RULE #3 DOES NOT CONTAIN ZIP CODE FOR ORDER. PRIORITY - MAJOR
    Given Admin User edited "Shipping Rule Third" with next data:
      | ZIP           | 10114,10116,10117 |
      | ZIP1          | 10114,10116,10117 |
    And Admin User edited "Shipping Rule Second" with next data:
      | Enabled    | false |
    When Buyer is again on Shipping Method Checkout step on "Shopping List 1"
    Then There is no shipping method available for this order

  Scenario: "Shipping 2H" > CHECK CORRECTNESS OF FLAT RATE TYPE = PER ITEM CALCULATION. PRIORITY - CRITICAL
    Given Admin User created "Shipping Rule Fourth" with next data:
      | Enabled       | true              |
      | Country1      | Ukraine           |
      | Country2      | Germany           |
      | Currency      | EUR               |
      | Sort Order    | 0                 |
      | ZIP1          | 10115,10116,10117 |
      | ZIP2          | 10115,10116,10117 |
      | Type          | Per Item          |
      | Price         | 1.5               |
      | HandlingFee   | 1.5               |
    When Buyer is again on Shipping Method Checkout step on "Shopping List 1"
    Then Shipping Type "Flat Rate: €9.00" is shown for Buyer selection
    And  the order total is recalculated to "€19.00"

  Scenario: "Shipping 2I" > SHIPPING RULE #5 IS APPLICABLE FOR ALL COUNTRIES. PRIORITY - MAJOR
    Given Admin User created "Shipping Rule Fifth" with next data:
      | Enabled       | true      |
      | Currency      | EUR       |
      | Sort Order    | -1        |
      | Type          | Per Order |
      | Price         | 5         |
      | HandlingFee   | 1.5       |
    When Buyer is again on Shipping Method Checkout step on "Shopping List 1"
    And Buyer created order with next shipping address:
      | Country         | Ukraine              |
      | City            | Kyiv                 |
      | State           | Kyïvs'ka mis'ka rada |
      | Zip/Postal Code | 01000                |
      | Street          | Hreschatik           |
    Then Shipping Type "Flat Rate: €6.50" is shown for Buyer selection
    And  the order total is recalculated to "€16.50"
