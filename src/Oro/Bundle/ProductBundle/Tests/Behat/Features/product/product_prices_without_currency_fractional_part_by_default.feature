@ticket-BB-14800
@regression
@fixture-OroProductBundle:single_product.yml

Feature: Product prices without currency fractional part by default
  In order to use correct fractional prices in currencies without fractional digits by default
  As an Administrator
  I add a currency in which the fractional digits is not exists by default and check price view on the frontend
  and see that the fractional digits part exists and it not rounded or trimmed.

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Add 'Japanese Yen' to allowed currencies
    Given I proceed as the Admin
    And login as administrator
    And go to System/ Configuration
    And follow "System Configuration/General Setup/Currency" on configuration sidebar
    And fill "Currency Form" with:
      | Allowed Currencies | Japanese Yen (JPY) |
    And click "Add"
    And type "1" in "Rate From 1"
    And click on empty space
    And type "1" in "Rate To 1"
    And click on empty space
    When I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Enable 'Japanese Yen' currency
    Given I go to System/Configuration
    And follow "Commerce/Catalog/Pricing" on configuration sidebar
    And fill "Pricing Form" with:
      | Enabled Currencies System | false              |
      | Enabled Currencies        | [Japanese Yen (¥)] |
      | Default Currency System   | false              |
      | Default Currency          | Japanese Yen (¥)   |
    When I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Add 'Japanese Yen' to 'Default Price List'
    Given I go to Sales/ Price Lists
    And click edit "Default Price List" in grid
    And fill form with:
      | Currencies | [Japanese Yen (¥)] |
    When I save and close form
    Then I should see "Price List has been saved" flash message

  Scenario: Add product prices
    When download "Product Price" Data Template file
    And I fill template with data:
      | Product SKU | Quantity | Unit Code | Price  | Currency |
      | PSKU1       | 1        | each      | 1      | JPY      |
      | PSKU1       | 2        | each      | 1.6    | JPY      |
      | PSKU1       | 3        | each      | 1.67   | JPY      |
      | PSKU1       | 4        | each      | 1.678  | JPY      |
      | PSKU1       | 5        | each      | 1.6789 | JPY      |
    And I import file
    And reload the page
    Then number of records should be 5

  Scenario: Check pricing on storefront
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And type "PSKU1" in "search"
    When I click "Search Button"
    And click "View Details" for "PSKU1" product
    Then I should see "1 ¥1"
    And should see "2 ¥1.6"
    And should see "3 ¥1.67"
    And should see "4 ¥1.678"
    And should see "5 ¥1.6789"

  Scenario: Check Quick Order Form
    Given click "Quick Order Form"
    And fill "Quick Order Form" with:
      | SKU1 | PSKU1 |
    And I wait for products to load
    When type "1" in "Quick Order Form > QTY1"
    And click on empty space
    Then "PSKU1" product should has "¥1" value in price field
    When type "5" in "Quick Order Form > QTY1"
    And click on empty space
    Then "PSKU1" product should has "¥8.3945" value in price field
