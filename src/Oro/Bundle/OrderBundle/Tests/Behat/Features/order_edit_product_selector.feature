@regression
@ticket-BB-12766
@fixture-OroProductBundle:product_with_html.yml
Feature: Order Edit Product Selector
  In order to manage products in a Order
  As administrator
  I need to be able to select needed Products

  Scenario: Select product from widget
    Given I login as administrator
    When go to Sales/Orders
    And I click "Create Order"
    And I click "Add Product"
    And I choose Product "ProductWithHTML" in 1 row
    Then I should not see alert
