@ticket-BB-9254
@ticket-BB-9255
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroCheckoutBundle:InventoryLevel.yml
Feature: Open Orders Grid
  In order to see Totals and sort by Subtotals
  As a Buyer
  I need to have an ability to sort by "Subtotals" and see correct "Totals" column

  Scenario: Set internal status "Sent to Customer" for Quote with PO number "PO1"
    Given I login as administrator
    And go to Sales/Quotes
    And click view PO1 in grid
    When I click "Send to Customer"
    And click "Send"
    Then I should see "Quote #1 successfully sent to customer" flash message

  Scenario: Prepare Checkouts
    Given There is EUR currency in the system configuration
    And AmandaRCole@example.org customer user has Buyer role
    And I signed in as AmandaRCole@example.org on the store frontend

    When I open page with shopping list List 1
    And I press "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue

    And I click "Account"
    And I click "Quotes"
    And I click view PO1 in grid
    And I press "Accept and Submit to Order"
    And I press "Submit"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue

    And I open Order History page on the store frontend
    And I reset "Completed" filter on grid "OpenOrdersGrid"
    Then I should see following "OpenOrdersGrid" grid:
      | Started From |
      | List 1       |
      | Quote #1     |

  Scenario: Checking Open Orders Total Values and Sorting By "Shipping"
    Given I open Order History page on the store frontend
    When I show column "Subtotal" in "OpenOrdersGrid" frontend grid
    And I show column "Shipping" in "OpenOrdersGrid" frontend grid
    And I show column "Total" in "OpenOrdersGrid" frontend grid
    And I reload the page
    And I sort OpenOrdersGrid by Subtotal

    Then I should see following "OpenOrdersGrid" grid:
      | Subtotal | Shipping | Total  |
      | $10.00   | $3.00   | $13.00 |
      | $25.00   | $3.00   | $28.00 |

    When I sort OpenOrdersGrid by Subtotal again
    Then I should see following "OpenOrdersGrid" grid:
      | Subtotal | Shipping | Total  |
      | $25.00   | $3.00   | $28.00 |
      | $10.00   | $3.00   | $13.00 |
