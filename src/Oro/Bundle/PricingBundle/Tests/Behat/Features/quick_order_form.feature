@regression
@ticket-BB-19138
@ticket-BB-20670
@ticket-BB-20731
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroCheckoutBundle:CheckoutCustomerFixture.yml
@fixture-OroCheckoutBundle:Products_quick_order_form_ce.yml

Feature: Quick order form
  In order to provide customers with ability to quickly start an order
  As customer
  I need to be able to enter products' skus and quantities and start checkout

  Scenario: Check price changed on SKU changes
    Given I login as AmandaRCole@example.org buyer
    When I click "Quick Order Form"
    And I fill "QuickAddForm" with:
      | SKU1 | psku1 |
    Then "PSKU1" product should has "$45.00" value in price field
    When I fill "QuickAddForm" with:
      | SKU1 | PSKUwithlowercase |
    Then "PSKUwithlowercase" product should has "N/A" value in price field

    When I click "Create Order"
    Then I should see "Cannot create order because Shopping List has no items with price"

    When I click "Get Quote"
    Then I should see "REQUEST A QUOTE"
    And I should see "Product4"
    And I should see "QTY: 1 item"
    And I should see "Target Price $0.00"
    And I should see "Listed Price: N/A"

  Scenario: Check products markup result in autocomplete drop down list
    When I click "Quick Order Form"
    And I type "class" in "QuickOrderFirstSkuField"
    And I wait for products to load
    Then I should see exact "classPSKU - Product6Class" in the "QuickOrderFirstSkuFieldTypeahead" element

  Scenario: Check products subtotal calculation with minimal tier of quantity
    Given I click "Quick Order Form"
    And I fill "QuickAddForm" with:
      | SKU1 | classPSKU |
    And I wait for products to load
    When I type "3" in "Quick Order Form > QTY1"
    Then "QuickAddForm" must contains values:
      | SKU1      | classPSKU - Product6Class |
      | QTY1      | 3                         |
      | SUBTOTAL1 | N/A                       |
    When I type "13" in "Quick Order Form > QTY1"
    Then "QuickAddForm" must contains values:
      | SKU1      | classPSKU - Product6Class |
      | QTY1      | 13                        |
      | SUBTOTAL1 | $130.00                   |
