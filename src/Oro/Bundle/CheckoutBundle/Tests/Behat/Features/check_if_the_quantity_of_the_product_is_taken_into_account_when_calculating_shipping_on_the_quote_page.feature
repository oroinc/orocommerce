@regression
@ticket-BB-23038
@fixture-OroCustomerBundle:CustomerUsersWithAmandaAddressFixture.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml

Feature: Check if the quantity of the product is taken into account when calculating shipping on the quote page

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create product
    Given I proceed as the Admin
    And login as administrator
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

  Scenario Outline: Create integrations
    Given I go to System/ Integrations/ Manage Integrations
    When I click "Create Integration"
    And fill "Integration Form" with:
      | Type  | <Type> |
      | Name  | <Name> |
      | Label | <Name> |
    And save and close form
    Then I should see "Integration saved" flash message
    Examples:
      | Type               | Name                 |
      | Flat Rate Shipping | Flat Rate Shipping 1 |
      | Flat Rate Shipping | Flat Rate Shipping 2 |

  Scenario Outline: Create shipping rules
    Given I go to System/ Shipping Rules
    When I click "Create Shipping Rule"
    And fill "Shipping Rule" with:
      | Enable     | true         |
      | Name       | <Name>       |
      | Sort Order | 1            |
      | Currency   | USD          |
      | Expression | <Expression> |
      | Method     | <Method>     |
    And fill form with:
      | Price | 10        |
      | Type  | per_order |
    And save and close form
    Then I should see "Shipping rule has been saved" flash message
    Examples:
      | Name                      | Expression           | Method               |
      | Flat Rate Shipping Rule 1 | subtotal.value < 150 | Flat Rate Shipping 1 |
      | Flat Rate Shipping Rule 2 | subtotal.value > 150 | Flat Rate Shipping 2 |

  Scenario: Create a quote and check shipping calculation
    Given I go to Sales/Quotes
    When I click "Create Quote"
    And fill form with:
      | Customer      | Customer1   |
      | Customer User | Amanda Cole |
    And fill "Quote Line Items" with:
      | Product  | ORO_PRODUCT |
      | Quantity | 1           |
    And click "Add Offer"
    When click "Shipping Information"
    And I click on "Calculate Shipping"
    Then I should see "Flat Rate Shipping 1 $10.00"

    When I fill "Quote Line Items" with:
      | Quantity | 2 |
    And click "Shipping Information"
    And click on "Calculate Shipping"
    Then I should see "Flat Rate Shipping 2 $10.00"

    When I click "Shipping Method Flat Rate Radio Button"
    And save and close form
    Then I should see "Quote has been saved" flash message

    When I click "Send to Customer"
    And click "Send"
    Then I should see "Quote #1 successfully sent to customer" flash message

  Scenario: Update customer payment
    Given I go to Sales/ Payment terms
    When I click "Create Payment Term"
    And type "Payment term" in "Label"
    And save and close form
    Then I should see "Payment term has been saved" flash message

    When I go to Customers/ Customers
    And click edit Customer1 in grid
    And fill form with:
      | Payment Term | Payment term |
    And save and close form
    Then I should see "Customer has been saved" flash message

  Scenario: Customer can create order from Quote
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    When I click "Account Dropdown"
    And I click "Quotes"
    And click "View" on first row in grid
    And should see following "Frontend Quote Grid" grid:
      | Item                         | Quantity                  | Unit Price      |
      | ORO_PRODUCT SKU: ORO_PRODUCT | 2 ea or more 1 ea or more | $100.00 $100.00 |
    And click "Accept and Submit to Order"
    And click "First Product Second Offer"
    When I click "Checkout"
    Then Page title equals to "Billing Information - Checkout"
    When I click "Ship to This Address"
    And click "Continue"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    Then I should see Checkout Totals with data:
      | Subtotal | $100.00 |
      | Shipping | $10.00  |
    And should see "Total: $110.00"
    When I press "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
