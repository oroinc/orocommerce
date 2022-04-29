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
    And I click "Add a note to entire Shopping List"
    And I type "My shopping list notes" in "Shopping List Notes"
    And I click on "Save Shopping List Notes"
    Then I should see "My shopping list notes"
    When I click "Add Shopping List item Note" on row "SKU123" in grid
    And I fill in "Shopping List Product Note" with "SKU123 Product Note"
    And I click "Add"
    Then I should see "Line item note has been successfully updated" flash message
    And I click "Create Order"
    Then I should see following grid:
      | SKU    | Item                                         |
      | SKU123 | 400-Watt Bulb Work Light SKU123 Product Note |
    And I should see "My shopping list notes" in the "Checkout Order Summary Notes" element
    And I click "Continue"
    Then I should see following grid:
      | SKU    | Item                                         |
      | SKU123 | 400-Watt Bulb Work Light SKU123 Product Note |
    And I should see "My shopping list notes" in the "Checkout Order Summary Notes" element
    And I click "Continue"
    Then I should see following grid:
      | SKU    | Item                                         |
      | SKU123 | 400-Watt Bulb Work Light SKU123 Product Note |
    And I should see "My shopping list notes" in the "Checkout Order Summary Notes" element
    And I click "Continue"
    Then I should see following grid:
      | SKU    | Item                                         |
      | SKU123 | 400-Watt Bulb Work Light SKU123 Product Note |
    And I should see "My shopping list notes" in the "Checkout Order Summary Notes" element
    And I click "Continue"
    Then I should see following grid:
      | SKU    | Item                                         |
      | SKU123 | 400-Watt Bulb Work Light SKU123 Product Note |
    And I should not see a "Checkout Order Summary Notes" element
    And "Checkout Order Review Form" must contains values:
      | Notes | My shopping list notes |
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Line item notes should be visible in single page checkout
    Given I proceed as the Admin
    And I login as administrator
    And I go to System/Workflows
    When I click "Activate" on row "Single Page Checkout" in grid
    And I click "Activate" in modal window
    Then I should see "Workflow activated" flash message
    Given I proceed as the User
    When I open page with shopping list List 2
    And I click "Add a note to entire Shopping List"
    And I type "My shopping list notes" in "Shopping List Notes"
    And I click on "Save Shopping List Notes"
    Then I should see "My shopping list notes"
    When I click "Add Shopping List item Note" on row "SKU123" in grid
    And I fill in "Shopping List Product Note" with "SKU123 Product Note"
    And I click "Add"
    Then I should see "Line item note has been successfully updated" flash message
    And I click "Create Order"
    Then I should see following grid:
      | Item                                                         |
      | 400-Watt Bulb Work Light SKU123 In Stock SKU123 Product Note |
    And I should not see a "Checkout Order Summary Notes" element
    And "Checkout Order Review Form" must contains values:
      | Notes | My shopping list notes |
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Order view page should show order notes and line item notes on storefront
    Given I click "Orders"
    And click view "1" in grid
    Then I should see "Notes My shopping list notes"
    And I should see that Notes in 1 row is equal to "SKU123 Product Note"
    When I click "Orders"
    And click view "2" in grid
    Then I should see "Notes My shopping list notes"
    And I should see that Notes in 1 row is equal to "SKU123 Product Note"

  Scenario: Order view page should show order notes and line item notes in back-office
    Given I proceed as the Admin
    When I go to Sales/Orders
    And click view "1" in grid
    Then I should see "Customer Notes My shopping list notes"
    And I should see that Notes in 1 row is equal to "SKU123 Product Note"
    When I go to Sales/Orders
    And click view "2" in grid
    Then I should see "Customer Notes My shopping list notes"
    And I should see that Notes in 1 row is equal to "SKU123 Product Note"
