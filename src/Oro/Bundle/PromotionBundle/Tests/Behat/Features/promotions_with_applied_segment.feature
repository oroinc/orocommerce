@ticket-BB-18076
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroPromotionBundle:shopping_list.yml
Feature: Promotions with Applied Segment
  In order to manage promotions and reuse existing segments
  As an administrator
  I need to have ability to create promotion and apply segment filter

  Scenario: Logged in as buyer and manager on different window sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create Product Segment
    Given I proceed as the Admin
    And I login as administrator
    When I go to Reports & Segments / Manage Segments
    And I click "Create Segment"
    And I fill "Segment Form" with:
      | Name         | Product Segment SKU1 |
      | Entity       | Product              |
      | Segment Type | Dynamic              |
    And I add the following columns:
      | SKU |
    And I add the following filters:
      | Field Condition | SKU | is equal to | SKU1 |
    When I save and close form
    Then I should see "Segment saved" flash message
    And I should see "SKU1"
    And I should not see "SKU2"

  Scenario: Create Promotion that uses Segment
    When I go to Marketing / Promotions / Promotions
    And I click "Create Promotion"
    And I fill "Promotion Form" with:
      | Name                         | Promotion name  |
      | Sort Order                   | 10              |
      | Enabled                      | 1               |
      | Stop Further Rule Processing | 1               |
      | Triggered by                 | Conditions only |
      | Discount                     | Order Line Item |
      | Type                         | Percent         |
      | Discount Value (%)           | 10.0            |
      | Unit Of Quantity             | item            |
    And I click on "Advanced Filter"
    When I drag and drop "Apply segment" on "Drop condition here"
    And I type "Product Segment SKU1" in "Choose segment"
    Then I should see "Product Segment SKU1" in the "Select2 results" element
    When I click on "Product Segment SKU1"
    And I save form
    And I click "Continue" in confirmation dialogue
    Then I should see "Promotion has been saved" flash message

  Scenario: Check Shopping List Line Items Discounts
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    Then I see next line item discounts for shopping list "List 1":
      | SKU  | Discount |
      | SKU1 | -$1.00   |
      | SKU2 |          |
