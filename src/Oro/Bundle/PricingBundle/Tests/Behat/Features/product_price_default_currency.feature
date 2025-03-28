@ticket-BAP-16805
@regression

Feature: Product price default currency
  As an administrator, I want to see that the value in the currency selector corresponds to the default value set in
  the system configuration.

  Scenario: Create product with default currency (USD)
    Given I login as administrator
    And go to Products/Products
    When I click "Create Product"
    And click "Continue"
    When I fill "Create Product Form" with:
      | SKU    | ORO_PRODUCT_1 |
      | Name   | ORO_PRODUCT_1 |
      | Status | Enable        |
    And click "Product Prices"
    And click "Add Product Price"
    And I set Product Price collection element values in 1 row:
      | Price List     | Default Price List |
      | Quantity value | 1                  |
      | Quantity Unit  | each               |
      | Value          | 10                 |
      # Do not set the currency, use the default currency.
    And save form
    Then I should see "Product has been saved" flash message

  Scenario: Set EUR as default currency
    Given I go to System/Configuration
    When I follow "System Configuration/General Setup/Currency" on configuration sidebar
    And click "EuroAsDefaultValue"
    And click "Yes"
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Create product with default currency (EUR)
    Given I go to Products/Products
    When I click "Create Product"
    And click "Continue"
    When I fill "Create Product Form" with:
      | SKU    | ORO_PRODUCT_2 |
      | Name   | ORO_PRODUCT_2 |
      | Status | Enable        |
    And click "Product Prices"
    And click "Add Product Price"
    And I set Product Price collection element values in 1 row:
      | Price List     | Default Price List |
      | Quantity value | 1                  |
      | Quantity Unit  | each               |
      | Value          | 10                 |
      # Do not set the currency, use the default currency.
    And save form
    Then I should see "Product has been saved" flash message

  Scenario: Check product currencies
    Given I go to Products/Products
    When I check "USD"
    And check "EUR"
    Then I should see "Price (USD)" column in grid
    And should see "Price (EUR)" column in grid
    And records in grid should be 2
    And should see following grid:
      | SKU           | Name          | Price (USD) | Price (EUR) |
      | ORO_PRODUCT_2 | ORO_PRODUCT_2 |             | Each €10.00 |
      | ORO_PRODUCT_1 | ORO_PRODUCT_1 | Each $10.00 |             |

