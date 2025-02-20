@regression
@ticket-BB-25188
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml

Feature: Promotions at Checkout with schedules
  In order to find out applied schedules promotions at checkout
  As an site user
  I need to have ability to see applied schedules promotion at checkout stage

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create promotion without schedules
    Given I login as administrator
    And I go to Marketing/Promotions/Promotions
    And click "Create Promotion"
    And fill "Promotion Form" with:
      | Name               | Sample promotion |
      | Sort Order         | 1                |
      | Enabled            | 1                |
      | Type               | Percent          |
      | Discount Value (%) | 50               |
    And I click on "Advanced Filter"
    And I drag and drop "Field Condition" on "Drop condition here"
    And I click "Choose a field.."
    And I click on "SKU"
    And type "SKU123" in "ActiveFilterValue"
    And save form
    And I click "Continue" in confirmation dialogue
    And I should see "Promotion has been saved" flash message

  Scenario: Check checkout page that promotion without schedules is applied
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I am on homepage
    And I open page with shopping list List 1
    And I should see following "Multi Shipping Shopping List" grid:
      | SKU    | Product                  | Price  | Subtotal |
      | SKU123 | 400-Watt Bulb Work Light | $2.00  | $10.00   |
    And I should see Checkout Totals with data:
      | Subtotal | $10.00 |
      | Discount | -$5.00 |
      | Total:   | $5.00  |

  Scenario: Create promotion without activate day but with deactivate date in future
    Given I proceed as the Admin
    And I go to Marketing/Promotions/Promotions
    And click edit Sample promotion in grid
    And fill "Promotion Form" with:
      | Deactivate At (first) | <Date:today +1 day> |
    And save form
    And I should see "Promotion has been saved" flash message

  Scenario: Check checkout page that promotion without activate day but with deactivate date in future is applied
    Given I proceed as the Buyer
    And I am on homepage
    And I open page with shopping list List 1
    And I should see following "Multi Shipping Shopping List" grid:
      | SKU    | Product                  | Price  | Subtotal |
      | SKU123 | 400-Watt Bulb Work Light | $2.00  | $10.00   |
    And I should see Checkout Totals with data:
      | Subtotal | $10.00 |
      | Discount | -$5.00 |
      | Total:   | $5.00  |

  Scenario: Create promotion with activate day in past but without deactivate date
    Given I proceed as the Admin
    And I go to Marketing/Promotions/Promotions
    And click edit Sample promotion in grid
    And I click "Remove Promotion Schedule"
    Then I click "Add Promotion Schedule"
    And fill "Promotion Form" with:
      | Activate At (first) | <Date:today -1 day> |
    And save form
    And I should see "Promotion has been saved" flash message

  Scenario: Check checkout page that promotion with activate day in past but without deactivate date is applied
    Given I proceed as the Buyer
    And I am on homepage
    And I open page with shopping list List 1
    And I should see following "Multi Shipping Shopping List" grid:
      | SKU    | Product                  | Price  | Subtotal |
      | SKU123 | 400-Watt Bulb Work Light | $2.00  | $10.00   |
    And I should see Checkout Totals with data:
      | Subtotal | $10.00 |
      | Discount | -$5.00 |
      | Total:   | $5.00  |

  Scenario: Create promotion with activate day in past and deactivate date in future
    Given I proceed as the Admin
    And I go to Marketing/Promotions/Promotions
    And click edit Sample promotion in grid
    And I click "Remove Promotion Schedule"
    Then I click "Add Promotion Schedule"
    And fill "Promotion Form" with:
      | Activate At (first) | <Date:today -1 day> |
      | Deactivate At (first) | <Date:today +1 day> |
    And save form
    And I should see "Promotion has been saved" flash message

  Scenario: Check checkout page that promotion with activate day in past and deactivate date in future is applied
    Given I proceed as the Buyer
    And I am on homepage
    And I open page with shopping list List 1
    And I should see following "Multi Shipping Shopping List" grid:
      | SKU    | Product                   | Price  | Subtotal |
      | SKU123 | 400-Watt Bulb Work Light  | $2.00  | $10.00   |
    And I should see Checkout Totals with data:
      | Subtotal | $10.00 |
      | Discount | -$5.00 |
      | Total:   | $5.00  |

  Scenario: Create promotion with activate and deactivate day in future
    Given I proceed as the Admin
    And I go to Marketing/Promotions/Promotions
    And click edit Sample promotion in grid
    And I click "Remove Promotion Schedule"
    Then I click "Add Promotion Schedule"
    And fill "Promotion Form" with:
      | Activate At (first) | <Date:today +1 day> |
      | Deactivate At (first) | <Date:today +3 day> |
    And save form
    And I should see "Promotion has been saved" flash message

  Scenario: Check checkout page that promotion with activate day in future is not applied
    Given I proceed as the Buyer
    And I am on homepage
    And I open page with shopping list List 1
    And I should see following "Multi Shipping Shopping List" grid:
      | SKU    | Product                  | Price  | Subtotal |
      | SKU123 | 400-Watt Bulb Work Light | $2.00  | $10.00   |
    And I should see Checkout Totals with data:
      | Subtotal | $10.00 |
      | Total:   | $10.00  |
