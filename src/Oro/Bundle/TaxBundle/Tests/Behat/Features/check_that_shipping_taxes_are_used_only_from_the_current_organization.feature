@regression
@ticket-BB-24654
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroTaxBundle:check_that_shipping_taxes_are_used_only_from_the_current_organization.yml

Feature: Check that shipping taxes are used only from the current organization
  Make sure that the taxes in the order correspond to the organization in which the checkout is made

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I activate "Single Page Checkout" workflow

  Scenario: Configure tax calculation
    Given I proceed as the Admin
    And login as administrator
    When I go to System/Configuration
    And follow "Commerce/Taxation/Tax Calculation" on configuration sidebar
    And uncheck "Use default" for "Use as Base by Default" field
    And fill "Tax Calculation Form" with:
      | Use As Base By Default | Destination |
    When I save form
    Then I should see "Configuration saved" flash message

  Scenario: Create Tax
    Given I go to Taxes/ Taxes
    And click "Create Tax"
    And fill form with:
      | Code | TAX |
      | Rate | 50  |
    When I save and close form
    Then I should see "Tax has been saved" flash message

  Scenario: Create Tax Jurisdiction
    Given I go to Taxes/ Tax Jurisdictions
    And click "Create Tax Jurisdiction"
    And fill form with:
      | Code    | CALIFORNIA_JURISDICTION |
      | Country | United States           |
      | State   | California              |
    When I save and close form
    Then should see "Tax Jurisdiction has been saved" flash message

  Scenario Outline: Create Tax for each organization
    Given I am logged in under <Organization> organization

    Given I go to Taxes/ Product Tax Codes
    And click "Create Product Tax Code"
    And fill form with:
      | Code | PRODUCT_TAX_CODE |
    And I press "Save and Close"
    Then I should see "Product Tax Code has been saved" flash message

    When I go to Taxes/ Customer Tax Codes
    And click "Create Customer Tax Code"
    And fill form with:
      | Code | CUSTOMER_TAX_CODE |
    When I save and close form
    Then I should see "Customer Tax Code has been saved" flash message

    When I go to Taxes/ Tax Rules
    And click "Create Tax Rule"
    And fill "Tax Rule Form" with:
      | Customer Tax Code | CUSTOMER_TAX_CODE       |
      | Product Tax Code  | PRODUCT_TAX_CODE        |
      | Tax Jurisdiction  | CALIFORNIA_JURISDICTION |
      | Tax               | TAX                     |
    And save and close form
    Then should see "Tax Rule has been saved" flash message
    Examples:
      | Organization |
      | Acme         |
      | ORO          |

  Scenario: Add customer tax to Customer
    Given go to Customers/ Customers
    And I click edit Company A in grid
    When I fill form with:
      | Tax Code | CUSTOMER_TAX_CODE |
    And save and close form
    Then should see "Customer has been saved" flash message

  Scenario: Create product with tax and prices
    Given I go to Products/Products
    When I click "Create Product"
    And click "Continue"
    And fill "Create Product Form" with:
      | SKU      | ORO_PRODUCT      |
      | Name     | ORO_PRODUCT      |
      | Status   | Enable           |
      | Tax Code | PRODUCT_TAX_CODE |
    And click "Product Prices"
    And click "Add Product Price"
    And I set Product Price collection element values in 1 row:
      | Price List     | Default Price List |
      | Quantity value | 1                  |
      | Quantity Unit  | each               |
      | Value          | 10                 |
      | Currency       | $                  |
    And save form
    Then I should see "Product has been saved" flash message

  Scenario: Create Shopping List
    Given I proceed as the Buyer
    And I signed in as MarleneSBradley@example.org on the store frontend
    And type "ORO_PRODUCT" in "search"
    And click "Search Button"
    And click "Add to Shopping List"
    When I open page with shopping list Shopping List
    And click "Checkout"
    Then I should see "Subtotal $10.00"
    And should see "Shipping $3.00"
    And should see "Tax $5.0"
    And should see "Total: $18.00"
    When I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

