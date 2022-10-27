@regression
@pricing-storage-combined
@ticket-BB-18216
@fixture-OroPricingBundle:ProductPrices.yml

Feature: CPL activation rule processing
  In order to have ability to manage Combined Price Lists schedule
  As an Administrator
  I want to be sure that Combined Price Lists with schedule are calculated only within configured time offset

  Scenario: Add new price to product price
    Given I login as administrator
    When I go to Products/Products
    And click Edit PSKU1 in grid
    And click "AddPrice"
    And fill "ProductPriceForm" with:
      | Price List | Default Price List |
      | Quantity   | 1                  |
      | Value      | 7                  |
      | Currency   | $                  |
    And I submit form
    Then I should see "Product has been saved" flash message

  Scenario: Add schedule and rule to price list
    Given I go to Sales/ Price Lists
    And click edit "priceListForWebsite" in grid
    And I fill "Price List Form" with:
      | Activate At (first) | <Date:today +2 month> |
      | Rule                | product.id > 0        |
    And I click "Add Price Calculation Rules"
    And I click "Enter expression unit"
    And I click "Enter expression currency"
    And I fill "Price Calculation Rules Form" with:
      | Price for quantity | pricelist[1].prices.quantity * 10 |
      | Price Unit         | pricelist[1].prices.unit          |
      | Price Currency     | pricelist[1].prices.currency      |
      | Calculate As       | pricelist[1].prices.value * 1.2   |
      | Priority           | 1                                 |
    And I save and close form
    Then I should see "Price List has been saved" flash message

  Scenario: Assign priceListForWebsite to customer
    Given I go to Customers/Customers
    And click Edit first customer in grid
    And I choose Price List "priceListForWebsite" in 1 row
    And I submit form
    Then I should see "Customer has been saved" flash message
    And There are 0 prices in combined price list:
      | priceListForWebsite |
      | Default Price List  |

  Scenario: Update rule for price list
    Given I go to Sales/ Price Lists
    And click edit "priceListForWebsite" in grid
    And I fill "Price Calculation Rules Form" with:
      | Calculate As | pricelist[1].prices.value * 1.1 |
    And I save and close form
    Then I should see "Price List has been saved" flash message
    And There are 0 prices in combined price list:
      | priceListForWebsite |
      | Default Price List  |

  Scenario: Update activation date for price list to enable price list
    Given I click "Edit"
    And I fill "Price List Form" with:
      | Activate At (first) | <Date:today -2 month> |
    And I save and close form
    Then I should see "Price List has been saved" flash message
    When I click "Recalculate"
    Then I should see "Product Prices have been successfully recalculated" flash message
    And There are 2 prices in combined price list:
      | priceListForWebsite |
      | Default Price List  |
