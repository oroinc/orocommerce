@ticket-BB-16484
@fixture-OroProductBundle:products_frontend_search_grid.yml

Feature: Product frontstore grid default page size
  In order to see frontend product search grid
  As a Buyer
  I want to see correct records in frontend product search grid

  Scenario: Ensure that grid has default per page parameter if it is empty in request
    Given I go to "/product/?grid%5Bfrontend-product-search-grid%5D=i%3D1%26p"
    Then number of records in "Product Frontend Grid" grid should be 30
    And records in "Product Frontend Grid" should be 25
