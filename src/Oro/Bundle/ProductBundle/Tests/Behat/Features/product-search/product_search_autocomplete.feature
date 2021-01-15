@regression
@ticket-BB-20073
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
@fixture-OroProductBundle:product_search/products.yml

Feature: Product search autocomplete
  In order to be able to search for products on frontstore
  As a Buyer
  I search for products through the main product search functionality

  Scenario: Feature Background
    Given I enable the existing localizations
    And I signed in as AmandaRCole@example.org on the store frontend
    And I go to the homepage

  Scenario: Check the search autocomplete when no products found
    When I type "Search string" in "search"
    And I should see an "Search Autocomplete" element
    Then I should see "No products were found to match your search" in the "Search Autocomplete No Found" element
    And I should not see an "Search Autocomplete Item" element
    And I should not see an "Search Autocomplete Submit" element

  Scenario: Check the search autocomplete when products found
    When I type "Product" in "search"
    Then I should see an "Search Autocomplete" element
    And I should see "Product" in the "Search Autocomplete Highlight" element
    And I should see an "Search Autocomplete Product Image" element
    And I should see "In Stock" in the "Search Autocomplete Inventory Status" element
    And I should see "$10.00" in the "Search Autocomplete Product" element
    And I should see an "Search Autocomplete Submit" element
    And I should see "See All 3 Results" in the "Search Autocomplete Submit" element

  Scenario: Check the search grid after following by the search autocomplete link
    When I click "Search Autocomplete Submit"
    And number of records in "Product Frontend Grid" should be 3
    And I type "PSKU3" in "search"
    And I click "Search Autocomplete Product"
    Then I should see "All Products / Product3"
