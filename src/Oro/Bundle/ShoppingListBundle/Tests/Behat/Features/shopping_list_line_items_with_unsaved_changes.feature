@ticket-BB-20192
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml

Feature: Shopping List Line Items With Unsaved Changes
  As a Buyer
  I need to be protected from accidental unsaved data loss

  Scenario: Discard order transaction with unsaved changed
    Given I set configuration property "oro_shopping_list.shopping_lists_page_enabled" to "1"
    And I login as AmandaRCole@example.org buyer
    And I follow "Account"
    When I click on "Shopping Lists Navigation Link"
    And I click Edit "List 2" in grid
    Then I should see following grid:
      | SKU    | QtyUpdate All |
      | SKU123 | 10 item        |
    When I click on "Shopping List Inline Line Item 1 Quantity"
    And I type "3" in "Shopping List Inline Line Item 1 Quantity Input"
    And I click "Create Order"
    Then should see "You have unsaved changes, are you sure you want to leave this page?" in confirmation dialogue
    And I click "Cancel" in confirmation dialogue
    And I click "Cancel"
    Then Page title equals to "List 2 - Shopping Lists - My Account"

