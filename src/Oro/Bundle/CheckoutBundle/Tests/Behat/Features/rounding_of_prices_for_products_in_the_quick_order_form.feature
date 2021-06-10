@ticket-BAP-20454
@regression
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
@fixture-OroCheckoutBundle:quick_order_form_products_with_decimal_prices.yml

Feature: Rounding of prices for products in the quick order and rfq form
  In order to be able to work with decimal prices
  As a customer
  I add a product with a decimal price and quantity and check that the price is accurate and correctly rounded

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Add product to quick order form, rfq and check price without dynamic precision
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    And click "Quick Order Form"
    When I fill "QuickAddForm" with:
      | SKU1 | SKU |
      | QTY1 | 5   |
    And I wait for products to load
    And type "5" in "Quick Order Form > QTY1"
    And click on empty space
    Then "Product" product should has "$50.00" value in price field
    When I click "Get Quote"
    Then I should see "Listed Price: $10.00"

  Scenario: Enable dynamic precision
    Given I proceed as the Admin
    And login as administrator
    And I go to System/Configuration
    And I follow "Commerce/Catalog/Pricing" on configuration sidebar
    When fill "PricingConfigurationForm" with:
      | Allow To Round Displayed Prices And Amounts System | false |
      | Allow To Round Displayed Prices And Amounts        | false |
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Add product to quick order form, rfq and check price without dynamic precision
    Given I proceed as the Buyer
    And click "Quick Order Form"
    When I fill "QuickAddForm" with:
      | SKU1 | SKU |
      | QTY1 | 5   |
    And I wait for products to load
    And type "5" in "Quick Order Form > QTY1"
    And click on empty space
    Then "Product" product should has "$49.9975" value in price field
    When I click "Get Quote"
    Then I should see "Listed Price: $9.9995"
