@regression
@ticket-BB-27513
@fixture-OroPricingBundle:ProductPrices.yml

Feature: Check CPL after Prices Import

  Scenario: Create two session
    Given I login as AmandaRCole@example.org the "Buyer" at "first_session" session
    And I login as administrator and use in "second_session" as "Admin"

  Scenario: Import new prices
    Given I operate as the Admin
    When I go to Customers/Customers
    And click Edit first customer in grid
    And fill "Customer Form" with:
      | Price List | Default Price List |
    And I submit form
    Then I should see "Customer has been saved" flash message

    And I go to Sales/Price Lists
    And click View Default Price List in grid
    And I download "ProductPrice" Data Template file
    And I fill template with data:
      | Product SKU | Quantity | Unit Code | Price | Currency |
      | PSKU1       | 1        | item      | 11    | USD      |
      | PSKU1       | 2        | item      | 21    | USD      |
      | PSKU1       | 3        | item      | 31    | USD      |
    Then I import file

    When I continue as the Buyer
    And I am on the homepage
    And I click "NewCategory"
    And I click "Product 1"
    And I should see "$11.00"

  Scenario: Update the price for PSKU1 via back-office UI
    Given I operate as the Admin
    When I go to Sales/Price Lists
    And click View Default Price List in grid
    And click edit 11.00 in "Price list Product prices Grid"
    And fill "Update Product Price Form" with:
      | Price | 19 |
    And I click "Save"
    Then I should see "Product Price has been added" flash message

  Scenario: Check updated price is reflected in Price Calculation Details
    When I go to Sales/Price Calculation Details
    And I filter SKU as Contains "PSKU1"
    And fill "Price Calculation Details Grid Sidebar" with:
      | Website  | Default        |
      | Customer | first customer |
    And click on PSKU1 in grid
    Then I should see next prices for "Customer Prices":
      | Item (USD) |
      | 1 $19.00   |
      | 2 $21.00   |
      | 3 $31.00   |
