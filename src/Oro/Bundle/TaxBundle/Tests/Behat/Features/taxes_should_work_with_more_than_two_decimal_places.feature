@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroTaxBundle:taxes_should_work_with_more_than_two_decimal_places.yml
@ticket-BB-15155
@ticket-BB-15969

Feature: Taxes should work with more than two decimal places
  In order to be able to make purchases
  As a buyer
  I want to be able to create orders with products that have taxes with more than two decimal places

  Scenario: Prepare sessions
    Given I login as AmandaRCole@example.org the "Buyer" at "first_session" session
    And I login as administrator and use in "second_session" as "Admin"

  Scenario: Can't save tax rate with more than 4 decimal places
    Given I go to Taxes/Taxes
    And I click "Create Tax"
    And I fill form with:
      | Code | Wrong_tax_rate |
      | Rate | 0.12345        |
    And I save form
    Then I should see validation errors:
      | Rate | Tax rate can't have more than 4 decimal places |
    When I fill form with:
      | Code | Wrong_tax_rate |
      | Rate | 0.000001       |
    And I save form
    Then I should see validation errors:
      | Rate | Tax rate can't have more than 4 decimal places |
    Given I fill form with:
      | Code | Correct_tax_rate |
      | Rate | 0.1234           |
    And I save form
    Then I should not see validation errors:
      | Rate | Tax rate can't have more than 4 decimal places |
    And I should see "Tax has been saved" flash message
    And Rate field should has 0.1234 value
    Then I go to Taxes/Taxes
    And I should see following grid:
      | Code             | Rate     |
      | berlin_sales     | 12.1234% |
      | Correct_tax_rate | 0.1234%  |
    And click view "Correct_tax_rate" in grid
    Then I should see "Rate 0.1234%"

  Scenario: Order created successfully with tax with more than two decimal places
    Given I go to System/Configuration
    And I follow "Commerce/Taxation/Tax Calculation" on configuration sidebar
    And uncheck "Use default" for "Use as Base by Default" field
    And uncheck "Use default" for "Origin Address" field
    And I fill "Tax Calculation Form" with:
      | Use As Base By Default | Origin  |
      | Origin Country         | Germany |
      | Origin Region          | Berlin  |
      | Origin Zip Code        | 10115   |
    And I save form
    Then I should see "Configuration saved" flash message
    Given There are products in the system available for order
    And I operate as the Buyer
    When I open page with shopping list List 1
    And I click "Create Order"
    Then I should not see "500 Internal Server Error"
    And I should see "Billing information"
    And I should see "Subtotal $10.00"
    And I should see "Tax $1.21"
    And I should see "Total $11.21"
    And I click "Continue"
    And I click "Continue"
    And I click "Continue"
    And I click "Continue"
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Tax creation with some specific value
    Given I operate as the Admin
    And I go to Taxes/Taxes
    And I click "Create Tax"
    When I fill form with:
      | Code | Specific_tax_rate |
      | Rate | 9.598             |
    And I save form
    Then I should not see validation errors:
      | Rate | Tax rate can't have more than 4 decimal places |
    And I should see "Tax has been saved" flash message
    When I go to Taxes/Taxes
    And click view "Specific_tax_rate" in grid
    Then I should see "Rate 9.598%"
