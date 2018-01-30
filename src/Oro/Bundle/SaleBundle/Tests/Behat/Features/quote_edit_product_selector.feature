@regression
@ticket-BB-12766
@fixture-OroProductBundle:product_with_html.yml
Feature: Quote Edit Product Selector
  In order to manage products in a Quote
  As administrator
  I need to be able to select needed Products

  Scenario: Select product from widget
    Given I login as administrator
    When go to Sales/Quotes
    And I click "Create Quote"
    And I click "Save and Close"
    And I should see "Quote Form" validation errors:
      | LineItemProduct | Product cannot be empty. |
    And I choose Product "ProductWithHTML" in 1 row
    Then I should not see alert
