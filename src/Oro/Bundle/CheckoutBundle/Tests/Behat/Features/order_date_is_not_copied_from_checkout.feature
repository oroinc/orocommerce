@ticket-BB-26673
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:CheckoutWithPastDate.yml

Feature: Order Date Is Not Copied From Checkout
  In order to have accurate order creation timestamps
  As a buyer
  I want the order date to reflect when the order was placed, not when the checkout was started

  Scenario: Verify checkout has past date in Open Orders grid
    Given AmandaRCole@example.org customer user has Buyer role
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open Order History page on the store frontend
    Then I should see following "Open Orders Grid" grid:
      | Started From        |
      | Past Checkout List  |
    # The "Started At" column should show the checkout's createdAt date (1/15/2024, 10:00 AM)
    And I should see "1/15/2024"

  Scenario: Complete checkout and verify order date is current date, not checkout date
    Given I click "Check Out" on row "Past Checkout List" in grid "OpenOrdersGrid"
    When I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

    When I follow "click here to review"
    Then I should be on Order Frontend View page
    # The Order Date should be today's date, not the checkout's past date (1/15/2024)
    And I should not see "1/15/2024"
    And I should not see "2024"
    And I should see today's date in the "Order Date" element
