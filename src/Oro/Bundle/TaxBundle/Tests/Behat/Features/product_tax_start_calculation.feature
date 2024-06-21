@ticket-BB-21759
@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroTaxBundle:product_tax_start_calculation.yml

Feature: Product tax start calculation
  As an administrator, I want to be able to configure the tax calculation and see the corresponding changes in
  the order.

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I enable configuration options:
      | oro_tax.tax_enable |
    And I change configuration options:
      | oro_tax.use_as_base_by_default | destination |
      | oro_tax.start_calculation_on   | total       |

  Scenario: Create order with taxes using 'Start calculation on' configuration with 'total' option
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I am on homepage
    And click "Quick Order"
    And fill "Quick Order Form" with:
      | SKU1 | simple-product-01 |
      | SKU2 | simple-product-02 |
      | SKU3 | simple-product-03 |
      | SKU4 | simple-product-04 |
    And I wait for products to load
    And fill "Quick Order Form" with:
      | QTY1 | 5 |
    When I click "Create Order"
    And click "Ship to This Address"
    And click "Continue"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    Then I should see Checkout Totals with data:
      | Subtotal | $281.42 |
      | Shipping | $3.00   |
      | Tax      | $53.47  |
    And should see "Total: $337.89"
    When I press "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Checkout 'Start calculation on' configuration
    Given I change configuration options:
      | oro_tax.start_calculation_on | item |

  Scenario: Create order with taxes using 'Start calculation on' configuration with 'item' option
    Given I am on homepage
    And click "Quick Order"
    And fill "Quick Order Form" with:
      | SKU1 | simple-product-01 |
      | SKU2 | simple-product-02 |
      | SKU3 | simple-product-03 |
      | SKU4 | simple-product-04 |
    And I wait for products to load
    And fill "Quick Order Form" with:
      | QTY1 | 5 |
    When I click "Create Order"
    And click "Ship to This Address"
    And click "Continue"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    Then I should see Checkout Totals with data:
      | Subtotal | $281.42 |
      | Shipping | $3.00   |
      | Tax      | $53.48  |
    And should see "Total: $337.90"
    When I press "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Login as administrator
    Given I proceed as the Admin
    And login as administrator

  Scenario: Check the order with taxes that was created with the configuration 'Start calculation on' and the option 'item'
    Given I go to Sales/ Orders
    When I filter "Total ($)" as equals "$337.90"
    Then number of records should be 1
    When I click "View" on first row in grid
    And click "Promotions and Discounts"
    Then I see next subtotals for "Backend Order":
      | Subtotal | $281.42 |
      | Tax      | $53.48  |
      | Total    | $337.90 |
    And should see next rows in "Backend Order Taxes Totals Table" table
      |          | Incl. Tax | Excl. Tax | Tax Amount |
      | Shipping | $3.00     | $3.00     | $0.00      |
      | Total    | $337.90   | $284.42   | $53.48     |
    And should see next rows in "Backend Order Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 19%  | $281.42        | $53.48     |

  Scenario: Check the order with taxes that was created with the configuration 'Start calculation on' and the option 'total'
    Given I go to Sales/ Orders
    When I filter "Total ($)" as equals "$337.89"
    Then number of records should be 1
    When I click "View" on first row in grid
    And click "Promotions and Discounts"
    Then I see next subtotals for "Backend Order":
      | Subtotal | $281.42 |
      | Tax      | $53.47  |
      | Total    | $337.89 |
    And should see next rows in "Backend Order Taxes Totals Table" table
      |          | Incl. Tax | Excl. Tax | Tax Amount |
      | Shipping | $3.00     | $3.00     | $0.00      |
      | Total    | $337.89   | $284.42   | $53.47     |
    And should see next rows in "Backend Order Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 19%  | $281.42        | $53.47     |
