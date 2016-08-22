@fixture-ShippingRule.yml
  @ProductUnit11.yml
Feature: Applying shipping rules applying
  In order to decrease shipping cost
  As administrator
  I need to be able change shipping methods rules and orders

#  Background:
#    And Buyer User with Edit Shipping Address permissions
#    And Shopping Rule Flat Rate Shipping Cost = 1.5
#    And Shopping Rule Flat Rate Type = per Order by default
#    And Shopping Rule Flat Rate Handling Fee = 1.5

  Scenario Outline: "SHIPPING” > SHIPPING RULE #1 BASED ON COUNTRY ONLY. PRIORITY - CRITICAL
    Given there is EUR currency in the system configuration exist
    And I login as AmandaRCole@example.org
#    Given Admin User created Flat Rate Shipping Rule #1 with next data:
#      | Country       | Germany |
#      | Rule Currency | EUR     |
#      | Rule Status   | Enabled |
#    And Buyer created order with:
#      | Shipping Address | Berlin, Germany,10115 |
#      | Currency         | EUR                   |
#      | Price            | <orderPrice>          |
    When Buyer is on Shipping Method Checkout step on Shopping List 1
    Then Shipping Type "<shippingRate>" is shown for Buyer selection
    And One the next Checkout step order subtotal is recalculated to <orderSubTotal>
    Examples:
      | shippingRate       | orderPrice | orderSubTotal |
      | Flat Rate 3.00 EUR | 10         | 13            |
      | Flat Rate 5.00 EUR | 23         | 28            |
      | Flat Rate 7.00 EUR | 4          | 11            |

#  Scenario: "SHIPPING" > EDIT AND DISABLE SHIPPING RULE #1 BASED ON COUNTRY ONLY. PRIORITY - MAJOR
#    Given Admin User edited "Flat Rate Shipping Rule #1" with next data:
#      | Country       | Germany  |
#      | Rule Currency | EUR      |
#      | Rule Status   | Disabled |
#    And Buyer created order with:
#      | Shipping Address | Berlin,Germany,10115 |
#      | Currency         | EUR                  |
#    When Buyer is on Shipping Method Checkout step on Shopping List 1
#    Then There is no shipping method available for this order
#
#  Scenario: "SHIPPING" > DIFFERENT CURRENCIES FOR SHIPPING RULE #1 AND ORDER. PRIORITY - MAJOR
#    Given Admin User edited "Flat Rate Shipping Rule #1" with next data:
#      | Country       | Germany |
#      | Rule Currency | USD     |
#      | Rule Status   | Enabled |
#    And Buyer created order with:
#      | Shipping Address | Berlin,Germany,10115 |
#      | Currency         | EUR                  |
#    When Buyer is on Shipping Method Checkout step on Shopping List 1
#    Then There is no shipping method available for this order
