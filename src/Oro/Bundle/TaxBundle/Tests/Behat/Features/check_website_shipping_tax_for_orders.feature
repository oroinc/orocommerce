@regression
@ticket-BB-24076
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroTaxBundle:OrderTaxCurrencies.yml
@fixture-OroTaxBundle:ProductAndTaxes.yml

Feature: Check website shipping tax for orders
  In order to manage usage of shipping tax by website configuration
  As an administrator
  I want to be see correct total value in order detail page

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session |
      | Buyer | second_session |
    And I proceed as the Admin
    And login as administrator

  Scenario: Configure taxes
    When I go to System/Websites
    And I click "Configuration" on row "Default" in grid
    And I follow "Commerce/Taxation/Shipping" on configuration sidebar
    And uncheck "Use Organization" for "Tax Code" field
    And uncheck "Use Organization" for "Shipping Rates Include Tax" field
    And I fill "Tax Shipping Form" with:
      | Tax Code                   | taxable_items |
      | Shipping Rates Include Tax | true          |
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: Configure Tax Calculation
    Given I go to System/Configuration
    And I follow "Commerce/Taxation/Tax Calculation" on configuration sidebar
    And fill "Tax Calculation Form" with:
      | Use As Base By Default Use Default | false       |
      | Use As Base By Default             | Destination |
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: Order product with included shipping as buyer
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 2
    And I click "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I check "Delete this shopping list after submitting order" on the "Order Review" checkout step and press Submit Order
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    When I click "click here to review"
    Then I should be on Order Frontend View page
    And I should see "Total $13.90"

  Scenario: Check order taxes in admin
    Given I proceed as the Admin
    And I go to Sales/Orders
    When I click "View" on first row in grid
    When I click "Totals"
    And I should see next rows in "Backend Order Taxes Totals Table" table
      |          | Incl. Tax | Excl. Tax | Tax Amount |
      | Shipping | $3.00     | $2.75     | $0.25      |
      | Total    | $13.90    | $12.75    | $1.15      |
    And I should see next rows in "Backend Order Taxes Results Table" table
      | Tax               | Rate | Taxable Amount  | Tax Amount |
      | berlin_sales      | 9%   | $10.00          | $0.90     |
    And I see next subtotals for "Backend Order":
      | Subtotal        | $10.00  |
      | Total           | $13.90  |

  Scenario: Order product with included shipping as admin
    Given I go to Sales/Orders
    And click "Create Order"
    And click "Add Product"
    When I fill "Order Form" with:
      | Customer         | Company A                                   |
      | Customer User    | Amanda Cole                                 |
      | Billing Address  | ORO, Fifth avenue, 10115 Berlin, Germany    |
      | Shipping Address | ORO, Fifth avenue, 10115 Berlin, Germany    |
      | Product          | SKU123                                      |
      | Quantity         | 5                                           |

    And I click on "Calculate Shipping"
    And I click "Shipping Method Flat Rate Radio Button"
    And I save form
    And I should see next rows in "Backend Order Taxes Totals Table" table
      |          | Incl. Tax | Excl. Tax | Tax Amount |
      | Shipping | $3.00     | $2.75     | $0.25      |
      | Total    | $13.90    | $12.75    | $1.15      |
    And I should see next rows in "Backend Order Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | berlin_sales      | 9%   | $10.00          | $0.90     |
    And I see next subtotals for "Backend Order":
      | Subtotal        | $10.00  |
      | Total           | $13.90  |
