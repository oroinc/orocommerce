@ticket-BAP-20454
@regression
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
@fixture-OroCheckoutBundle:quick_order_form_products_with_decimal_prices.yml

Feature: Rounding of prices for products in the quick order form
  In order to be able to work with decimal prices in the quick order form
  As a customer
  I add a product with a decimal price and quantity and check that the price is accurate and correctly rounded

  Scenario: Add product to quick order form and check total price
    Given I login as AmandaRCole@example.org buyer
    And click "Quick Order Form"
    When I fill "Quick Order Form" with:
      | SKU1 | SKU |
      | QTY1 | 5   |
    And I wait for products to load
    And type "5" in "Quick Order Form > QTY1"
    And click on empty space
    Then "Product" product should has "$49.9975" value in price field
