@regression
@fixture-OroPricingBundle:ProductPrices.yml
Feature: Visibility of product prices in different cases
  Scenario: Empty product prices for non authorized user
    Given I am on "/product"
    Then I should not see a "Product Price Listed" element
    And I should not see a "Product Price Main" element

  Scenario: Empty product prices for authorized user
    Given I login as AmandaRCole@example.org the "Buyer" at "first_session" session
    And I am on "/product"
    Then I should see that "Product Price Hint" contains "Price not available"
    And I should not see a "Product Price Main" element
    When I click on "Product Price Hint"
    Then I should not see a "Product Price Popover" element

  Scenario: Add prices for already created product and check prices visibility
    Given I login as administrator
    And I go to Products/ Products
    And click edit "PSKU1" in grid
    And click "AddPrice"
    And fill "ProductPriceForm" with:
      | Price List     | Default Price List |
      | Quantity       | 1                  |
      | Value          | 100                |
      | Currency       | $                  |
    And I submit form
    When I continue as the Buyer
    And I am on "/product"
    And I should see that "Product Price Listed" contains "$100.00"
    When I hover on "Product Price Hint"
    Then I should see a "Product Price Popover" element
    And I should see that "Product Price Popover" contains "$100.00"
