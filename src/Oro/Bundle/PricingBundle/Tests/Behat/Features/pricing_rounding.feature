@regression
@ticket-BB-13406
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroPricingBundle:PricingRounding.yml
@fixture-OroPricingBundle:PricingRoundingInventoryLevel.yml
@fixture-OroPricingBundle:PricingRoundingQuote.yml

Feature: Pricing rounding
  In order to check pricing rounding in shopping list/order/rfq/quote
  As an Administrator
  I create shopping list/order/rfq/quote and change Pricing Precision

  Scenario: Create different window session
    Given sessions active:
      | admin    |first_session |
      | customer |second_session|

  Scenario: Create demo data
    Given I proceed as the admin
    And login as administrator
    And go to Products/ Products
    And click edit "SKU123" in grid
    And click "Product Prices"
    And click "Add Product Price"
    And fill "Product Price Form" with:
      |Price List|Default Price List|
      |Quantity  |1   |
      |Value     |7.45|
      |Currency  |$   |
    And save and close form
    And go to Products/ Products
    And click edit "SKU456" in grid
    And click "Product Prices"
    And click "Add Product Price"
    And fill "Product Price Form" with:
      |Price List|Default Price List|
      |Quantity  |1   |
      |Value     |4.54|
      |Currency  |$   |
    And save and close form

  Scenario: Default system stage
    Given I proceed as the admin
    And go to System/ Configuration
    When follow "Commerce/Catalog/Pricing" on configuration sidebar
    And Pricing Precision field should has 4 value
    And I proceed as the customer
    And I signed in as AmandaRCole@example.org on the store frontend
    When click "NewCategory"
    Then should see "Your Price: $7.45 / item" for "Phone" product
    And should see "Listed Price: $7.45 / item" for "Phone" product
    And should see "Your Price: $4.54 / item" for "Light" product
    And should see "Listed Price: $4.54 / item" for "Light" product
    And click "Add to Shopping List" for "Phone" product
    When I hover on "Shopping Cart"
    And click "Shopping list"
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

  Scenario: Set Pricing Precision value to 0
    Given I proceed as the admin
    And fill "PricingConfigurationForm" with:
      |Pricing Precision System|false|
      |Pricing Precision       |0    |
    And click "Save settings"
    And I proceed as the customer
    When I hover on "Shopping Cart"
    And click "Shopping list"
    Then should see "Subtotal $7"
    And should see "Total $7"
    When I hover on "Shopping Cart"
    And click "New Front Shopping List"
    Then should see "Subtotal $5"
    And should see "Total $5"

    And I wait for action
