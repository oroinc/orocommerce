@ticket-BB-11006
@automatically-ticket-tagged
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroSaleBundle:QuoteEditOrderOnReview.yml
@fixture-OroWarehouseBundle:Checkout.yml
@skip
Feature: Edit Order which has been created from Quote and proceed with checkout

  Scenario: Request for Quote
    Given I login as AmandaRCole@example.org buyer
    And I request a quote from shopping list "List 1" with data:
      | PO Number | PONUMBER1 |

  Scenario: Create Quote
    And I login as administrator
    And I create a quote from RFQ with PO Number "PONUMBER1"
    And I click "Send to Customer"
    And I click "Send"

  Scenario: Start checkout and edit order on Order Review step
    Given I enable the existing warehouses
    Given I login as AmandaRCole@example.org buyer
    Then I click "Quotes"
    Then I click view PONUMBER1 in grid
    And I press "Accept and Submit to Order"
    And I press "Submit"
    Then Buyer is on enter billing information checkout step
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I click "Edit Order"
    Then I type "6" in "First Product Quantity on Quote"
    And press "Submit"
    And I press "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
