@regression
@ticket-BB-21773
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
Feature: Shopping list limit and notes handling during checkout
  As a Customer User, when the shopping list limit is set to 1 in the system configuration,
  I should not see any previously added notes in Shopping List after processing the Checkout.

  Scenario: Remove one shopping list
    Given I login as AmandaRCole@example.org the "Buyer" at "first_session" session
    And I open shopping list widget
    And I click "View Details"
    And I click "Shopping List Actions"
    And I click "Delete"
    And I click "Yes, delete" in modal window
    And I should see "Shopping List deleted" flash message
    Then I should see "1" in the "Shopping List Widget" element

  Scenario: Set limit to One shopping list in configuration
    Given I login as administrator and use in "second_session" as "Admin"
    And I activate "Single Page Checkout" workflow
    And I go to System/Configuration
    And I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And I uncheck "Use default" for "Shopping List Limit" field
    And I fill in "Shopping List Limit" with "1"
    And I save setting
    Then I should see "Configuration saved" flash message

  Scenario: Add notes to Shopping List
    Given I operate as the Buyer
    And I reload the page
    And I click "Edit"
    And I click "Shopping List Actions"
    And I click "Add Note"
    And I type "My important note" in "Shopping List Notes in Modal"
    And I press "Space" key on "UiWindow okButton" element
    Then I should see "My important note"

  Scenario: Process Checkout
    Given I open shopping list widget
    And I click "Checkout"
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Check if notes are cleared in Shopping List
    Given I open shopping list widget
    And I click "Open List"
    Then I should not see "My important note"
