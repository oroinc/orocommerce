@regression
@ticket-BB-27016
@fixture-OroPricingBundle:ProductPricesGridSorting.yml

Feature: Product prices grid sorting at product view page
  In order to have ability to observe product prices on product view page
  As an Administrator
  I want to sort datagrid with product prices at product view page

  Scenario: Sort product prices grid by Unit column
    Given I login as administrator
    And I go to Products/ Products
    And I click view "PSKU1" in grid

    When I sort "ProductPricesGrid" by Unit
    Then I should see following "ProductPricesGrid" grid:
      | Price List         | Quantity | Unit  | Value | Currency |
      | Default Price List | 5        | item  | 15.00 | USD      |
      | Default Price List | 10       | piece | 30.00 | USD      |

    When I sort "ProductPricesGrid" by Unit again
    Then I should see following "ProductPricesGrid" grid:
      | Price List         | Quantity | Unit  | Value | Currency |
      | Default Price List | 10       | piece | 30.00 | USD      |
      | Default Price List | 5        | item  | 15.00 | USD      |
