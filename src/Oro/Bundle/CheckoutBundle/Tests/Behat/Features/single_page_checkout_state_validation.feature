@ticket-BB-15031
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroCheckoutBundle:InventoryLevel.yml

@skip
#  need to clarify with PO
Feature: Single Page Checkout State Validation
  In order to validate checkout information
  As a Customer User
  I want to be warned about valuable changes

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Enable Single Page Checkout Workflow
    Given I proceed as the Admin
    And There is USD currency in the system configuration
    And I login as administrator
    And go to System/Workflows
    When I click "Activate" on row "Single Page Checkout" in grid
    And I click "Activate" in modal window
    Then I should see "Workflow activated" flash message

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

  Scenario: Change price for product used in checkout
    Given I proceed as the Admin
    And I go to Products/ Products
    And click edit "400-Watt Bulb Work Light" in grid
    When I click "Product Prices"
    And I set Product Price collection element values in 2 row:
      | Price List     | pricelist_shipping |
      | Quantity value | 5                  |
      | Quantity Unit  | item               |
      | Value          | 15                 |
    When I save form
    Then I should see "Product has been saved" flash message

  Scenario: After price change buyer is warned about checkout content changes
    Given I proceed as the Buyer
    And I wait "Submit Order" button
    When I click "Submit Order"
    Then I should not see "Thank You For Your Purchase!"
    And I should see "There was a change to the contents of your order." flash message

  Scenario: Buyer can finish checkout after he was acknowledged about changes
    Given I wait "Submit Order" button
    When I click "Submit Order"
    Then I should see "Thank You For Your Purchase!"
