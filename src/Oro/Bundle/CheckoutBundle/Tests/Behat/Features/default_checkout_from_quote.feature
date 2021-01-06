@ticket-BB-7164
@automatically-ticket-tagged
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroCheckoutBundle:InventoryLevel.yml
@community-edition-only

Feature: Default Checkout From Quote
  In order to create order on front store
  As a buyer
  I want to start and complete checkout from quote

  Scenario: Enable Single Page Checkout Workflow
    Given There is USD currency in the system configuration
    And I login as administrator

  Scenario: Set internal status "Sent to Customer" for Quote with PO number "PO1"
    Given go to Sales/Quotes
    And click view PO1 in grid
    When I click "Send to Customer"
    And click "Send"
    Then I should see "Quote #1 successfully sent to customer" flash message

  Scenario: Create order from Quote PO1 and verify quantity
    Given AmandaRCole@example.org customer user has Buyer role
    And I signed in as AmandaRCole@example.org on the store frontend
    And I follow "Account"
    And I click "Quotes"
    And I click view PO1 in grid
    When I click "Accept and Submit to Order"
    And I click "Submit"
    Then Checkout "Order Summary Products Grid" should contain products:
      | 400-Watt Bulb Work Light | 5 | items |
    And I should see Checkout Totals with data:
      | Subtotal | $25.00 |
      | Shipping | $3.00  |

    When I open Order History page on the store frontend
    Then I should see following grid:
      | Step                | Started From | Items | Subtotal |
      | Billing Information | Quote #1     | 1     | $25.00   |
    And I click "Check Out" on row "Quote #1" in grid "OpenOrdersGrid"

    When I click "Edit items"
    And I type "10" in "First Product Quantity on Quote"
    And I click "Submit"
    Then Checkout "Order Summary Products Grid" should contain products:
      | 400-Watt Bulb Work Light | 10 | items |
    And I should see Checkout Totals with data:
      | Subtotal | $50.00 |
      | Shipping | $3.00  |

    When I open Order History page on the store frontend
    Then I should see following grid:
      | Step                | Started From | Items | Subtotal |
      | Billing Information | Quote #1     | 1     | $50.00   |
    And I click "Check Out" on row "Quote #1" in grid "OpenOrdersGrid"

  Scenario: Process checkout
    Given I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

    When I follow "click here to review"
    Then I should be on Order Frontend View page

  Scenario: Checking Order History grid with Open Orders
    Given I open Order History page on the store frontend
    And there is no records in "OpenOrdersGrid"
    And I click "View" on row "1" in grid "PastOrdersGrid"
    Then I should be on Order Frontend View page
