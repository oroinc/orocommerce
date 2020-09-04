@regression
@ticket-BB-15845
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroCheckoutBundle:CheckoutCustomerFixture.yml
@fixture-OroCheckoutBundle:CheckoutProductFixture.yml
@fixture-OroCheckoutBundle:CheckoutShoppingListFixture.yml
@fixture-OroCheckoutBundle:CheckoutQuoteFixture.yml
@fixture-OroCheckoutBundle:CheckoutQuoteFixtureEur.yml
@fixture-OroCheckoutBundle:InventoryLevel.yml
@fixture-OroCheckoutBundle:ShippingEur.yml
@fixture-OroCheckoutBundle:PaymentEur.yml

Feature: Default Checkout From Quote With Multi Currency
  In order to create order on front store
  As a Buyer
  I want to be able to start form Quote and complete "Default" checkout

  Scenario: Enable required currencies
    Given I login as administrator
    And I go to System/Configuration
    And I follow "Commerce/Catalog/Pricing" on configuration sidebar
    When fill "Pricing Form" with:
      | Enabled Currencies System | false                     |
      | Enabled Currencies        | [US Dollar ($), Euro (€)] |
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Set internal status "Sent to Customer" for Quote with PO number "PO1"
    Given go to Sales/Quotes
    And click view PO1 in grid
    When I click "Send to Customer"
    And click "Send"
    Then I should see "Quote #1 successfully sent to customer" flash message

  Scenario: Create order from Quote PO1 and verify quantity
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I follow "Account"
    And I click "Quotes"
    And I click view PO1 in grid
    When I click "Accept and Submit to Order"
    And I type "5" in "First Product Quantity on Quote"
    And I click on empty space
    And I click "Submit"
    Then Checkout "Order Summary Products Grid" should contain products:
      | 400-Watt Bulb Work Light | 5 | items |
    And I should see Checkout Totals with data:
      | Subtotal | $25.00 |
      | Shipping | $3.00  |

  Scenario: Trying to change currency to EUR
    Given I click "Currency Switcher"
    When I click "Euro"
    Then I should see "This checkout can be completed only in US Dollar. Please return to the original page to switch the currency." flash message
    And I open Order History page on the store frontend
    And I should see following grid:
      | Step                | Started From | Items | Subtotal |
      | Billing Information | Quote #1     | 1     | $25.00   |

  Scenario: Create order from Quote PO1 and verify quantity in EUR
    Given I click "Currency Switcher"
    And I click "Euro"
    And I follow "Account"
    And I click "Quotes"
    And I click view PO1 in grid
    When I click "Accept and Submit to Order"
    And I type "4" in "First Product Quantity on Quote"
    And I click on empty space
    And I click "Submit"
    Then Checkout "Order Summary Products Grid" should contain products:
      | 400-Watt Bulb Work Light | 4 | items |
    And I should see Checkout Totals with data:
      | Subtotal | €18.00 |
      | Shipping | €2.80  |

  Scenario: Process checkout
    Given I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    When I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    And I follow "click here to review"
    And I should be on Order Frontend View page

  Scenario: Checking Order History grid with Open Orders
    Given I open Order History page on the store frontend
    Then I should see following "OpenOrdersGrid" grid:
      | Step                | Started From | Items | Currency | Subtotal |
      | Billing Information | Quote #1     | 1     | USD      | €25.00   |
    Then I should see following "Past Orders Grid" grid:
      | Shipping Address                                      | Total  |
      | Primary address ORO Fifth avenue 10115 Berlin Germany | €20.80 |
    And I click "View" on row "1" in grid "PastOrdersGrid"
