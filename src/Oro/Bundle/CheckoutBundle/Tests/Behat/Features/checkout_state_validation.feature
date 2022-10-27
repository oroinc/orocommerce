@ticket-BB-15031
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroCheckoutBundle:InventoryLevel.yml

Feature: Checkout State Validation
  In order to validate checkout information
  As a Customer User
  I want to be warned about valuable changes

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create order from Shopping List 1 and verify quantity
    Given I proceed as the Buyer
    And AmandaRCole@example.org customer user has Buyer role
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I scroll to top
    And I wait line items are initialized
    And I click "Create Order"
    Then Checkout "Order Summary Products Grid" should contain products:
      | 400-Watt Bulb Work Light | 5 | items |
    And I should see Checkout Totals with data:
      | Subtotal | $10.00 |
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue

  Scenario: Change price for product used in checkout
    Given I proceed as the Admin
    And I login as administrator
    And I go to Products/ Products
    And click edit "400-Watt Bulb Work Light" in grid
    When I click "Product Prices"
    And I set Product Price collection element values in 2 row:
      | Price List     | Default price list |
      | Quantity value | 5                  |
      | Quantity Unit  | item               |
      | Value          | 15                 |
    When I save form
    Then I should see "Product has been saved" flash message

  Scenario: After price change buyer is warned about checkout content changes
    Given I proceed as the Buyer
    When I click "Submit Order"
    Then I should see "There was a change to the contents of your order." flash message
    And I should not see "Thank You For Your Purchase!"

  Scenario: Buyer can finish checkout after he was acknowledged about changes
    Given I click "Submit Order"
    Then I should see "Thank You For Your Purchase!"
