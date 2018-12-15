@ticket-BB-10008
@fixture-OroPricingBundle:PriceListsWithPrices.yml

Feature: Product prices grid sorting at price list view page
  In order to have ability to observe product prices in price list
  As an Administrator
  I want to see & sort datagrid with product prices at price list view page

  Scenario: Check product prices at view page of Price List
    Given I login as administrator
    And I go to Sales/ Price Lists

    When I click view First Price List in grid
    Then I should see following "Price list Product prices Grid" grid:
      | Product SKU | Product name | Quantity | Unit  | Value | Currency | Type   |
      | PSKU1       | Product 1    | 5        | item  | 15.00 | USD      | Manual |
      | PSKU2       | Product 2    | 10       | piece | 30.00 | USD      | Manual |

    When I sort "Price list Product prices Grid" by Unit
    Then I should see following "Price list Product prices Grid" grid:
      | Product SKU | Product name | Quantity | Unit  | Value   | Currency | Type   |
      | PSKU1       | Product 1    | 5        | item  | 15.00 | USD      | Manual |
      | PSKU2       | Product 2    | 10       | piece | 30.00 | USD      | Manual |

    When I sort "Price list Product prices Grid" by Unit again
    Then I should see following "Price list Product prices Grid" grid:
      | Product SKU | Product name | Quantity | Unit  | Value | Currency | Type   |
      | PSKU2       | Product 2    | 10       | piece | 30.00 | USD      | Manual |
      | PSKU1       | Product 1    | 5        | item  | 15.00 | USD      | Manual |
