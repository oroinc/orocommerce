@regression
@ticket-BB-21025
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroTaxBundle:ProductAndTaxes.yml
@fixture-OroCheckoutBundle:Checkout.yml

Feature: Recalculation Tax After Overwriting Shipping Cost
  In order to have correct amount of tax after overwriting shipping cost manually
  As an administrator
  I can see shipping cost row in totals of order are updated correctly in edit and view page

  Scenario: Create sessions for administrator and buyer
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Set tax calculation rule
    Given I proceed as the Admin
    When I login as administrator
    And I go to System/ Configuration
    And I follow "Commerce/Taxation/Shipping" on configuration sidebar
    And uncheck "Use default" for "Tax Code" field
    When I fill "Tax Shipping Form" with:
      | Tax Code | Product Tax Code |
    And I save form
    Then I should see "Configuration saved" flash message
    When I follow "Commerce/Taxation/Tax Calculation" on configuration sidebar
    And uncheck "Use default" for "Use as Base by Default" field
    And I fill "Tax Calculation Form" with:
      | Use As Base By Default | Destination |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check out and create an order with flat rate of shipping cost
    Given I proceed as the Buyer
    And I signed in as MarleneSBradley@example.org on the store frontend
    When I open page with shopping list "Shopping list"
    And I click "Create Order"
    And on the "Billing Information" checkout step I press Continue
    And on the "Shipping Information" checkout step I press Continue
    And I check "Flat Rate" on the checkout page
    And on the "Shipping" checkout step I press Continue
    And I check "Payment Terms" on the checkout page
    And on the "Payment" checkout step I press Continue
    Then I should see "Subtotal $40.00"
    And I should see "Shipping $3.00"
    And I should see "Tax $4.30"
    And I should see "$47.30"
    When I fill form with:
      | PO Number | Order1 |
    And I press "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Check submitted order and the total of order with tax
    Given I proceed as the Admin
    When I go to Sales/Orders
    And I click Edit "Order1" in grid
    And I press "Order Totals"
    Then I should see next rows in "Backend Shipping Cost Tax Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Shipping   | $3.30     | $3.00     | $0.30      |
      | Total      | $47.30    | $43.00    | $4.30      |
    When I click "Shipping Information"
    And I type "0" in "Overridden Shipping Cost Amount"
    And I press "Order Totals"
    Then I should see next rows in "Backend Shipping Cost Tax Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Shipping   | $0.00     | $0.00     | $0.00      |
      | Total      | $44.00    | $40.00    | $4.00      |
    When I click "Shipping Information"
    And I type "10" in "Overridden Shipping Cost Amount"
    And I press "Order Totals"
    Then I should see next rows in "Backend Shipping Cost Tax Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Shipping   | $11.00    | $10.00    | $1.00      |
      | Total      | $55.00    | $50.00    | $5.00      |
    When I save form
    Then I should see "Order has been saved" flash message
    When I press "Order Totals"
    Then I should see next rows in "Backend Shipping Cost Tax Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Shipping   | $11.00    | $10.00    | $1.00      |
      | Total      | $55.00    | $50.00    | $5.00      |
    And I should see "Subtotal $40.00"
    And I should see "Shipping $10.00"
    And I should see "Tax $5.00"
    And I should see "Total $55.00"
