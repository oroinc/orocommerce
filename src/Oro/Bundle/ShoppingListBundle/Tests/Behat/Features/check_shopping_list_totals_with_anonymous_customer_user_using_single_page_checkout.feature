@regression
@ticket-BB-23185
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml

Feature: Check shopping list totals with anonymous customer user using single page checkout

  Scenario: Feature Background
    Given sessions active:
      | Admin | system_session |
      | Guest | first_session  |
    And I activate "Single Page Checkout" workflow

  Scenario: Set limit to One shopping list in configuration
    Given I proceed as the Admin
    And login as administrator
    When I go to System/Configuration
    And follow "Commerce/Sales/Shopping List" on configuration sidebar
    And uncheck "Use default" for "Shopping List Limit" field
    And fill in "Shopping List Limit" with "1"
    And uncheck "Use default" for "Enable Guest Shopping List" field
    And check "Enable Guest Shopping List"
    And I save setting
    Then I should see "Configuration saved" flash message

  Scenario: Enable guest checkout setting
    Given I follow "Commerce/Sales/Checkout" on configuration sidebar
    And uncheck "Use default" for "Enable Guest Checkout" field
    And check "Enable Guest Checkout"
    When I save form
    Then the "Enable Guest Checkout" checkbox should be checked

  Scenario: Create product with prices
    Given I go to Products/Products
    When I click "Create Product"
    And click "Continue"
    And fill "Create Product Form" with:
      | SKU    | ORO_PRODUCT_1 |
      | Name   | ORO_PRODUCT_1 |
      | Status | Enable        |
    And click "Product Prices"
    And click "Add Product Price"
    And I set Product Price collection element values in 1 row:
      | Price List     | Default Price List |
      | Quantity value | 1                  |
      | Quantity Unit  | each               |
      | Value          | 10.50              |
      | Currency       | $                  |
    And save form
    Then I should see "Product has been saved" flash message

  Scenario: Create Shopping List as unauthorized user
    Given I proceed as the Guest
    And am on homepage
    When I type "ORO_PRODUCT_1" in "search"
    And click "Search Button"
    And click "ORO_PRODUCT_1"
    And click "Add to Shopping List"
    Then I should see "Product has been added to" flash message and I close it
    When I open shopping list widget
    And click "Open List"
    Then I should see "ORO_PRODUCT_1"

  Scenario: Check shopping list widget after order create
    When I click on "Create Order"
    And I click on "Flash Message Close Button"
    And I open shopping list widget
    Then I should see "1 ea $10.50"
    And should not see "1 ea $0.00"
