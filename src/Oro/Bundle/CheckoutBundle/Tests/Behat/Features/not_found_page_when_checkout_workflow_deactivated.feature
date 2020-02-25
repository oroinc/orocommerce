@ticket-BB-12453
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroCheckoutBundle:InventoryLevel.yml

Feature: Not found page when Checkout workflow deactivated
  In order to create order on front store
  As a buyer
  I want to see Not Found page for started checkouts when workflow is deactivated

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Start checkout from Shopping List 1
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I open page with shopping list List 1
    And I scroll to top
    And I wait line items are initialized
    When I click "Create Order"
    And I follow "Account"
    And I click "Order History"
    Then I should see following grid:
      | Step                | Started From | Items | Subtotal |
      | Billing Information | List 1       | 1     | $10.00   |
    And I click "Check Out" on row "List 1" in grid "OpenOrdersGrid"

  Scenario: Disable checkout workflow
    Given I proceed as the Admin
    And I login as administrator
    And I go to System/Workflows
    And I check "Checkout" in Related Entity filter
    And I filter Exclusive Active Groups as is equal to "b2b_checkout_flow"
    When I click "Deactivate" on row "Checkout" in grid
    And I click "Yes, Deactivate" in modal window
    Then I should see "Workflow deactivated" flash message

  Scenario: Check checkout page
    Given I proceed as the Buyer
    When I reload the page
    Then I should see "404 Not Found"

  Scenario: Check checkout page
    Given I follow "Account"
    When I click "Order History"
    Then there are no records in "OpenOrdersGrid"
