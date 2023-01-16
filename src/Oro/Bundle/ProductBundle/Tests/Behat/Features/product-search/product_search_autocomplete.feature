@regression
@elasticsearch
@ticket-BB-20073
@feature-BAP-19790
@fixture-OroProductBundle:product_search/products.yml

Feature: Product search autocomplete
  In order to be able to search for products on storefront
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
    And I should see an "Search Autocomplete Category Image" element
    And I should see "Product" in the "Search Autocomplete Category Head" element
    And I should see "Retail Supplies" in the "Search Autocomplete Category Body" element
    And I should see an "Search Autocomplete Product Image" element
    And I should see picture "Search Autocomplete Product Picture" element
    And I should see "In Stock" in the "Search Autocomplete Inventory Status" element
    And I should see "$10.00" in the "Search Autocomplete Product" element
    And I should see an "Search Autocomplete Submit" element
    And I should see "See All 3 Results" in the "Search Autocomplete Submit" element

  Scenario: Check the search autocomplete navigation by ArrowDown key
    When I press "ArrowDown" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "Retail Supplies" inside "Search Autocomplete" element
    When I press "ArrowDown" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "Product2" inside "Search Autocomplete" element
    When I press "ArrowDown" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "Product3" inside "Search Autocomplete" element
    When I press "ArrowDown" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "Product1" inside "Search Autocomplete" element
    When I press "ArrowDown" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "See All 3 Results" inside "Search Autocomplete" element
    When I press "ArrowDown" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "Retail Supplies" inside "Search Autocomplete" element
    When I press "Esc" key on "Search Form Field" element
    Then I should not see an "Search Autocomplete" element
    When I press "ArrowDown" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "Retail Supplies" inside "Search Autocomplete" element
    When I press "ArrowDown" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "Product2" inside "Search Autocomplete" element
    When I press "Enter" key on "Search Form Field" element
    And I should see "All Products / Product2"

  Scenario: Check the search autocomplete navigation by ArrowUp key
    When I type "Product" in "search"
    And I should see an "Search Autocomplete" element
    And I press "ArrowUp" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "See All 3 Results" inside "Search Autocomplete" element
    When I press "ArrowUp" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "Product1" inside "Search Autocomplete" element
    When I press "ArrowUp" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "Product3" inside "Search Autocomplete" element
    When I press "ArrowUp" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "Product2" inside "Search Autocomplete" element
    When I press "ArrowUp" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "Retail Supplies" inside "Search Autocomplete" element
    When I press "ArrowUp" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "See All 3 Results" inside "Search Autocomplete" element
    When I press "Esc" key on "Search Form Field" element
    Then I should not see an "Search Autocomplete" element
    When I press "ArrowUp" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "See All 3 Results" inside "Search Autocomplete" element
    When I press "Enter" key on "Search Form Field" element
    Then number of records in "Product Frontend Grid" should be 3

  Scenario: Check the search grid after following by the search autocomplete link
    When I type "Product" in "search"
    And I should see an "Search Autocomplete" element
    Then I click "Search Autocomplete Submit"
    And number of records in "Product Frontend Grid" should be 3
    And I type "PSKU3" in "search"
    And I click "Search Autocomplete Product"
    Then I should see "All Products / Product3"
