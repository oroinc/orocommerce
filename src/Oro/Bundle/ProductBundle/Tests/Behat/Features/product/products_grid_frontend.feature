@regression
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
@fixture-OroProductBundle:products_grid_frontend.yml
@ticket-BAP-17648

Feature: Products Grid Frontend
  In order to ensure frontend products grid works correctly
  As a buyer
  I check filters are working and sorting is working as designed.

  Scenario: Visit page with incorrect number
    Given I signed in as AmandaRCole@example.org on the store frontend
    When open page number test of frontend product grid
    Then should not see "There was an error performing the requested operation. Please try again or contact us for assistance." flash message
    And I should see "PSKU1"
    And number of records in "Product Frontend Grid" should be 19

  Scenario: Check Fulltext Filter
    Given number of records in "Product Frontend Grid" should be 19
    When I filter "Any Text" as contains "PSKU3"
    Then I should see "PSKU3"
    And I should not see "PSKU1"
    And I click "Clear All Filters"

  Scenario: Check Price Filter
    Given number of records in "Product Frontend Grid" should be 19
    And I set range filter "Price" as min value "10" and max value "10" use "each" unit
    Then I should see "PSKU1"
    And I should not see "PSKU5"
    And I click "Clear All Filters"

  Scenario: Check Name Filter
    Given number of records in "Product Frontend Grid" should be 19
    When I filter "Name" as does not contain "Product 19"
    Then I should not see "PSKU19"
    And I should see "PSKU1"

  Scenario: Check Filter Applies After Different Actions
    Given number of records in "Product Frontend Grid" should be 18
    When I select 10 from per page list dropdown in "Product Frontend Grid"
    Then I should not see "PSKU19"
    And number of records in "Product Frontend Grid" should be 18
    When I go to next page in "Product Frontend Grid"
    Then I should not see "PSKU19"
    And number of records in "Product Frontend Grid" should be 18
    When I reload the page
    Then I should not see "PSKU19"
    And number of records in "Product Frontend Grid" should be 18
    When I hide filter "Name" in "Product Frontend Grid" frontend grid
    And I select 25 from per page list dropdown in "Product Frontend Grid"
    Then I should see "PSKU19"
    And number of records in "Product Frontend Grid" should be 19

  Scenario: Check Price Sorter
    Given I am on "/product"
    When I sort frontend grid "Product Frontend Grid" by "Price (Low to High)"
    Then PSKU1 must be first record in "Product Frontend Grid"
    And PSKU2 must be second record in "Product Frontend Grid"
    When I sort frontend grid "Product Frontend Grid" by "Price (High to Low)"
    Then PSKU20 must be first record in "Product Frontend Grid"
    And PSKU19 must be second record in "Product Frontend Grid"

  Scenario: Check Name Sorter
    Given I am on "/product"
    When I sort frontend grid "Product Frontend Grid" by "Name (Low to High)"
    Then PSKU1 must be first record in "Product Frontend Grid"
    And PSKU10 must be second record in "Product Frontend Grid"
    When I sort frontend grid "Product Frontend Grid" by "Name (High to Low)"
    Then PSKU9 must be first record in "Product Frontend Grid"
    And PSKU8 must be second record in "Product Frontend Grid"

  Scenario: Check Sorter Applies After Different Actions
    When I select 10 from per page list dropdown in "Product Frontend Grid"
    Then PSKU9 must be first record in "Product Frontend Grid"
    And PSKU8 must be second record in "Product Frontend Grid"
    When I go to next page in "Product Frontend Grid"
    Then PSKU17 must be first record in "Product Frontend Grid"
    And PSKU16 must be second record in "Product Frontend Grid"
    When I reload the page
    Then PSKU17 must be first record in "Product Frontend Grid"
    And PSKU16 must be second record in "Product Frontend Grid"
    When I sort frontend grid "Product Frontend Grid" by "Name (Low to High)"
    Then PSKU19 must be first record in "Product Frontend Grid"
    And PSKU2 must be second record in "Product Frontend Grid"
    And I select 25 from per page list dropdown in "Product Frontend Grid"
    Then PSKU1 must be first record in "Product Frontend Grid"
    And PSKU10 must be second record in "Product Frontend Grid"
