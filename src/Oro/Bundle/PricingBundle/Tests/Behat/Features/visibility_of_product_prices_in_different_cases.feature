@regression
@fixture-OroPricingBundle:ProductPrices.yml
Feature: Visibility of product prices in different cases
  In order to have ability to filter products by product prices on the store frontend
  As an Buyer
  I want to see and manage product prices filter`s state on product grid page

  Scenario: Empty product prices for non authorized user
    Given I am on "/product"
    Then I should not see a "Product Price Listed" element
    And I should see that "Product Price Container" contains "Price not available"
    And I should not see a "Product Price Main" element
    And I should not see a "Product Price Hint" element

  Scenario: Empty product prices for authorized user
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I am on "/product"
    Then I should not see a "Product Price Listed" element
    And I should see that "Product Price Container" contains "Price not available"
    And I should not see a "Product Price Main" element
    And I should not see a "Product Price Hint" element

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

    Then I am on "/product"
    And I should see that "Product Price Listed" contains "$100.00"
    When I hover on "Product Price Hint"
    Then I should see a "Product Price Popover" element
    And I should see that "Product Price Popover" contains "$100.00"

  Scenario: Resetting Price Filter
    Given I am on "/product"
    And I filter Price as equals "12,00"
    And I should see filter hints in frontend grid:
      | Price: equals 1,200.00 / ea |
    When I reset "Price" filter on grid "ProductFrontendGrid"
    And click on "Open Filters Panel Button"
    Then filter "Price" should have selected "between" type
