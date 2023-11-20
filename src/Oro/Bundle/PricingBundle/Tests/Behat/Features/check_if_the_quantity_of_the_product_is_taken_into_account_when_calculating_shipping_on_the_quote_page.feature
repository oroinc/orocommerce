@regression
@ticket-BB-23038

Feature: Check if the quantity of the product is taken into account when calculating shipping on the quote page

  Scenario: Create product
    Given I login as administrator
    And go to Products/Products
    When I click "Create Product"
    And click "Continue"
    And fill "ProductForm" with:
      | SKU    | ORO_PRODUCT |
      | Name   | ORO_PRODUCT |
      | Status | Enabled     |
    And click "AddPrice"
    And fill "ProductPriceForm" with:
      | Price List | Default Price List |
      | Quantity   | 1                  |
      | Value      | 100                |
      | Currency   | $                  |
    And save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Create shipping integration
    Given I go to System/ Integrations/ Manage Integrations
    When I click "Create Integration"
    And fill "Integration Form" with:
      | Type  | Flat Rate Shipping |
      | Name  | Flat Rate Shipping |
      | Label | Flat Rate Shipping |
    And save and close form
    Then I should see "Integration saved" flash message

  Scenario: Create shipping rule
    Given I go to System/ Shipping Rules
    When I click "Create Shipping Rule"
    And fill "Shipping Rule" with:
      | Enable     | true                    |
      | Name       | Flat Rate Shipping Rule |
      | Sort Order | 1                       |
      | Currency   | USD                     |
      | Expression | subtotal.value > 150    |
      | Method     | Flat Rate Shipping      |
    And I fill form with:
      | Price | 10        |
      | Type  | per_order |
    And save and close form
    Then I should see "Shipping rule has been saved" flash message

  Scenario: Create a quote and check shipping calculation
    Given I go to Sales/Quotes
    When I click "Create Quote"
    And fill "Quote Line Items" with:
      | Product  | ORO_PRODUCT |
      | Quantity | 1           |
    When I click on "Calculate Shipping"
    And I should see "No shipping methods are available"
    When I save and close form
    Then I should see "Quote has been saved" flash message

  Scenario: Create a quote and check shipping calculation
    Given I go to Sales/Quotes
    When I click "Create Quote"
    And fill "Quote Line Items" with:
      | Product  | ORO_PRODUCT |
      | Quantity | 2           |
    When I click on "Calculate Shipping"
    Then I should see "Flat Rate Shipping $10.00"
    And should not see "No shipping methods are available"
    And click "Shipping Method Flat Rate Radio Button"
    When I save and close form
    Then I should see "Quote has been saved" flash message
