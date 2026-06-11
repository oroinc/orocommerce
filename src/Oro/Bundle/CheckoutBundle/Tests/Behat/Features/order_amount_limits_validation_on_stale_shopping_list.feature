@ticket-BB-26770
@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml

Feature: Order amount limits validation on stale shopping list
  In order to surface a user-friendly error when admin tightens minimum or maximum order amount
  after the shopping list page is already loaded
  As a buyer
  I want the matching limit message instead of the generic "Could not perform transition" error

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Buyer pre-loads shopping list page while no order amount limits are configured
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    Then I should see a "Create Order From Shopping List Button" element

  Scenario: Admin sets maximum order amount below minimum
    Given I proceed as the Admin
    And login as administrator
    And I go to System/Configuration
    And I follow "Commerce/Sales/Checkout" on configuration sidebar
    And uncheck "Use default" for "Minimum Order Amount" field
    And I fill in "Minimum Order Amount USD Config Field" with "50"
    And uncheck "Use default" for "Maximum Order Amount" field
    And I fill in "Maximum Order Amount USD Config Field" with "5"
    When I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Buyer sees only the minimum violation message when both limits are violated
    Given I proceed as the Buyer
    When I click "Create Order"
    Then I should see "A minimum order subtotal of $50.00 is required to check out. Please add $40.00 more to proceed." flash message

  Scenario: Admin lowers minimum order amount below shopping list subtotal
    Given I proceed as the Admin
    And I fill in "Minimum Order Amount USD Config Field" with "5"
    # Maximum stays $5 from the previous scenario — List 1 subtotal $10 now violates only the maximum
    When I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Buyer sees only the maximum violation message after the minimum is loosened
    Given I proceed as the Buyer
    When I click "Create Order"
    Then I should see "The order subtotal cannot exceed $5.00. Please remove at least $5.00 to proceed." flash message

  Scenario: Admin loosens both order amount limits
    Given I proceed as the Admin
    And I fill in "Minimum Order Amount USD Config Field" with "1"
    And I fill in "Maximum Order Amount USD Config Field" with "100"
    When I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Buyer completes checkout from the same stale shopping list page
    Given I proceed as the Buyer
    When I click "Create Order"
    And I click "Ship to This Address"
    And I click "Continue"
    And I click "Continue"
    And I click "Continue"
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
