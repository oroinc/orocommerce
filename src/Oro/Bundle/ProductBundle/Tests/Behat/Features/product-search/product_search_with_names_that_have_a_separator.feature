@regression
@ticket-BB-20268
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
@fixture-OroProductBundle:product_search/products_with_specified_names.yml

Feature: Product search with names that have a separator
  In order to be able to search for products on frontstore with names that have a separator
  As a buyer
  I search for products using name with specific separator and see that the products are in the product grid

  Scenario: Search product
    Given I signed in as AmandaRCole@example.org on the store frontend
    And go to the homepage
    When I type "87-13" in "search"
    And click "Search Button"
    Then number of records in "Product Frontend Grid" should be 1
    And I should see "87-13" product
