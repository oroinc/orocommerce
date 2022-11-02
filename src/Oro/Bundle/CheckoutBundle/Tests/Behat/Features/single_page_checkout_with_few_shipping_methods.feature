@ticket-BB-16802
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroFlatRateShippingBundle:FlatRate2Integration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@regression

Feature: Single Page Checkout With Few Shipping Methods
  In order to complete the checkout process
  As a Buyer
  I want to see all available shipping methods

  Scenario: Create different window session
    Given sessions active:
      | Admin  |first_session |
      | User   |second_session|

  Scenario: Enable Single Page Checkout Workflow
    Given I proceed as the Admin
    And I login as administrator
    And I go to System/Workflows
    When I click "Activate" on row "Single Page Checkout" in grid
    And I click "Activate" in modal window
    Then I should see "Workflow activated" flash message

  Scenario: Configure shipping rules
    Given I go to System/Shipping Rules
    And I click Edit "Default" in grid
    And I fill "Shipping Rule" with:
      | Method     | Flat Rate 2 |
    And I fill form with:
      | Price | 2         |
      | Type  | per_order |
    When I save and close form
    Then I should see "Shipping rule has been saved" flash message

  Scenario: Check notification shown for no payment method selected
    Given I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I scroll to top
    And I wait line items are initialized
    And I click "Create Order"
    Then Shipping Type "Flat Rate: $3.00" is shown for Buyer selection
    And Shipping Type "Flat Rate 2: $2.00" is shown for Buyer selection

    When I reload the page
    Then Shipping Type "Flat Rate: $3.00" is shown for Buyer selection
    And Shipping Type "Flat Rate 2: $2.00" is shown for Buyer selection
