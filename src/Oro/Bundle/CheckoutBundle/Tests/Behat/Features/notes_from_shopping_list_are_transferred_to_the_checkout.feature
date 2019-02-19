@ticket-BB-10567
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml

Feature: Notes from shopping list are transferred to the checkout

  Scenario: Create different window sessions
    Given sessions active:
      | Admin | first_session  |
      | User  | second_session |

  Scenario: Line item notes and shopping list notes should be visible in checkout
    Given I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I click "View Options for this Shopping List"
    And I click on "Add a Note to This Shopping List"
    And I type "My shopping list notes" in "shopping_list_notes"
    And I click on empty space
    And I should see "Record has been successfully updated" flash message
    And I click "Add a Note to This Item"
    And I fill in "Shopping List Product Note" with "SKU123 Product Note"
    And I click on empty space
    And I should see "Record has been successfully updated" flash message
    And I click "Create Order"
    Then I should see "400-Watt Bulb Work Light Note: SKU123 Product Note"
    And I should see "Notes: My shopping list notes"
    And I click "Continue"
    Then I should see "400-Watt Bulb Work Light Note: SKU123 Product Note"
    And I should see "Notes: My shopping list notes"
    And I click "Continue"
    Then I should see "400-Watt Bulb Work Light Note: SKU123 Product Note"
    And I should see "Notes: My shopping list notes"
    And I click "Continue"
    Then I should see "400-Watt Bulb Work Light Note: SKU123 Product Note"
    And I should see "Notes: My shopping list notes"
    And I click "Continue"
    Then I should see "400-Watt Bulb Work Light Note: SKU123 Product Note"
    And I should not see "Notes: My shopping list notes"
    And "Checkout Order Review Form" must contains values:
      | Notes | My shopping list notes |
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Order view page should show order notes and line item notes on storefront
    Given I click "Orders"
    And click view "1" in grid
    Then I should see "Notes My shopping list notes"
    And I should see that Notes in 1 row is equal to "SKU123 Product Note"

  Scenario: Order view page should show order notes and line item notes in back-office
    Given I proceed as the Admin
    And I login as administrator
    And I go to Sales/Orders
    And click view "1" in grid
    Then I should see "Customer Notes My shopping list notes"
    And I should see that Notes in 1 row is equal to "SKU123 Product Note"
