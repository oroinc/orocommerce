@ticket-BB-15783
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroTaxBundle:digital_products_taxes.yml
Feature: Digital Products Taxes
  In order to sell digital products with taxes
  As a Administrator
  I want to see destination address used to calculate taxes for digital products

  Scenario: Prepare sessions
    Given I login as AmandaRCole@example.org the "Buyer" at "first_session" session
    And I login as administrator and use in "second_session" as "Admin"

  Scenario: Configure Shipping Origin and Digital Products Tax Codes
    Given I go to System/Configuration
    And I follow "Commerce/Taxation/Tax Calculation" on configuration sidebar
    And uncheck "Use default" for "Use as Base by Default" field
    And uncheck "Use default" for "Origin Address" field
    And I fill "Tax Calculation Form" with:
      | Use As Base By Default | Shipping Origin |
      | Origin Country         | Germany         |
      | Origin Region          | Berlin          |
      | Origin Zip Code        | 10115           |
    And I save form
    Then I should see "Configuration saved" flash message
    And I go to System/Configuration
    And I follow "Commerce/Taxation/US Sales Tax" on configuration sidebar
    And uncheck "Use default" for "Digital Products Tax Codes" field
    And I fill "Tax US Sales Tax Form" with:
      | Digital Products Tax Codes | digital |
    And I save form
    Then I should see "Configuration saved" flash message
    And I go to System/Configuration
    And I follow "Commerce/Taxation/EU VAT Tax" on configuration sidebar
    And uncheck "Use default" for "Digital Products Tax Codes" field
    And I fill "Tax EU Vat Tax Form" with:
      | Digital Products Tax Codes | digital |
    And I save form
    Then I should see "Configuration saved" flash message
    And There are products in the system available for order

  Scenario: Customer from European Union, Austria buys digital and non-digital products
    Given I operate as the Buyer
    When I open page with shopping list List 1
    And I click "Create Order"
    And I select "ORO, Second avenue, 1010 Vienna, Austria" on the "Billing Information" checkout step and press Continue
    And I select "ORO, Second avenue, 1010 Vienna, Austria" on the "Shipping Information" checkout step and press Continue
    And I click "Continue"
    And I click "Continue"
    And I should see "Subtotal $20.00"
    And I should see "Shipping $3.00"
    And I should see "Tax $3.00"
    And I should see "Total $26.00"
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Customer from United States, Florida buys digital and non-digital products
    Given I operate as the Buyer
    When I go to homepage
    And I open page with shopping list List 2
    And I click "Create Order"
    And I select "ORO, Third avenue, TALLAHASSEE FL US 32003" on the "Billing Information" checkout step and press Continue
    And I select "ORO, Third avenue, TALLAHASSEE FL US 32003" on the "Shipping Information" checkout step and press Continue
    And I click "Continue"
    And I click "Continue"
    And I should see "Subtotal $20.00"
    And I should see "Shipping $3.00"
    And I should see "Tax $1.00"
    And I should see "Total $24.00"
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
