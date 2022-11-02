Feature: Product view page for non existing product
  In order to get actual catalog information
  As a site user
  I need to receive 404 response for non existing products

  Scenario: Product view page for non existing product should return 404 response
    Given go to "/product/view/404"
    Then I should see "404 Not Found"
