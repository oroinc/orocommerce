@fixture-Products.yml
Feature: Quick order form
  In order to provide customers with ability to quickly start an order
  As customer
  I need to be able to enter products' skus and quantities and start checkout

  Scenario: "Quick order form 1A" > CREATE ORDER WHEN SHIPPING METHOD IS DISABLED. PRIORITY - MAJOR
    Given I login as AmandaRCole@example.org buyer
    When I am on quick order form page
    And I add product "PSKU1" with quantity "3" to quick order form
    And click create order button
    Then I should see flash error messages
    And quick order form contains product with sku "PSKU1" and quantity "3"
