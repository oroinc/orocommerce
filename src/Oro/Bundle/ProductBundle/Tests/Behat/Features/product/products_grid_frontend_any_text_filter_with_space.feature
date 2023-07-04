@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
@fixture-OroProductBundle:products_grid_frontend.yml
@ticket-BB-22494
@elasticsearch

Feature: Products Grid Frontend any text filter with space
  In order to ensure frontend products grid works correctly
  As a buyer
  I check any text filter are working when entering one space.

  Scenario: Visit product list page and fill space in any text filter
    Given I signed in as AmandaRCole@example.org on the store frontend
    When I open page number 1 of frontend product grid
    And number of records in "Product Frontend Grid" should be 19
    Then I filter "Any Text" as contains " "
    And I should not see "There was an error performing the requested operation. Please try again or contact us for assistance."
    And number of records in "Product Frontend Grid" should be 0

  Scenario: Visit product list page and fill does not contains space in any text filter
    When I open page number 1 of frontend product grid
    And number of records in "Product Frontend Grid" should be 19
    Then I filter "Any Text" as does not contain " "
    And I should not see "There was an error performing the requested operation. Please try again or contact us for assistance."
    And number of records in "Product Frontend Grid" should be 19

  Scenario: Visit product list page and fill contains space in filter by sku
    When I open page number 1 of frontend product grid
    And number of records in "Product Frontend Grid" should be 19
    Then I filter SKU as contains " "
    And I should not see "There was an error performing the requested operation. Please try again or contact us for assistance."
    And number of records in "Product Frontend Grid" should be 19

  Scenario: Visit product list page and fill does not contains space in filter by sku
    When I open page number 1 of frontend product grid
    And number of records in "Product Frontend Grid" should be 19
    Then I filter SKU as does not contain " "
    And I should not see "There was an error performing the requested operation. Please try again or contact us for assistance."
    And number of records in "Product Frontend Grid" should be 0

  Scenario: Visit product list page and fill is equals space in filter by sku
    When I open page number 1 of frontend product grid
    And number of records in "Product Frontend Grid" should be 19
    Then I filter SKU as is equal " "
    And I should not see "There was an error performing the requested operation. Please try again or contact us for assistance."
    And number of records in "Product Frontend Grid" should be 0

  Scenario: Visit product list page and fill contains space in filter by sku
    When I open page number 1 of frontend product grid
    And number of records in "Product Frontend Grid" should be 19
    Then I filter Name as contains " "
    And I should not see "There was an error performing the requested operation. Please try again or contact us for assistance."
    And number of records in "Product Frontend Grid" should be 19

  Scenario: Visit product list page and fill does not contains space in filter by sku
    When I open page number 1 of frontend product grid
    And number of records in "Product Frontend Grid" should be 19
    Then I filter Name as does not contain " "
    And I should not see "There was an error performing the requested operation. Please try again or contact us for assistance."
    And number of records in "Product Frontend Grid" should be 0

  Scenario: Visit product list page and fill is equals space in filter by sku
    When I open page number 1 of frontend product grid
    And number of records in "Product Frontend Grid" should be 19
    Then I filter Name as is equal " "
    And I should not see "There was an error performing the requested operation. Please try again or contact us for assistance."
    And number of records in "Product Frontend Grid" should be 0
