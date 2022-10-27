@regression
@ticket-BB-13406
@ticket-BB-16195
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroPricingBundle:PricingRounding.yml
@fixture-OroPricingBundle:PricingRoundingQuote.yml
@skip

Feature: Pricing rounding
  In order to check pricing rounding in shopping list/order/rfq/quote
  As an Administrator
  I create shopping list/order/rfq/quote and change Pricing Precision

  Scenario: Create different window session
    Given sessions active:
      | admin    | first_session  |
      | customer | second_session |

  Scenario: Create demo data
    Given I proceed as the admin
    And login as administrator
    And go to Products/ Products
    And click edit "SKU123" in grid
    And click "Product Prices"
    And click "Add Product Price"
    And fill "Product Price Form" with:
      | Price List | Default Price List |
      | Quantity   | 1                  |
      | Value      | 7.45               |
      | Currency   | $                  |
    And save and close form
    And go to Products/ Products
    And click edit "SKU456" in grid
    And click "Product Prices"
    And click "Add Product Price"
    And fill "Product Price Form" with:
      | Price List | Default Price List |
      | Quantity   | 1                  |
      | Value      | 4.54               |
      | Currency   | $                  |
    And save and close form
    Then I should see "Product has been saved" flash message
    And go to Sales/ Quotes
    And click view "Q123" in grid
    And click "Send to Customer"
    And click "Send"
    And I should see "Quote #1 successfully sent to customer" flash message
    And go to Sales/ Quotes
    And click view "Q456" in grid
    And click "Send to Customer"
    And click "Send"
    And I should see "Quote #2 successfully sent to customer" flash message
    And go to Sales/ Quotes
    And click view "Q789" in grid
    And click "Send to Customer"
    And click "Send"
    And I should see "Quote #3 successfully sent to customer" flash message
    And go to Sales/ Quotes
    And click view "Q321" in grid
    And click "Send to Customer"
    And click "Send"
    And I should see "Quote #4 successfully sent to customer" flash message
    And go to Sales/ Quotes
    And click view "Q654" in grid
    And click "Send to Customer"
    And click "Send"
    And I should see "Quote #5 successfully sent to customer" flash message

  Scenario: Default system stage
    Given I proceed as the admin
    And go to System/ Configuration
    When follow "Commerce/Catalog/Pricing" on configuration sidebar
    And Subtotals Calculation Precision in Sales Documents field should has 4 value
    And I proceed as the customer
    And I signed in as AmandaRCole@example.org on the store frontend
    When click "NewCategory"
    Then should see "Your Price: $7.45 / item" for "Phone" product
    And should see "Listed Price: $7.45 / item" for "Phone" product
    And should see "Your Price: $4.54 / item" for "Light" product
    And should see "Listed Price: $4.54 / item" for "Light" product
    And click "Add to Shopping List" for "Phone" product
    When I hover on "Shopping Cart"
    And I click "Shopping List" on shopping list widget
    Then should see "Subtotal $7.45"
    And should see "Total $7.45"
    When I hover on "Shopping Cart"
    And click "Create New List"
    And should see an "Create New Shopping List popup" element
    And type "New Front Shopping List" in "Shopping List Name"
    And click "Create"
    And should see "New Front Shopping List"
    And click "NewCategory"
    When click "Add to New Front Shopping List" for "Light" product
    And I hover on "Shopping Cart"
    And click "New Front Shopping List"
    Then should see "Subtotal $4.54"
    And should see "Total $4.54"

  Scenario: Set Subtotals Calculation Precision in Sales Documents value to 0 (Half Up)
    Given I proceed as the admin
    And fill "PricingConfigurationForm" with:
      | Subtotals Calculation Precision in Sales Documents System | false |
      | Subtotals Calculation Precision in Sales Documents        | 0     |
    And click "Save settings"
    And should see "Configuration saved" flash message
    When go to Products/ Products
    And click view "SKU123" in grid
    And click "Product Prices"
    Then should see following grid:
      | Price List         | Quantity | Unit | Value |
      | Default Price List | 1        | item | 7.45  |
    When click "Edit"
    And click "Product Prices"
    Then Value field should has 7.4500 value
    And click "Cancel"
    And I proceed as the customer
    When I hover on "Shopping Cart"
    And I click "Shopping List" on shopping list widget
    Then should see "Subtotal $7"
    And should see "Total $7"
    When click "Create Order"
    Then should see "Subtotal $7"
    And should see "Total $7"
    When click "Continue"
    Then should see "Subtotal $7"
    And should see "Total $7"
    When click "Continue"
    Then should see "Subtotal $7"
    And should see "Total $10"
    When I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    Then should see "Subtotal $7"
    And should see "Total $10"
    When I check "Payment Terms" on the "Payment" checkout step and press Continue
    Then should see "Subtotal $7"
    And should see "Total $10"
    And fill "Order Review Form" with:
      | PO Number | Order1 |
    And click "Submit Order"
    And I see the "Thank You" page with "Thank You For Your Purchase!" title

    When I hover on "Shopping Cart"
    And click "New Front Shopping List"
    Then should see "Subtotal $5"
    And should see "Total $5"
    When click "Create Order"
    Then should see "Subtotal $5"
    And should see "Total $5"
    When click "Continue"
    Then should see "Subtotal $5"
    And should see "Total $5"
    When click "Continue"
    Then should see "Subtotal $5"
    And should see "Total $8"
    When I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    Then should see "Subtotal $5"
    And should see "Total $8"
    When I check "Payment Terms" on the "Payment" checkout step and press Continue
    Then should see "Subtotal $5"
    And should see "Total $8"
    And fill "Order Review Form" with:
      | PO Number | Order2 |
    And click "Submit Order"
    And I see the "Thank You" page with "Thank You For Your Purchase!" title

    And follow "Account"
    And click "Quotes"
    When click on Q123 in grid
    And click "Accept and Submit to Order"
    Then should see "Subtotal $7"
    And should see "Total $7"
    And follow "Account"
    And click "Quotes"
    When click on Q456 in grid
    And click "Accept and Submit to Order"
    Then should see "Subtotal $5"
    And should see "Total $5"
    And follow "Account"
    When click "Order History"
    Then should see following "PastOrdersGrid" grid:
      | Order Number | Total  |
      | 2            | $8.00  |
      | 1            | $10.00 |

    And I proceed as the admin
    When go to Sales/ Orders
    Then should see following grid:
      | Order Number | Total  |
      | 1            | $10.00 |
      | 2            | $8.00  |
    When click view "Order1" in grid
    Then should see "Subtotal $7"
    And should see "Total $10"
    And go to Sales/ Orders
    When click view "Order2" in grid
    Then should see "Subtotal $5"
    And should see "Total $8"

  Scenario: Set Pricing Precision value to 1 (Half Up)
    Given I proceed as the admin
    And go to System/ Configuration
    And follow "Commerce/Catalog/Pricing" on configuration sidebar
    And fill "PricingConfigurationForm" with:
      | Subtotals Calculation Precision in Sales Documents System | false |
      | Subtotals Calculation Precision in Sales Documents        | 1     |
    And click "Save settings"
    And should see "Configuration saved" flash message
    When go to Products/ Products
    And click view "SKU123" in grid
    And click "Product Prices"
    Then should see following grid:
      | Price List         | Quantity | Unit | Value |
      | Default Price List | 1        | item | 7.45  |
    When click "Edit"
    And click "Product Prices"
    Then Value field should has 7.4500 value
    And click "Cancel"
    When go to Sales/ Orders
    Then should see following grid:
      | Order Number | Total  |
      | 1            | $10.00 |
      | 2            | $8.00  |
    When click view "Order1" in grid
    Then should see "Subtotal $7"
    And should see "Total $10"
    And go to Sales/ Orders
    When click view "Order2" in grid
    Then should see "Subtotal $5"
    And should see "Total $8"

    And I proceed as the customer
    And follow "Account"
    And click "Quotes"
    When click on Q123 in grid
    And click "Accept and Submit to Order"
    Then should see "Subtotal $7.5"
    And should see "Total $7.5"
    And follow "Account"
    And click "Quotes"
    When click on Q456 in grid
    And click "Accept and Submit to Order"
    Then should see "Subtotal $4.5"
    And should see "Total $4.5"

    When click "NewCategory"
    Then should see "Your Price: $7.45 / item" for "Phone" product
    And should see "Listed Price: $7.45 / item" for "Phone" product
    And should see "Your Price: $4.54 / item" for "Light" product
    And should see "Listed Price: $4.54 / item" for "Light" product
    And click "Add to Shopping List" for "Phone" product
    When I hover on "Shopping Cart"
    And I click "Shopping List" on shopping list widget
    Then should see "Subtotal $7.5"
    And should see "Total $7.5"
    When click "Create Order"
    Then should see "Subtotal $7.5"
    And should see "Total $7.5"
    When click "Continue"
    Then should see "Subtotal $7.5"
    And should see "Total $7.5"
    When click "Continue"
    Then should see "Subtotal $7.5"
    And should see "Total $10.5"
    When I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    Then should see "Subtotal $7.5"
    And should see "Total $10.5"
    When I check "Payment Terms" on the "Payment" checkout step and press Continue
    Then should see "Subtotal $7.5"
    And should see "Total $10.5"
    And fill "Order Review Form" with:
      | PO Number | Order3 |
    And click "Submit Order"
    And I see the "Thank You" page with "Thank You For Your Purchase!" title

    When I hover on "Shopping Cart"
    And click "Create New List"
    And should see an "Create New Shopping List popup" element
    And type "New Front Shopping List" in "Shopping List Name"
    And click "Create"
    And should see "New Front Shopping List"
    And click "NewCategory"
    When click "Add to New Front Shopping List" for "Light" product
    And I hover on "Shopping Cart"
    And click "New Front Shopping List"
    Then should see "Subtotal $4.5"
    And should see "Total $4.5"
    When click "Create Order"
    Then should see "Subtotal $4.5"
    And should see "Total $4.5"
    When click "Continue"
    Then should see "Subtotal $4.5"
    And should see "Total $4.5"
    When click "Continue"
    Then should see "Subtotal $4.5"
    And should see "Total $7.5"
    When I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    Then should see "Subtotal $4.5"
    And should see "Total $7.5"
    When I check "Payment Terms" on the "Payment" checkout step and press Continue
    Then should see "Subtotal $4.5"
    And should see "Total $7.5"
    And fill "Order Review Form" with:
      | PO Number | Order4 |
    And click "Submit Order"
    And I see the "Thank You" page with "Thank You For Your Purchase!" title
    And follow "Account"
    When click "Order History"
    Then should see following "PastOrdersGrid" grid:
      | Order Number | Total  |
      | 4            | $7.50  |
      | 3            | $10.50 |
      | 2            | $8.00  |
      | 1            | $10.00 |

    And I proceed as the admin
    When go to Sales/ Orders
    Then should see following grid:
      | Order Number | Total  |
      | 1            | $10.00 |
      | 2            | $8.00  |
      | 3            | $10.50 |
      | 4            | $7.50  |
    When click view "Order3" in grid
    Then should see "Subtotal $7.50"
    And should see "Total $10.50"
    And go to Sales/ Orders
    When click view "Order4" in grid
    Then should see "Subtotal $4.50"
    And should see "Total $7.50"

  Scenario: Set Pricing Precision value to 0 (Half Down)
    Given I proceed as the admin
    And go to System/ Configuration
    And follow "Commerce/Catalog/Pricing" on configuration sidebar
    And fill "PricingConfigurationForm" with:
      | Subtotals Calculation Precision in Sales Documents System | false     |
      | Subtotals Calculation Precision in Sales Documents        | 0         |
      | Pricing Rounding Type System                              | false     |
      | Pricing Rounding Type                                     | Half Down |
    And click "Save settings"
    And should see "Configuration saved" flash message
    When go to Products/ Products
    And click view "SKU123" in grid
    And click "Product Prices"
    Then should see following grid:
      | Price List         | Quantity | Unit | Value |
      | Default Price List | 1        | item | 7.45  |
    And I proceed as the customer
    And follow "Account"
    And click "Quotes"
    When click on Q123 in grid
    And click "Accept and Submit to Order"
    Then should see "Subtotal $7"
    And should see "Total $7"
    And follow "Account"
    And click "Quotes"
    When click on Q456 in grid
    And click "Accept and Submit to Order"
    Then should see "Subtotal $5"
    And should see "Total $5"
    And follow "Account"
    And click "Quotes"
    When click on Q789 in grid
    And click "Accept and Submit to Order"
    Then should see "Subtotal $8"
    And should see "Total $8"
    And follow "Account"
    And click "Quotes"
    When click on Q321 in grid
    And click "Accept and Submit to Order"
    Then should see "Subtotal $4"
    And should see "Total $4"


  Scenario: Set Pricing Precision value to 0 (Ceil)
    Given I proceed as the admin
    And go to System/ Configuration
    And follow "Commerce/Catalog/Pricing" on configuration sidebar
    And fill "PricingConfigurationForm" with:
      | Subtotals Calculation Precision in Sales Documents System | false |
      | Subtotals Calculation Precision in Sales Documents        | 0     |
      | Pricing Rounding Type System                              | false |
      | Pricing Rounding Type                                     | Ceil  |
    And click "Save settings"
    And should see "Configuration saved" flash message
    When go to Products/ Products
    And click view "SKU123" in grid
    And click "Product Prices"
    Then should see following grid:
      | Price List         | Quantity | Unit | Value |
      | Default Price List | 1        | item | 7.45  |
    And I proceed as the customer
    And follow "Account"
    And click "Quotes"
    When click on Q123 in grid
    And click "Accept and Submit to Order"
    Then should see "Subtotal $8"
    And should see "Total $8"
    And follow "Account"
    And click "Quotes"
    When click on Q456 in grid
    And click "Accept and Submit to Order"
    Then should see "Subtotal $5"
    And should see "Total $5"
    And follow "Account"
    And click "Quotes"
    When click on Q789 in grid
    And click "Accept and Submit to Order"
    Then should see "Subtotal $8"
    And should see "Total $8"

  Scenario: Set Pricing Precision value to 0 (Floor)
    Given I proceed as the admin
    And go to System/ Configuration
    And follow "Commerce/Catalog/Pricing" on configuration sidebar
    And fill "PricingConfigurationForm" with:
      | Subtotals Calculation Precision in Sales Documents System | false |
      | Subtotals Calculation Precision in Sales Documents        | 0     |
      | Pricing Rounding Type System                              | false |
      | Pricing Rounding Type                                     | Floor |
    And click "Save settings"
    And should see "Configuration saved" flash message
    When go to Products/ Products
    And click view "SKU123" in grid
    And click "Product Prices"
    Then should see following grid:
      | Price List         | Quantity | Unit | Value |
      | Default Price List | 1        | item | 7.45  |
    And I proceed as the customer
    And follow "Account"
    And click "Quotes"
    When click on Q123 in grid
    And click "Accept and Submit to Order"
    Then should see "Subtotal $7"
    And should see "Total $7"
    And follow "Account"
    And click "Quotes"
    When click on Q456 in grid
    And click "Accept and Submit to Order"
    Then should see "Subtotal $4"
    And should see "Total $4"
    And follow "Account"
    And click "Quotes"
    When click on Q789 in grid
    And click "Accept and Submit to Order"
    Then should see "Subtotal $7"
    And should see "Total $7"

  Scenario: Set Pricing Precision value to 0 (Half Even)
    Given I proceed as the admin
    And go to System/ Configuration
    And follow "Commerce/Catalog/Pricing" on configuration sidebar
    And fill "PricingConfigurationForm" with:
      | Subtotals Calculation Precision in Sales Documents System | false     |
      | Subtotals Calculation Precision in Sales Documents        | 0         |
      | Pricing Rounding Type System                              | false     |
      | Pricing Rounding Type                                     | Half Even |
    And click "Save settings"
    And should see "Configuration saved" flash message
    When go to Products/ Products
    And click view "SKU123" in grid
    And click "Product Prices"
    Then should see following grid:
      | Price List         | Quantity | Unit | Value |
      | Default Price List | 1        | item | 7.45  |
    And I proceed as the customer
    And follow "Account"
    And click "Quotes"
    When click on Q321 in grid
    And click "Accept and Submit to Order"
    Then should see "Subtotal $4"
    And should see "Total $4"
    And follow "Account"
    And click "Quotes"
    When click on Q654 in grid
    And click "Accept and Submit to Order"
    Then should see "Subtotal $4"
    And should see "Total $4"

  Scenario: Set Pricing Precision value to 2 (Half Up)
    Given I proceed as the admin
    And go to System/ Configuration
    And follow "Commerce/Catalog/Pricing" on configuration sidebar
    When fill "PricingConfigurationForm" with:
      | Subtotals Calculation Precision in Sales Documents System | false   |
      | Subtotals Calculation Precision in Sales Documents        | 2       |
      | Pricing Rounding Type System                              | false   |
      | Pricing Rounding Type                                     | Half Up |
    And I save form
    Then I should see "Configuration saved" flash message
    When go to Products / Products
    And click edit "SKU789" in grid
    And click "Product Prices"
    And click "Add Product Price"
    And fill "Product Price Form" with:
      | Price List | Default Price List |
      | Quantity   | 1                  |
      | Value      | 33.495             |
      | Currency   | $                  |
    And I save and close form
    Then I should see "Product has been saved" flash message

    When I proceed as the customer
    And type "SKU789" in "search"
    And I click "Search Button"
    And I click "Add to Shopping List" for "SKU789" product
    And I hover on "Shopping Cart"
    And I click "Shopping List" on shopping list widget
    And I scroll to top
    And I wait line items are initialized
    And I type "3" in "Shopping List Line Item 1 Quantity"
    And I scroll to "Create Order"
    Then I should see "Record has been successfully updated" flash message
    When I scroll to top
    And I click "Create Order"
    Then Checkout "Order Summary Products Grid" should contain products:
      | TV | 3 | items | $33.50 | $100.49 |
    And I should see Checkout Totals with data:
      | Subtotal | $100.49 |
